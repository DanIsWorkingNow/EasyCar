<?php

require_once __DIR__.'/../Rentals/RentalHelpers.php';

use App\Livewire\Booking\CarAvailabilityPicker;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Car;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\PaymentReceived;
use App\Payments\PaymentManager;
use App\Services\PaymentService;
use App\Services\Rentals\HandoverData;
use App\Services\Rentals\RentalOperationsService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->branch = Branch::factory()->create();
    $this->car = Car::factory()->create(['branch_id' => $this->branch->id, 'price_per_day' => 100]);
});

function unpaidBooking(Car $car): Booking
{
    $booking = Booking::factory()->create([
        'start_date' => now()->addDays(4)->toDateString(), 'end_date' => now()->addDays(6)->toDateString(),
        'total_days' => 3, 'total_price' => 300,
    ]);
    $booking->cars()->attach($car->id, ['rental_start' => $booking->start_date, 'rental_end' => $booking->end_date, 'quantity' => 1, 'price' => 300]);

    return $booking->fresh();
}

function paySandbox(Payment $payment): void
{
    $request = app(PaymentManager::class)->driver('sandbox')->simulatedWebhook($payment, 'success');
    test()->call('POST', '/payments/webhook/sandbox', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_X_SANDBOX_SIGNATURE' => $request->header('X-Sandbox-Signature')], $request->getContent())->assertOk();
}

it('requires accepting the rental terms and records the version accepted', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $component = Livewire::test(CarAvailabilityPicker::class)
        ->set('startDate', now()->addDays(3)->toDateString())
        ->set('endDate', now()->addDays(5)->toDateString())
        ->call('toggleCar', $this->car->id)
        ->call('createBooking')
        ->assertHasErrors('acceptTerms');

    $component->set('acceptTerms', true)->call('createBooking')->assertHasNoErrors();

    $booking = Booking::where('user_id', $user->id)->firstOrFail();
    expect($booking->terms_version)->toBe(config('compliance.terms_version'))
        ->and($booking->terms_accepted_at)->not->toBeNull()
        ->and($booking->terms_accepted_ip)->not->toBeNull();
});

it('serves the Terms of Rental and Privacy Notice', function () {
    $this->get(route('legal.terms'))->assertOk()->assertSee('Terms of Rental')->assertSee('Late return');
    $this->get(route('legal.privacy'))->assertOk()->assertSee('PDPA');
});

it('issues a sequentially numbered tax invoice when a rental payment settles', function () {
    $booking = unpaidBooking($this->car);
    paySandbox(app(PaymentService::class)->startCheckout($booking, 'sandbox'));

    $second = unpaidBooking(Car::factory()->create(['branch_id' => $this->branch->id, 'price_per_day' => 50]));
    paySandbox(app(PaymentService::class)->startCheckout($second, 'sandbox'));

    $year = now()->year;
    $first = Invoice::orderBy('id')->first();
    expect(Invoice::count())->toBe(2)
        ->and($first->number)->toBe("INV-{$year}-000001")
        ->and(Invoice::orderBy('id')->skip(1)->first()->number)->toBe("INV-{$year}-000002")
        ->and($first->total)->toEqual(300.0)
        ->and($first->tax_amount)->toEqual(0.0)
        ->and($first->lines[0]['description'])->toContain($this->car->plate_number)
        ->and($first->billed_to['email'])->toBe($booking->user->email)
        ->and($first->seller['name'])->toBe(config('compliance.company.name'));

    // Saving the payment again never duplicates the invoice.
    $first->payment->touch();
    expect(Invoice::count())->toBe(2);
});

it('extracts tax from the inclusive total when a rate is configured', function () {
    config(['compliance.invoices.tax_rate' => 0.08]);
    $booking = Booking::factory()->create(['total_price' => 108, 'total_days' => 1]);
    $booking->cars()->attach($this->car->id, ['rental_start' => $booking->start_date, 'rental_end' => $booking->end_date, 'quantity' => 1, 'price' => 108]);

    paySandbox(app(PaymentService::class)->startCheckout($booking->fresh(), 'sandbox'));

    $invoice = Invoice::firstOrFail();
    expect($invoice->total)->toEqual(108.0)->and($invoice->tax_amount)->toEqual(8.0)->and($invoice->subtotal)->toEqual(100.0);
});

it('issues a credit note against the invoice when a payment is refunded', function () {
    $booking = unpaidBooking($this->car);
    $payment = app(PaymentService::class)->startCheckout($booking, 'sandbox');
    paySandbox($payment);

    app(PaymentService::class)->refund($payment->fresh(), 'Customer cancelled');

    $credit = Invoice::where('kind', 'credit_note')->firstOrFail();
    $original = Invoice::where('kind', 'invoice')->firstOrFail();
    expect($credit->number)->toBe('CN-'.now()->year.'-000001')
        ->and($credit->original_invoice_id)->toBe($original->id)
        ->and($credit->total)->toEqual(300.0)
        ->and($credit->lines[0]['description'])->toContain($original->number);
});

it('invoices extras paid at the counter with one line per charge', function () {
    Storage::fake('local');
    $staff = branchStaff($this->branch);
    $booking = approvedBooking([$this->car]);
    $ops = app(RentalOperationsService::class);
    at('2026-12-10 10:30');
    $ops->pickUp($booking, $this->car, $staff, new HandoverData(odometer: 1, fuel: 8, signature: signaturePng()));
    at('2026-12-13 10:00');
    $ops->returnCar($booking->fresh(), $this->car, $staff, new HandoverData(odometer: 2, fuel: 6, signature: signaturePng()));

    $this->actingAs($staff)->post(route('ops.charges.collect', $booking), ['method' => 'cash'])->assertSessionHas('success');

    $invoice = Payment::where('purpose', 'charges')->firstOrFail()->invoices()->firstOrFail();
    expect($invoice->lines)->toHaveCount(2)->and($invoice->total)->toEqual(130.0);
});

it('lets the customer and branch staff download the PDF, and nobody else', function () {
    $booking = unpaidBooking($this->car);
    paySandbox(app(PaymentService::class)->startCheckout($booking, 'sandbox'));
    $invoice = Invoice::firstOrFail();

    $this->actingAs($booking->user)->get(route('invoices.show', $invoice))->assertOk()->assertSee($invoice->number);
    $pdf = $this->actingAs($booking->user)->get(route('invoices.pdf', $invoice));
    $pdf->assertOk()->assertHeader('Content-Type', 'application/pdf');
    expect($pdf->headers->get('Content-Disposition'))->toContain($invoice->number.'.pdf');

    $this->actingAs(branchStaff($this->branch))->get(route('invoices.pdf', $invoice))->assertOk();
    $this->actingAs(User::factory()->create())->get(route('invoices.pdf', $invoice))->assertForbidden();
    $this->actingAs(branchStaff(Branch::factory()->create()))->get(route('invoices.show', $invoice))->assertForbidden();
});

it('attaches the invoice PDF to the payment receipt email', function () {
    $booking = unpaidBooking($this->car);
    $payment = app(PaymentService::class)->startCheckout($booking, 'sandbox');
    paySandbox($payment);

    $mail = (new PaymentReceived($payment->fresh()))->toMail($booking->user);
    $invoice = Invoice::firstOrFail();

    expect($mail->rawAttachments)->toHaveCount(1)
        ->and($mail->rawAttachments[0]['name'])->toBe($invoice->filename())
        ->and(substr($mail->rawAttachments[0]['data'], 0, 4))->toBe('%PDF');
});
