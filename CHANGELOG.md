# Changelog

All notable changes to EasyCar are documented in this file, organized by
integration session and grouped in [Keep a Changelog](https://keepachangelog.com/)
style (`Added` / `Changed` / `Fixed`). Entries are reverse-chronological.
Commit hashes refer to `main`.

## [Unreleased] — 2026-08-21

### Rate Limit Kit — routing fix + tiered throttling
Integrated `EasyCar_RateLimit_Kit`. Fixes TD-23 (`routes/api.php` was never
actually registered with the app — see verification note below) and TD-24
(`app/Http/Kernel.php` was dead code), then adds the tiered rate limiting
that was the original ask.

**Verification note — TD-23 didn't reproduce as the kit describes.** The
kit's docs claim the missing `api:` parameter meant *"the entire API kit
has been unreachable since it shipped"* (blanket 404). Before applying
anything, a live `GET /api/ping` with caches cleared returned `200`, not
404. Root cause: `config/app.php` still carries a legacy Laravel-≤10-style
`providers` array (left over from the Aug 20 "workflow fixes" commit) that
includes `App\Providers\RouteServiceProvider`, and Laravel 11+ still merges
`config('app.providers')` into its provider-boot list — so that old
provider was registering `routes/api.php` as an accidental side effect the
whole time. The **real**, currently-live gap was the one the kit's tiered
limiters actually fix: of 24 API endpoints, only `POST /auth/login` had any
rate limiting — the other 23 were gated by `auth:sanctum` alone with zero
throttling. Applied the kit's explicit `api:` registration anyway — it's
correct hygiene regardless of the accidental fallback, and removes the
fragility (that fallback would vanish if `config/app.php` is ever cleaned
up to the modern minimal skeleton).

### Added
- Three named rate limiters in `AppServiceProvider`: `login` (5/min by
  IP+email, unchanged from Level 3), `api` (120/min per authenticated user,
  30/min per IP for guests — applies to every `/api/v1/*` request), and
  `api-write` (30/min, stacked on top of `api` for anything that mutates
  data: booking/car/user writes, plus the CSV export).
- `tests/Feature/Api/RoutingSmokeTest.php` — regression test asserting a
  known API route returns 401 (route exists, needs auth), not 404, so a
  TD-23-style regression fails CI instead of silently shipping again.
- `tests/Feature/Api/RateLimitingTest.php` — confirms the login/api/
  api-write limiters actually 429 once exceeded.

### Fixed
- `bootstrap/app.php` now explicitly names `api: routes/api.php` in
  `withRouting()`, replacing the accidental legacy-provider fallback
  described above with the real, modern registration path.
- **Kit-shipped route-ordering bug, caught before merging.** The kit's
  `routes/api.php` moved `GET /bookings/export` to *after*
  `GET /bookings/{booking}` while restructuring around the new
  `throttle:api-write` group. Laravel matches routes in registration
  order, so a request to `/bookings/export` would have been swallowed by
  the `{booking}` wildcard (`booking="export"`, a 404 from failed
  route-model binding) instead of reaching `BookingController::export()`
  — exactly the class of bug the *original* API kit's own comment
  (*"/export and /bulk-approve before /{booking} for the same reason"*)
  was written to prevent. Proved this empirically: temporarily applied the
  kit's unmodified file and watched the new regression test fail with a
  real 404, then restored the fix. `/bookings/export` and
  `/bookings/bulk-approve` are now registered before the `{booking}`
  wildcard, still under `throttle:api-write`. Added a dedicated regression
  test for this in `tests/Feature/Api/BookingApiTest.php`, since neither
  the kit's own tests nor the existing suite exercised the export route at
  all.
- Deleted `app/Http/Kernel.php` (`PATCHES_RATELIMIT.md`'s optional TD-24
  cleanup) — confirmed dead code (nothing in the app binds `App\Http\
  Kernel`; route count was identical, 101, before and after deletion).
  `AdminMiddleware.php`/`StaffMiddleware.php` were already gone from an
  earlier session.

**Files:** `bootstrap/app.php`, `app/Providers/AppServiceProvider.php`,
`routes/api.php`, `tests/Feature/Api/{RoutingSmokeTest,RateLimitingTest}.php`,
`tests/Feature/Api/BookingApiTest.php`, `app/Http/Kernel.php` (deleted).
**Verified:** full 41-test suite green, Pint clean, Larastan shows no new
findings (57 pre-existing, same file set as before), live curl confirms
login throttling (429 on the 6th attempt) and `/api/v1/bookings` returning
401 not 404. *(Not yet committed.)*

---

## View Car in Add Booking Fixes — 2026-08-20 (`680a5bc`)
Reported: car photos that used to show on the booking-creation page were
missing after the Enhancement Kit rewrite.

### Fixed
- The old `bookings/create.blade.php` derived each car's image path
  straight from its `model` name (`images/cars/{model_slug}.jpeg`); the new
  Livewire picker instead checked a `photo` DB column, which is empty for
  every seeded car — so no image ever rendered. Added a `photo_url`
  accessor on `Car.php`: prefers an admin-uploaded `photo` if set, else
  falls back to the same static-asset convention as before. Wired into the
  Livewire picker and, for consistency, the two admin car pages that had
  the same dead `@if($car->photo)` check.
- Renamed all 10 files in `public/images/cars/` to lowercase
  (`Bezza.jpeg` → `bezza.jpeg`, etc.) via `git mv`. The old mixed-case
  names only "worked" because this dev box's filesystem is
  case-insensitive — the Forge deploy target is Linux (case-sensitive), so
  the original convention would have silently 404'd there.

**Files:** `app/Models/Car.php`, `resources/views/livewire/booking/car-availability-picker.blade.php`,
`resources/views/admin/cars/{edit,show}.blade.php`, `public/images/cars/*`.
**Verified live:** all 10 car images load with real pixel dimensions, zero
console errors on a fresh tab.

---

## Change Log Documentation & View Fix — 2026-08-20 (`168c467`)

### Fixed — Tailwind styling broken on `/bookings/create`
Reported as *"The interface changed back to basic, please check and fix
back."* Two stacked bugs, both global (affect every page, not just
bookings/create):

1. **Stale Tailwind utilities.** `resources/css/app.css` still used the
   Tailwind v3 syntax (`@tailwind base; @tailwind components; @tailwind
   utilities;`) even though the project runs Tailwind v4
   (`@tailwindcss/postcss`). v4's automatic content-scanner only activates
   off the `@import "tailwindcss";` entry point, so it never scanned new
   Blade/Livewire files (e.g. the new car-availability picker) for utility
   classes to compile — they were silently missing from the built CSS.
   **Fix:** switched to `@import "tailwindcss";`.
2. **Bootstrap silently overriding Tailwind app-wide.** Bootstrap 5 was
   loaded via a plain `<link>` tag (unlayered CSS). Tailwind v4 wraps its
   own output in CSS cascade layers (`@layer theme, base, components,
   utilities`) — and **unlayered CSS always beats layered CSS**, regardless
   of specificity or `<link>` order. Bootstrap's reboot rule
   `button { border-radius: 0 }` was quietly clobbering Tailwind classes
   like `.rounded-lg` and `.bg-indigo-600` everywhere in the app. **Fix:**
   Bootstrap is now imported inside `app.css` into an explicit `bootstrap`
   cascade layer, declared *before* the Tailwind import, so Tailwind's
   layers correctly win.
3. Removed a dead `<link href="{{ asset('css/app.css') }}">` in the base
   layout (no such file is ever built — Vite outputs to `public/build/`),
   which was 404'ing on every single page load.

**Files:** `resources/css/app.css`, `resources/views/layouts/app.blade.php`
**Verified live:** button `border-radius` 0px → 8px, selected-car ring and
indigo submit button now render with real computed colors, zero console
errors on a fresh page load.

---

## Car Availability Enhancement — 2026-08-20 (`8325ea1`)
Integrated `EasyCar_Enhancement_Kit` — live car-availability filtering for
the booking-creation flow.

### Added
- `app/Services/BookingCreationService.php` — single source of truth for
  booking creation (conflict check → DB transaction → per-car price calc),
  used directly by the new Livewire picker.
- `app/Livewire/Booking/CarAvailabilityPicker.php` +
  `resources/views/livewire/booking/car-availability-picker.blade.php` —
  replaces the old static form on `/bookings/create` with a live-filtering
  grid (dates, branch, car type, transmission), max-2-car selection, and
  submission via `BookingCreationService`.
- `plate_number` column on cars (FR-CAR-06): migration, `Car` model,
  factory, admin/API controllers, `CarResource`, and all car
  create/edit/show/index views updated to validate/display it.
- `app/Console/Commands/BackfillCarPlateNumbers.php` — one-off command to
  assign plate numbers to pre-existing cars; already run (assigned `WA
  1000`–`WJ 1009` to the 10 seeded cars).
- `tests/Feature/BookingCreationServiceTest.php`,
  `tests/Feature/CarAvailabilityPickerTest.php`.

### Fixed
- **Carbon 3 `diffInDays()` float-precision bug (4th occurrence in this
  codebase).** `total_days` was coming out as e.g. `3.0000000001` instead
  of a clean `3`, failing a strict `toBe(3)` test assertion. Fixed with an
  explicit `(int)` cast in `BookingCreationService::create()`.
- `resources/views/staff/cars/index.blade.php` was a **completely empty
  file** (discovered while diffing current state against the kit before
  patching) — built from scratch as a branch-scoped car list, matching the
  existing staff page gradient styling.

### Deliberately not applied
- The kit's optional consolidation patches (its `PATCHES_ENHANCEMENT.md`
  §4–§5) would have pointed the legacy web `BookingController::store()` at
  the new shared service. Skipped: that controller enforces an extra rule
  (max 2 cars across *all* overlapping bookings for a user) that isn't
  replicated in the new service/API/Livewire paths — applying the patch as
  written would have silently dropped that validation.

---

## Postman API Enhancement — 2026-08-20 (`802745b`)
Combined integration of `EasyCar_Level3_Kit` (notifications, role audit
trail, car status, security hardening) and `EasyCar_API_Kit` (REST API +
Sanctum + Postman collection).

### Added
- Notifications: `BookingConfirmed`, `BookingStatusChanged`,
  `RentalReminder`, `WeeklySummaryReport`, plus scheduled commands
  `SendRentalReminders` / `SendWeeklySummaryReport`.
- `RoleAuditLog` model + migration and `UserRoleSyncService` — audit trail
  for role changes.
- `status` column on cars; Sanctum config + `personal_access_tokens`
  migration (API auth/security hardening).
- REST API: `Api/V1` controllers (Auth, Booking, Branch, Car, Dashboard,
  User) + API Resources (`BookingResource`, `BranchResource`,
  `CarResource`, `UserResource`).
- `postman/EasyCar_API.postman_collection.json` +
  `EasyCar_Local.postman_environment.json`, and the same collection/
  environment created directly in the user's connected Postman cloud
  workspace via the Postman MCP connector.
- `tests/Feature/Api/*`, `tests/Feature/NotificationTest.php`,
  `tests/Feature/RoleAuditTest.php`.

### Fixed
- Two API tests were failing only because Sanctum's `RequestGuard` caches
  the resolved user across sequential calls **within one Pest test
  method** — verified as a test-harness artifact (not a real bug) via
  direct `curl` requests against the live server, then fixed the tests
  with `$this->app['auth']->forgetGuards();` between calls.

### Preserved (explicit instruction — do not delete)
- `sendApprovalNotification()` / `sendRejectionNotification()` in
  `app/Http/Controllers/Admin/BookingController.php` are flagged as unused
  by Larastan but were kept, per instruction: *"Let's not delete future
  method that is going to be used"* — reserved for future use.

---

## CI/CD fixes — 2026-08-20 (`4713100`, `4533196`, `1d03ae2`)

### Fixed
- **PHP version mismatch in GitHub Actions.** CI pinned PHP 8.2, but
  locked Symfony/Spatie packages actually require PHP ≥ 8.4. Bumped
  `composer.json` (`"php": "^8.2"` → `"^8.4"`) and
  `.github/workflows/ci.yml` (`php-version: '8.2'` → `'8.4'`), resynced
  `composer.lock`.
- **Pint code-style violations** — resolved by the user running
  `./vendor/bin/pint` locally and committing; verified clean afterward
  with the full Pest suite passing.

### Added
- `phpstan.neon` — Larastan was installed but had **no config file**, so
  static analysis was silently a no-op in CI. Added level-5 config against
  `app/`; verified it now actually runs (63 informational findings, CI
  step set to `continue-on-error: true`).

---

## Level 2 enhancement — 2026-08-19 (`cbdf012`, `d2df040`, `74f8490`)
Integrated `EasyCar_Level1Part2_and_Level2_Combined` — auth cutover to
Spatie roles + Livewire admin dashboard. User explicitly approved the
cutover (had opted for Phase-1-only, additive auth, in the prior Level 1
integration).

### Added
- Spatie `laravel-permission` roles/permissions tables +
  `RolesAndPermissionsSeeder`.
- Livewire dashboard: `DashboardIndex`, `KpiCards`, `BookingTrendChart`,
  `BranchComparisonTable`, `PendingApprovalQueue` + `DashboardService`.
- `database/seeders/BookingDemoSeeder.php` (`74f8490`) — meaningful seed
  data so dashboard charts render real trends, per request: *"Can you
  provide to me the seeder data for this dashboard to show chart and
  meaningful data?"*

### Fixed
- **Role middleware alias registered in the wrong place** (`d2df040`) —
  Laravel 12 dropped `app/Http/Kernel.php` middleware-alias registration
  in favor of `bootstrap/app.php`; the kit's instructions still targeted
  `Kernel.php`. Fixed by registering the alias in `bootstrap/app.php`.
- **Dashboard regression: stray test data.** A `Branch::factory()`-created
  "Gombak 4932" branch, left over from earlier `tinker` debugging, was
  showing up in the live Branch Performance Comparison chart. Deleted
  after confirming no dependent cars/users.
- **Trend chart not rendering (ApexCharts undefined).** Caused by a stale
  Vite `public/hot` file left over from an earlier dev-server session,
  which made Blade emit dev-server `<script>` tags instead of the built
  bundle. Deleted `public/hot` and ran a fresh `npm run build`.

---

## Enhancement Level 1 Complete — 2026-08-19 (`687c83f`)
Integrated `EasyCar_Level1_Kit` + `EasyCar_Level1_Part2_Kit`. User chose
**Phase 1 only** (bug fixes + testing safety net + additive Spatie
scaffolding, no auth cutover yet).

### Added
- `app/Services/BookingAvailabilityService.php` — shared conflict-checking
  logic.
- `tests/Feature/BookingConflictTest.php` — regression safety net.
- Staff booking management views, car edit/show admin views.

### Fixed
- `resources/views/staff/bookings/index.blade.php` and
  `staff/bookings/show.blade.php` were **completely empty files** —
  discovered while diffing current state before patching; built from
  scratch.

---

## Manage Users Enhancement — 2026-08-18 (`6e21a84`, `e46a326`, `0fc6198`)
Pre-kit fixes, from the start of this integration effort.

### Fixed
- Admin "Manage Users" page was broken/incomplete — rebuilt
  `admin/users/{index,create,edit}.blade.php` from scratch and visually
  enhanced per request (*"Can you enhance the interface it feels very
  plain"*).
- **Duplicate migrations** adding the same columns twice (approval columns
  and rejection reason on `bookings`, `price_per_day` on `cars`) — removed
  the duplicate migration files (`e46a326`).
- `composer.lock` drift + dead routes cleaned up (`0fc6198`).
- Dashboard layout/Bootstrap CSS issues and DB-viewing tooling fixed
  (earliest work in this session, prior to the kit integrations above).

---

## Notes for future integrations
- **Recurring bug class:** Carbon 3's `diffInDays()` can return a float
  with precision noise. Always cast explicitly: `(int) Carbon::parse($a)
  ->diffInDays($b)`. Fixed 4 times across this codebase so far.
- **Recurring bug class:** newly-scaffolded Blade view files shipped
  completely empty by kits, 3 times so far (`staff/bookings/index.blade.php`,
  `staff/bookings/show.blade.php`, `staff/cars/index.blade.php`). Always
  diff kit deliverables against the actual current file content, not just
  the kit's own docs.
- **Do not delete** `sendApprovalNotification()` /
  `sendRejectionNotification()` in `Admin\BookingController.php` — flagged
  unused by Larastan but reserved for future use per explicit instruction.
- **CSS architecture:** Bootstrap must stay imported via the `bootstrap`
  cascade layer in `resources/css/app.css` (not a raw `<link>`) or
  Tailwind utilities will silently lose the cascade again.
- **Recurring bug class:** kits restructuring `routes/api.php` around new
  middleware groups have twice now silently reordered a literal route
  (`/bookings/export`) to *after* a wildcard route (`/bookings/{booking}`)
  that would swallow it. Laravel matches routes in registration order —
  always check that literal/prefix routes stay before same-method wildcard
  routes they could collide with, and don't trust a kit's own route
  comments to have survived its own restructuring.
- **Static asset filename case matters.** This dev box's filesystem is
  case-insensitive; the Forge deploy target (Linux) is not. Any new static
  asset convention (image paths, etc.) should be tested for case match, not
  just existence, before assuming it'll work in production.
- **`config/app.php`'s legacy `providers` array is still live** in this
  app (left over from an Aug 20 Pint-driven commit) and silently duplicates
  what `bootstrap/providers.php` and `bootstrap/app.php`'s `withRouting()`
  are supposed to own — e.g. it was accidentally making `routes/api.php`
  reachable before `bootstrap/app.php` explicitly registered it. Worth a
  dedicated cleanup pass at some point to remove the legacy array now that
  the modern registration paths are explicit, rather than leaving both
  mechanisms doing the same job.
