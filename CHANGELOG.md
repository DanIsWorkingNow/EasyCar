# Changelog

All notable changes to EasyCar are documented in this file, organized by
integration session and grouped in [Keep a Changelog](https://keepachangelog.com/)
style (`Added` / `Changed` / `Fixed`). Entries are reverse-chronological.
Commit hashes refer to `main`.

## [Unreleased] — 2026-08-20

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
errors on a fresh page load. *(Not yet committed.)*

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
