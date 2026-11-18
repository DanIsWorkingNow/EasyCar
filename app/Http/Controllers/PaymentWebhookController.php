<?php

namespace App\Http\Controllers;

use App\Payments\Exceptions\InvalidSignatureException;
use App\Payments\Exceptions\PaymentException;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * POST /payments/webhook/{gateway}. CSRF-exempt (see bootstrap/app.php);
 * every request is authenticated by the gateway driver's signature check
 * or server-side confirmation instead.
 */
class PaymentWebhookController extends Controller
{
    public function __invoke(Request $request, string $gateway, PaymentService $payments)
    {
        try {
            $payments->handleWebhook($gateway, $request);
        } catch (InvalidSignatureException $e) {
            Log::warning('Rejected payment webhook', ['gateway' => $gateway, 'reason' => $e->getMessage(), 'ip' => $request->ip()]);

            return response()->json(['message' => 'Invalid signature.'], 400);
        } catch (PaymentException $e) {
            Log::error('Payment webhook failed', ['gateway' => $gateway, 'error' => $e->getMessage()]);

            return response()->json(['message' => 'Unable to process.'], 422);
        }

        return response()->json(['received' => true]);
    }
}
