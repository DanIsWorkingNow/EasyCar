# EasyCar

Car rental for the Klang Valley: a cinematic storefront where every car can be explored in 3D, a booking flow with live availability, online payments (Stripe, Billplz, ToyyibPay) with automatic refunds, and operations consoles for admins and branch staff.

Built with **Laravel 12**, **Livewire 4**, **Tailwind CSS 4** and **three.js**.

---

## Features

| Area | What you get |
| --- | --- |
| **Storefront** | Dark, cinematic theme with a hero billboard, horizontally scrolling rows, a ranked "Top 10" row, hover-reveal car tiles, an FAQ, and an email-capture sign-up. Fully responsive and keyboard-accessible. |
| **3D showroom** | Every car gets an interactive 3D model: orbit/zoom, paint colours, camera presets, headlights, fullscreen. Models are generated per body type (sedan, hatchback, SUV, MPV) by default; admins can upload a real `.glb` per car. three.js is lazy-loaded, rendering pauses off-screen, and there's a photo fallback when WebGL isn't available. |
| **Booking** | Live availability across branches, a 2-day minimum notice, up to 2 cars per customer for overlapping dates, server-side pricing, and customer edit/cancel. |
| **Payments** | Driver-based framework (`app/Payments`) with Stripe Checkout, Billplz and ToyyibPay drivers plus a built-in sandbox. Webhooks are signature-verified and idempotent. Rejected or cancelled paid bookings are refunded automatically. Unpaid holds are released on a schedule. |
| **Rental operations** | A board per branch showing pickups, returns due, cars on trip, overdue returns and no-shows. A tablet-friendly handover form records mileage, fuel, a checklist, damage on a tap-to-mark car diagram, photos and the customer's signature. Late, fuel and mileage charges are calculated automatically. |
| **Trust & compliance** | Driver verification (licence + IC/passport, encrypted at rest, private documents) with a staff review queue and enforcement at approval and pick-up; versioned rental-terms acceptance; Terms & PDPA Privacy pages; numbered tax invoices and credit notes as PDF, attached to receipt emails; branded email templates. |
| **Customer experience** | "My List" favourites, verified reviews & star ratings (with moderation and public replies), promo codes, and paid extras such as CDW protection, child seats and GPS. |
| **Pricing & revenue** | Seasonal and weekend pricing rules with a rate preview, weekly/monthly rates, refundable security deposits handled at the counter, and EasyCar Rewards points. |
| **Admin console** | Live KPIs and charts, approval queue, bookings (filters, bulk approve, CSV export), payments (refunds, webhook log), reviews, promotions, extras, fleet (3D preview, `.glb` upload), branches, and users with audited role changes. |
| **Staff console** | The same tools, scoped to the staff member's branch. |
| **API** | Versioned REST API (`/api/v1`) with Sanctum tokens and tiered rate limits. A Postman environment lives in `postman/`. |

## Quick start

Requirements: PHP 8.4+, Composer, Node 20+, and SQLite (or MySQL/Postgres).

```bash
composer install
npm ci
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan db:seed --class='Database\Seeders\BookingDemoSeeder'   # optional demo bookings + payments
php artisan storage:link
composer dev   # serve + queue + logs + vite
```

Open http://localhost:8000. Demo accounts (password `password`):

| Role | Email |
| --- | --- |
| Admin | `admin@easycar.com` |
| Staff (Bangi / Shah Alam / Gombak) | `staff.bangi@easycar.com`, `staff.shahalam@easycar.com`, `staff.gombak@easycar.com` |
| Customer | `customer1@easycar.com` |

Payments use the **sandbox gateway** by default, so you can complete the whole flow (reserve → pay → webhook → receipt) without real credentials.

## Payments

Configuration lives in `config/payments.php` and environment variables (see `.env.example`). A gateway is offered at checkout only when it is enabled **and** its credentials are present.

| Gateway | Methods | Refunds | Webhook / callback |
| --- | --- | --- | --- |
| Stripe Checkout | Cards, FPX, GrabPay | Automatic via API | `POST /payments/webhook/stripe` (HMAC `Stripe-Signature`, 5-minute replay window) |
| Billplz | FPX | Flagged for manual refund | `POST /payments/webhook/billplz` (`x_signature` HMAC) |
| ToyyibPay | FPX, cards | Flagged for manual refund | `POST /payments/webhook/toyyibpay` (status re-confirmed through the ToyyibPay API, never trusted from the POST) |
| Sandbox | Simulated | Automatic | `POST /payments/webhook/sandbox` (HMAC with the app key) |

How it works:

- `PaymentService` creates a `Payment` and redirects the customer to the gateway's hosted page. Card data never touches EasyCar.
- Every verified notification is stored in `payment_events` under a unique `(gateway, event_id)` key, so retries and replays are no-ops.
- Transitions run under a row lock. A paid payment can never be moved back to failed by a late event; a duplicate or orphaned payment is refunded automatically.
- Rejecting or cancelling a paid booking refunds it. Gateways without a refund API mark the payment `refund_pending`, and an admin confirms the manual refund from **Admin → Payments**.
- `bookings:expire-unpaid` (every 5 minutes) releases cars held by unpaid bookings after `PAYMENTS_HOLD_MINUTES`.

**Adding a gateway:** implement `App\Payments\Contracts\PaymentGateway`, add a `createXxxDriver()` method to `PaymentManager` (or call `->extend()`), and add an entry to `config/payments.php`.

## Rental operations

The trip lifecycle is **approved → picked up (on trip) → returned → completed**, driven by staff at the branch counter from **Operations** in the console.

- **Pickup:** allowed from the booking's start date. Staff record mileage and fuel, tick the condition checklist, mark any existing damage on the diagram, take photos, check the driving licence and capture the customer's signature. If the booking is still unpaid, staff must record payment taken at the counter (cash, card terminal or bank transfer) before handing over the keys. The car becomes `rented`.
- **Return:** the form starts from the pickup values and shows new damage against what was recorded at pickup. Automatic charges follow `config/rentals.php`:
  - **Late return:** each started 24 hours past the due time (end date at `RENTAL_RETURN_TIME`), after `RENTAL_LATE_GRACE_MINUTES`, at the daily rate × `RENTAL_LATE_FEE_MULTIPLIER`.
  - **Fuel:** each eighth of a tank short × `RENTAL_FUEL_CHARGE_PER_EIGHTH`.
  - **Mileage:** kilometres over `RENTAL_DAILY_KM_ALLOWANCE` per day × `RENTAL_EXCESS_KM_RATE`. This applies only if an allowance is set.

  Staff can add damage or cleaning charges and send the car to maintenance. Once every car on the booking is back, the booking is `completed`.
- **Extra charges:** customers pay them online from their booking page through the configured gateways (`payments.purpose = charges`, kept separate from the rental payment). Staff can also record them as paid at the counter, or waive a charge with a reason. Refunding an extras payment waives the charges it covered.
- **Reports:** every handover produces a printable report. Photos and signatures are stored on the **private** `local` disk and served only to the customer, staff at the car's branch, and admins.
- **Alerts:** `rentals:flag-overdue` (hourly) emails the customer, the branch team and admins once when a car is overdue.

## Trust & compliance

- **Driver verification:** customers upload their licence (front/back) and IC or passport from **Account → Driver verification**. Licence and ID numbers are stored with Laravel's `encrypted` cast; images go on the private `local` disk and are streamed only to the owner and staff. Staff review submissions in **Operations → Verifications**. With `COMPLIANCE_REQUIRE_VERIFIED_DRIVER=true` (the default), a booking can't be approved or handed over until the driver is verified, the licence is valid through the return date, and they meet `COMPLIANCE_MIN_DRIVER_AGE`. Staff see the exact reason on the booking.
- **Rental agreement:** the booking flow requires accepting the Terms of Rental. The accepted `COMPLIANCE_TERMS_VERSION`, time and IP are stored on the booking. `/terms` and `/privacy` (PDPA) are templates; have them reviewed before launch.
- **Invoices:** every settled payment (online, counter, rental or extras) gets an immutable tax invoice numbered `INV-YYYY-NNNNNN`; every refund gets a credit note `CN-YYYY-NNNNNN` referencing it. Seller, buyer and lines are snapshotted at issue time. Prices are tax-inclusive; set `INVOICE_TAX_RATE` to show the tax portion. PDFs (dompdf, ~25 KB) are attached to receipt and refund emails and downloadable from the booking and admin pages.

## Customer experience

- **My List:** customers save cars with the heart on any tile or car page; saved cars appear at `/my-list` and as a row on the storefront and home page.
- **Reviews:** only customers with a completed booking can review a car, once per car per booking, within `REVIEWS_WINDOW_DAYS` (default 90) of completion. Staff reply or hide reviews in **Operations → Reviews**. Car pages output schema.org `AggregateRating` for search results.
- **Promo codes** (**Admin → Promotions**): percent or fixed, with optional cap, minimum spend, validity window, total and per-customer limits, and first-booking-only. A redemption is any booking using the code that isn't cancelled or rejected, so cancelling frees it up. `WELCOME10` and `WEEKEND20` are created automatically.
- **Extras** (**Admin → Extras**): priced per day or per trip, with a max quantity. Prices are snapshotted on each booking, so changes apply only to new bookings.
- **No manual seeding:** `php artisan migrate` (run by the deploy script) creates the four default extras and the two launch codes on new and existing installs. It only adds missing rows, so it never overwrites an extra or code an admin has edited.
- The booking total is `rental + extras − discount`. Invoices itemise extras and show the discount as a negative line. The API accepts `addons: [{id, quantity}]` and `promo_code` on `POST /api/v1/bookings`.

## Pricing & revenue

- **Daily rates:** base `price_per_day` × the best season rule × the best day-of-week rule for that day (**Admin → Pricing**; rules can target a car type or branch, and priority breaks ties). The page previews each car's rates for 5 weeks. Changes apply to new bookings only.
- **Long rentals:** `PRICING_WEEKLY_DISCOUNT` (default 10%, 7+ days) and `PRICING_MONTHLY_DISCOUNT` (20%, 28+ days) off the car hire.
- **Security deposit:** car's own amount, else `pricing.deposit.by_type`, else `PRICING_DEPOSIT_DEFAULT` (RM 200). Snapshotted on the booking; staff must record it (card pre-auth, cash or transfer) at the first pick-up when `PRICING_DEPOSIT_REQUIRED=true`. After the return, the booking page offers **Release full deposit** or **Use for charges & release rest** (only when the deposit covers all extra charges).
- **Rewards:** `LOYALTY_POINTS_PER_RINGGIT` (1) on completed bookings; redeem from `LOYALTY_MIN_REDEEM` (100) points at `LOYALTY_POINT_VALUE` (RM 0.05) each, up to `LOYALTY_MAX_REDEEM_PERCENT` (50%) of a booking. Cancelled or rejected bookings return points. Admins adjust balances on the user's page; customers see `/rewards`.

## 3D models

- The default model is generated from extruded side profiles in `resources/js/car-viewer/procedural-car.js`, with paint taken from the car's **Paint colour** field.
- To use a real model, upload a binary glTF (`.glb`, up to 25 MB) on **Admin → Fleet → Edit**. It is auto-scaled, centred and grounded. Name the body material `paint` (or `body`/`carpaint`) to keep the colour picker working.
- Make sure you hold the licence for any model you upload.

## Operations

```bash
php artisan test                 # 165 Pest tests
./vendor/bin/pint --test         # code style
./vendor/bin/phpstan analyse     # Larastan
npm run build                    # production assets
```

**SIT (System Integration Testing):** `deploy/sit/setup.sh` builds an isolated SIT environment (own database, sandbox payments, emails to the log). `node tests/SIT/sit-pack.cjs` then runs 47 end-to-end browser cases across all modules. See [docs/SIT.md](docs/SIT.md).

The scheduler (`php artisan schedule:run` via cron) handles reminders, the weekly report, payment-hold expiry and overdue-return alerts. Queue workers deliver email notifications. `deploy/forge-deploy-script.sh` is a zero-downtime Laravel Forge deploy script.

**Production checklist:** set `APP_ENV=production` and `APP_DEBUG=false`; set `PAYMENTS_SANDBOX_ENABLED=false`; configure at least one real gateway and register its webhook URL; set `TRUSTED_PROXIES` if you're behind a load balancer; and configure mail.

## Project layout

```
app/Payments/            gateway contract, manager, drivers, DTOs
app/Services/            booking creation/availability, payments, dashboard
app/Services/Rentals/    handover (pickup/return), charge calculator
app/Livewire/            booking picker, live dashboards, handover form
resources/js/car-viewer/ three.js viewer + procedural car builder
resources/views/         layouts, components (x-car-tile, x-car-viewer…), pages
tests/Feature/           Pest feature tests (payments, lifecycle, pages, API)
```

See [docs/ROADMAP.md](docs/ROADMAP.md) for the 10-phase plan and [CHANGELOG.md](CHANGELOG.md) for the history of changes.
