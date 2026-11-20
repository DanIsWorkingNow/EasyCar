@extends('legal.layout', ['title' => 'Privacy Notice'])

@section('title', 'Privacy Notice')

@section('legal')
    <p>This notice explains how {{ config('compliance.company.name') }} processes your personal data under Malaysia's Personal Data Protection Act 2010 (PDPA).</p>
    <section>
        <h2>What we collect</h2>
        <ul>
            <li>Account details: name, email, password (hashed) and phone number.</li>
            <li>Driver verification: driving licence and IC/passport numbers (encrypted), date of birth and document photos.</li>
            <li>Rentals: bookings, handover readings, photos and signatures, payments and invoices. We never see or store your card details — payments are handled by our payment providers.</li>
        </ul>
    </section>
    <section>
        <h2>Why we use it</h2>
        <ul>
            <li>To verify you can legally drive, provide and insure the rental, take payment, issue invoices and resolve disputes.</li>
            <li>To send booking, payment and safety notices. We don't sell your data or send marketing without consent.</li>
        </ul>
    </section>
    <section>
        <h2>Who can see it</h2>
        <ul>
            <li>Our branch staff and administrators, on a need-to-know basis. Documents and handover photos are stored privately and served only to you and authorised staff.</li>
            <li>Payment providers (Stripe, Billplz or ToyyibPay) for the payments you make, and authorities where the law requires.</li>
        </ul>
    </section>
    <section>
        <h2>How long we keep it</h2>
        <ul>
            <li>Verification documents are replaced when you resubmit. Booking, payment and invoice records are kept for as long as tax law requires (currently 7 years).</li>
        </ul>
    </section>
    <section>
        <h2>Your rights</h2>
        <ul>
            <li>You can access and correct your data from your account, and ask us for a copy or to delete your account by emailing {{ config('compliance.company.email') }}.</li>
        </ul>
    </section>
@endsection
