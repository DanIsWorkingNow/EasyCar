<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * - invoices: immutable, sequentially numbered tax invoices (one per
 *   settled payment) and credit notes (one per refund). Seller, buyer and
 *   lines are snapshotted so later edits never change an issued document.
 * - bookings: which version of the rental terms the customer accepted.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 12); // invoice | credit_note
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('sequence');
            $table->string('number', 30)->unique();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('original_invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->string('currency', 3);
            $table->decimal('subtotal', 10, 2);
            $table->decimal('tax_rate', 5, 4)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->json('lines');
            $table->json('seller');
            $table->json('billed_to');
            $table->timestamp('issued_at');
            $table->timestamps();

            $table->unique(['kind', 'year', 'sequence']);
            $table->unique(['payment_id', 'kind']);
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->string('terms_version', 20)->nullable()->after('overdue_notified_at');
            $table->timestamp('terms_accepted_at')->nullable()->after('terms_version');
            $table->string('terms_accepted_ip', 45)->nullable()->after('terms_accepted_at');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['terms_version', 'terms_accepted_at', 'terms_accepted_ip']);
        });
        Schema::dropIfExists('invoices');
    }
};
