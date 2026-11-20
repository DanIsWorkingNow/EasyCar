# EasyCar — 10-phase roadmap to a 5-star product

Each phase ships as its own pull request with tests, docs and a browser
walkthrough, and leaves `main` deployable. The ordering puts what a real
rental business needs to operate legally and safely first, then revenue
and growth, then polish and scale.

| # | Phase | Status | Why it matters for a 5-star rating |
|---|---|---|---|
| 1 | **Production revamp** — cinematic UI, 3D showroom, payment gateway framework, core bug fixes | ✅ Done (PR #1) | First impression, trust at checkout, a stable base |
| 2 | **Rental operations** — pickup/return handovers, trip lifecycle, extra charges, operations board | ✅ Done (PR #2) | Bookings actually finish; disputes have evidence |
| 3 | **Trust & compliance** — driver verification (licence + IC/passport), rental agreement acceptance, tax invoices & receipts, branded emails, Terms/Privacy (PDPA) | ✅ Done (PR #3) | Legally rentable cars, professional paperwork, fewer fraud and no-show losses |
| 4 | **Customer experience** — "My List" favourites, verified reviews & ratings, promo codes, paid add-ons (child seat, GPS, insurance) | Planned | Social proof, repeat visits, higher basket value |
| 5 | **Pricing & revenue** — seasonal/weekend pricing, weekly & monthly rates, security-deposit holds, loyalty points | Planned | Competitive prices, protected margins, retention |
| 6 | **Fleet & maintenance** — service schedules by km/date, maintenance log & costs, road-tax/insurance/Puspakom expiry alerts, automatic unavailability while in service | Planned | Safe, legal cars; fewer breakdowns and last-minute cancellations |
| 7 | **Analytics & reporting** — revenue & utilisation per car/branch, cancellations and no-shows, cohorts, accounting (CSV/Excel) exports, scheduled reports | Planned | Decisions from data; finance can close the books |
| 8 | **Security & privacy hardening** — 2FA for staff/admins, full activity audit log, PDPA data export & account deletion, session/device management, strict CSP | Planned | Protects customer data; enterprise-grade trust |
| 9 | **Localisation, accessibility & mobile** — Bahasa Melayu/English, WCAG 2.2 AA audit fixes, installable PWA, performance budgets (Core Web Vitals) | Planned | Usable by every customer, on every device |
| 10 | **Integrations & scale** — WhatsApp/SMS notifications, Google Maps branch finder, calendar invites, public API docs + outbound webhooks, error monitoring, queue/cache on Redis, load testing | Planned | Meets customers where they are; stays fast and observable at scale |

## Phase 3 — Trust & compliance (scope)

1. **Driver verification (KYC).** Customers upload their driving licence
   (front/back) and IC or passport, with licence number, expiry date and
   date of birth. Documents are stored on the private disk. Staff review a
   verification queue and approve or reject with a reason. Approving a
   booking requires a verified driver (configurable), with licence still
   valid on the return date and minimum age met.
2. **Rental agreement.** Customers accept the versioned rental terms when
   booking; the version, time and IP are recorded on the booking.
   Public Terms of Rental and Privacy Notice (PDPA) pages.
3. **Invoices & receipts.** Every settled payment gets an immutable,
   sequentially numbered tax invoice (rental or extras). Prices are
   tax-inclusive; the SST portion is shown when a rate is configured.
   Downloadable PDF, attached to the payment receipt email.
4. **Branded email templates.** Dark, on-brand layout for every
   notification email.

## Definition of done (every phase)

- Feature tests for the new behaviour plus the full suite green in CI.
- Pint clean, Larastan 0 findings, `npm run build` succeeds.
- Playwright walkthrough of the new screens (desktop + mobile), no
  console errors.
- README + CHANGELOG updated; upgrade notes for any migration.
