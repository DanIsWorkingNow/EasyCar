# SIT — System Integration Testing

SIT runs every module together, end to end, in a production-like environment with its own database and data. It runs in a real browser, the way customers, branch staff and admins use the system.

## The SIT environment

| | |
| --- | --- |
| Env file | `.env.sit` (created from `deploy/sit/env.sit.example`, git-ignored) |
| Database | `database/sit.sqlite`, separate from local development |
| Payments | Sandbox gateway only, so no real money moves |
| Email | Written to `storage/logs/laravel.log` (`MAIL_MAILER=log`) |
| Business rules | Production settings: verified drivers required, deposits required, Rewards on |
| Data | Demo seed plus `SitScenarioSeeder`, with dates relative to today |

```bash
deploy/sit/setup.sh                              # build or rebuild from scratch
php artisan serve --env=sit --port=8100          # start SIT
node tests/SIT/sit-pack.cjs storage/sit-results  # run the pack (exit code 1 if anything fails)
```

To run the pack against a hosted staging server instead, set `SIT_BASE_URL` and seed that environment with the same seeders.

### SIT accounts (password `password`)

| Account | Purpose |
| --- | --- |
| `sit.verified@easycar.test` | Verified driver: a pick-up today, a car due back today, a completed trip, a pending paid booking |
| `sit.new@easycar.test` | Unverified driver with a pending booking (approval must be blocked) |
| `staff.bangi@easycar.com` | Branch staff (Bandar Baru Bangi) |
| `admin@easycar.com` | Administrator |

## Test cases (47)

### M1 Storefront & catalogue

| ID | Case | Expected result | Role |
| --- | --- | --- | --- |
| SIT-01-01 | Landing page loads with hero, rows and Top 10 | HTTP 200; hero billboard, ≥3 car rows incl. "Top 10 this month" | guest |
| SIT-01-02 | Browse filter by type (SUV) | Only SUVs in the results grid | guest |
| SIT-01-03 | Car page renders 3D showroom and availability calendar | WebGL canvas appears; 6-week availability grid shown | guest |
| SIT-01-04 | Weekly/monthly rate badges and deposit note on car page | Badges "Weekly rate · 10% off 7+ days" and "Monthly…"; deposit amount stated | guest |
| SIT-01-05 | Legal pages | /terms and /privacy return 200 with content | guest |
| SIT-01-06 | Mobile layout (390 px) has no horizontal scroll | Landing, browse and car pages fit the viewport | guest |

### M2 Accounts & access

| ID | Case | Expected result | Role |
| --- | --- | --- | --- |
| SIT-02-01 | Register a new customer account | Account created and signed in to the customer home | reg |
| SIT-02-02 | Wrong password is rejected | Stays on login with a credentials error | guest |
| SIT-02-03 | Role-based landing (admin → console) | Admin lands on /admin/dashboard | admin |
| SIT-02-04 | Customer cannot open the admin console | HTTP 403 on /admin/dashboard and /admin/pricing | customer |
| SIT-02-05 | Customer updates their profile | Success message and new name shown | reg |

### M3 Driver verification (KYC)

| ID | Case | Expected result | Role |
| --- | --- | --- | --- |
| SIT-03-01 | Customer submits licence and IC for verification | Status "Under review" after submit | reg |
| SIT-03-02 | Branch staff verifies the driver | Profile moves to Verified; customer sees Verified | staff |

### M4 Booking flow

| ID | Case | Expected result | Role |
| --- | --- | --- | --- |
| SIT-04-01 | Minimum 2-day notice is enforced | Error "Bookings must start at least 2 days from today." | customer |
| SIT-04-02 | Invalid promo code is rejected with a reason | Inline error under the promo field; total unchanged | customer |
| SIT-04-03 | Terms of Rental must be accepted | Error "Please accept the Terms of Rental to continue." | customer |
| SIT-04-04 | Reserve with an extra and promo WEEKEND20 | Booking created; page shows rental, CDW extra, RM 20 discount and deposit note | customer |
| SIT-04-05 | At most 2 cars per booking | Selecting a third car shows "You can select at most 2 cars." | customer |

### M5 Payments & invoices

| ID | Case | Expected result | Role |
| --- | --- | --- | --- |
| SIT-05-01 | Declined sandbox payment leaves booking unpaid | Payment failed message; booking still unpaid with Pay button | customer |
| SIT-05-02 | Successful sandbox payment marks booking paid and issues invoice | Paid badge; invoice INV-… listed | customer |
| SIT-05-03 | Invoice PDF downloads | application/pdf response with %PDF header | customer |
| SIT-05-04 | Cancelling a paid booking refunds it with a credit note | Booking cancelled; refund recorded; CN-… credit note | customer |

### M6 Approvals & admin bookings

| ID | Case | Expected result | Role |
| --- | --- | --- | --- |
| SIT-06-01 | Approval blocked for an unverified driver | Approve unavailable; reason about driver verification shown | admin |
| SIT-06-02 | Admin approves a verified, paid pending booking | Status becomes Confirmed/Approved | admin |
| SIT-06-03 | Admin rejects a booking with a reason | Status Rejected with the reason shown | admin |
| SIT-06-04 | Bookings CSV export | CSV file with header row and SIT references | admin |

### M7 Rental operations & deposits

| ID | Case | Expected result | Role |
| --- | --- | --- | --- |
| SIT-07-01 | Operations board shows today’s pick-up and return | Bangi board lists the Myvi pick-up and the Almera return due | staff |
| SIT-07-02 | Pick-up requires the security deposit | Submitting without a deposit shows "Record how the security deposit was taken." | staff |
| SIT-07-03 | Complete pick-up with card pre-auth deposit | Handover report opens; deposit Held on the booking | staff |
| SIT-07-04 | Return with fuel short creates a fuel charge | Check-in completes; RM 30.00 fuel charge (2/8 short) | staff |
| SIT-07-05 | Deposit settles the extra charges and the rest is released | Deposit "Part applied, rest released"; nothing outstanding | staff |
| SIT-07-06 | Handover report privacy | Owner can open the report; another customer gets 403 | customer |

### M8 Pricing & revenue

| ID | Case | Expected result | Role |
| --- | --- | --- | --- |
| SIT-08-01 | Admin creates a seasonal rule and sees it in the rate preview | Rule listed as Live; preview days carry the new rate | admin |
| SIT-08-02 | Booking flow explains weekend and holiday pricing | Car line notes "Weekend +10%" / "Year-end holidays +20%" | customer |
| SIT-08-03 | 7-day rental gets the weekly rate | Note "Weekly rate −10%" on the car line | customer |
| SIT-08-04 | Rewards page shows points from the completed trip | Balance 360 pts with a "Trip completed" entry | customer |
| SIT-08-05 | Using points lowers the booking total | Toggling "Use N Rewards points" adds a Rewards line and reduces the total | customer |
| SIT-08-06 | Admin adjusts a customer’s points | Balance increases by 100 with the reason in history | admin |

### M9 Customer experience

| ID | Case | Expected result | Role |
| --- | --- | --- | --- |
| SIT-09-01 | Add a car to My List | Heart toggles on; car appears on /my-list | customer |
| SIT-09-02 | Review a completed trip | Review posted and visible on the car page with rating | customer |
| SIT-09-03 | Staff reply to a review appears publicly | Reply shown under the review on the car page | admin |
| SIT-09-04 | Admin-created promo code works at checkout | SITPROMO (15% off) applies a discount line in the booking flow | admin |
| SIT-09-05 | Hiding an extra removes it from checkout | GPS navigator no longer offered after admin unticks "Offer at checkout" | admin |

### M10 Consoles, scoping & API

| ID | Case | Expected result | Role |
| --- | --- | --- | --- |
| SIT-10-01 | Admin dashboard KPIs and charts render | KPI cards and charts load without JavaScript errors | admin |
| SIT-10-02 | Staff only see their own branch | Bangi staff list includes Bangi bookings but not the Gombak (City) booking | staff |
| SIT-10-03 | REST API: token login and own bookings | POST /api/v1/auth/login returns a token; /api/v1/bookings lists only own bookings; no token → 401 | guest |

### M11 Notifications

| ID | Case | Expected result | Role |
| --- | --- | --- | --- |
| SIT-11-01 | Transactional emails are sent | Mail log has booking received, payment received, refund, verification and handover emails | guest |

## Defects found in SIT

| ID | Severity | Module | Defect | Status |
| --- | --- | --- | --- | --- |
| DEF-01 | High | Admin → Users | The edit form only offered Staff/Admin, so saving a customer's details (e.g. fixing a name) silently promoted them to Staff and gave them staff-console access. Pre-existing since before Phase 1. | Fixed: Customer option added, the form pre-selects the user's actual role, validation accepts 0/1/5. Regression test in `tests/Feature/RoleAuditTest.php`; SIT-08-06 also checks it. |
| OBS-01 | Low | Admin → Pricing | Day-of-week rules on Tue/Wed were labelled "Weekend" in the rules list. | Fixed: now labelled "Day of week" / "Season". |
