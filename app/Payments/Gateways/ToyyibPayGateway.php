<?php

namespace App\Payments\Gateways;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Payments\Contracts\PaymentGateway;
use App\Payments\Data\CheckoutSession;
use App\Payments\Data\GatewayResult;
use App\Payments\Data\RefundResult;
use App\Payments\Exceptions\InvalidSignatureException;
use App\Payments\Exceptions\PaymentException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * ToyyibPay bills. Callbacks and redirects are NOT treated as proof of
 * payment on their own: the bill's status is always re-confirmed
 * server-side through getBillTransactions, so a forged POST to the
 * callback URL can't mark anything paid.
 */
class ToyyibPayGateway implements PaymentGateway
{
    private const STATUS_SUCCESS = '1';

    private const STATUS_FAILED = '3';

    public function __construct(private array $config) {}

    public function name(): string
    {
        return 'toyyibpay';
    }

    public function isConfigured(): bool
    {
        return filled($this->config['secret_key'] ?? null) && filled($this->config['category_code'] ?? null);
    }

    public function createCheckout(Payment $payment, array $urls): CheckoutSession
    {
        $payment->loadMissing(['booking.cars', 'user']);
        $booking = $payment->booking;

        $response = $this->http()->asForm()->post('/index.php/api/createBill', [
            'userSecretKey' => $this->config['secret_key'],
            'categoryCode' => $this->config['category_code'],
            'billName' => Str::limit("EasyCar {$booking->reference}", 30, ''),
            'billDescription' => Str::limit($booking->car_names.' '.$booking->start_date->format('d/m').'-'.$booking->end_date->format('d/m/Y'), 100, ''),
            'billPriceSetting' => 1,
            'billPayorInfo' => 1,
            'billAmount' => $payment->amountInMinorUnits(),
            'billReturnUrl' => $urls['return'],
            'billCallbackUrl' => $urls['callback'],
            'billExternalReferenceNo' => $payment->uuid,
            'billTo' => Str::limit($payment->user->name, 60, ''),
            'billEmail' => $payment->user->email,
            'billPhone' => '0000000000',
            'billPaymentChannel' => 2,
        ]);

        $billCode = $response->json('0.BillCode');

        if (! $response->successful() || ! $billCode) {
            throw new PaymentException(
                'We could not start the online banking payment. Please try again or choose another method.',
                ['status' => $response->status(), 'body' => Str::limit($response->body(), 500)]
            );
        }

        return new CheckoutSession($billCode, $this->baseUrl().'/'.$billCode);
    }

    public function parseWebhook(Request $request): GatewayResult
    {
        $billCode = (string) $request->input('billcode', '');
        $orderId = (string) $request->input('order_id', '');

        if ($billCode === '' || $orderId === '') {
            throw new InvalidSignatureException('Missing ToyyibPay bill code.');
        }

        [$status, $transaction] = $this->confirm($billCode, $orderId);

        return new GatewayResult(
            eventId: 'bill:'.$billCode.':'.($transaction['billpaymentInvoiceNo'] ?? $request->input('refno', '')).':'.($status->value ?? 'pending'),
            type: 'bill.callback',
            status: $status,
            paymentUuid: $orderId,
            gatewayReference: $billCode,
            failureReason: $status === PaymentStatus::Failed ? (string) ($request->input('reason') ?: 'The payment was not successful.') : null,
            payload: ['callback' => $request->except('userSecretKey'), 'transaction' => $transaction],
        );
    }

    public function verifyReturn(Request $request, Payment $payment): ?GatewayResult
    {
        $billCode = (string) $request->query('billcode', '');

        if ($billCode === '' || ! hash_equals((string) $payment->gateway_reference, $billCode)) {
            return null;
        }

        [$status, $transaction] = $this->confirm($billCode, $payment->uuid);

        if ($status === null) {
            return null;
        }

        return new GatewayResult(
            eventId: 'return:'.$billCode.':'.$status->value,
            type: 'bill.return',
            status: $status,
            paymentUuid: $payment->uuid,
            gatewayReference: $billCode,
            failureReason: $status === PaymentStatus::Failed ? 'The payment was not successful.' : null,
            payload: ['transaction' => $transaction],
        );
    }

    public function supportsRefunds(): bool
    {
        return false;
    }

    public function refund(Payment $payment, float $amount, string $reason): RefundResult
    {
        throw new PaymentException('ToyyibPay does not support automatic refunds.');
    }

    /**
     * @return array{0: ?PaymentStatus, 1: array}
     */
    private function confirm(string $billCode, string $externalReference): array
    {
        $response = $this->http()->asForm()->post('/index.php/api/getBillTransactions', [
            'billCode' => $billCode,
        ]);

        $transactions = collect(is_array($response->json()) ? $response->json() : [])
            ->filter(fn ($t) => is_array($t))
            ->filter(fn ($t) => ! isset($t['billExternalReferenceNo']) || $t['billExternalReferenceNo'] === $externalReference);

        if ($paid = $transactions->firstWhere('billpaymentStatus', self::STATUS_SUCCESS)) {
            return [PaymentStatus::Paid, $paid];
        }

        if ($transactions->isNotEmpty() && $transactions->every(fn ($t) => ($t['billpaymentStatus'] ?? null) === self::STATUS_FAILED)) {
            return [PaymentStatus::Failed, $transactions->last()];
        }

        return [null, $transactions->last() ?? []];
    }

    private function baseUrl(): string
    {
        return ($this->config['sandbox'] ?? true) ? 'https://dev.toyyibpay.com' : 'https://toyyibpay.com';
    }

    private function http(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl())
            ->acceptJson()
            ->timeout(20)
            ->retry(1, 300, fn ($e) => $e instanceof ConnectionException, throw: false);
    }
}
