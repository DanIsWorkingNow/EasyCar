/*
 * EasyCar SIT (System Integration Testing) pack.
 *
 * End-to-end cases across every module, run in a real browser against the
 * SIT environment (deploy/sit/setup.sh, then `php artisan serve --env=sit --port=8100`).
 *
 *   node tests/SIT/sit-pack.cjs [outDir]          (SIT_BASE_URL to target another host)
 *
 * Writes <outDir>/results.json and one screenshot per case.
 */
const { chromium } = require('playwright');
const { execFileSync } = require('child_process');
const fs = require('fs');
const path = require('path');

const base = process.env.SIT_BASE_URL || 'http://127.0.0.1:8100';
const out = process.argv[2] || path.join(__dirname, 'results');
const root = path.resolve(__dirname, '../..');
fs.mkdirSync(out, { recursive: true });

const php = (code) => execFileSync('php', ['artisan', 'tinker', '--env=sit', `--execute=${code}`], { cwd: root }).toString().trim();
const fx = JSON.parse(php(`
$u = App\\Models\\User::where('email','sit.verified@easycar.test')->first();
$n = App\\Models\\User::where('email','sit.new@easycar.test')->first();
$b = fn ($car) => App\\Models\\Booking::whereHas('cars', fn ($q) => $q->where('model', $car))->latest('id')->first();
echo json_encode([
  'verifiedId' => $u->id,
  'pickup' => $b('Myvi')->only('id','reference'),
  'onTrip' => $b('Almera')->only('id','reference'),
  'done' => $b('City')->only('id','reference'),
  'pending' => $b('Exora')->only('id','reference'),
  'blocked' => $b('Bezza')->only('id','reference'),
  'cars' => App\\Models\\Car::pluck('id','model'),
  'points' => $u->pointsBalance(),
]);`));

const day = (n) => new Date(Date.now() + n * 864e5).toISOString().slice(0, 10);
const results = [];
const assert = (cond, msg) => { if (!cond) throw new Error(msg); };

(async () => {
  const browser = await chromium.launch();
  const ctxs = {};
  const jsErrors = [];
  async function as(role) {
    if (ctxs[role]) return ctxs[role];
    const ctx = await browser.newContext({ viewport: { width: 1366, height: 900 }, acceptDownloads: true });
    const page = await ctx.newPage();
    page.on('pageerror', (e) => jsErrors.push({ role, url: page.url(), msg: e.message }));
    page.on('console', (m) => { if (m.type() === 'error' && !/favicon|Failed to load resource/.test(m.text())) jsErrors.push({ role, url: page.url(), msg: m.text() }); });
    const emails = { customer: 'sit.verified@easycar.test', newcust: 'sit.new@easycar.test', staff: 'staff.bangi@easycar.com', admin: 'admin@easycar.com' };
    if (emails[role]) {
      await page.goto(`${base}/login`);
      await page.fill('input[name=email]', emails[role]);
      await page.fill('input[name=password]', 'password');
      await Promise.all([page.waitForLoadState('networkidle'), page.click('button:has-text("Sign in")')]);
    }
    return (ctxs[role] = page);
  }
  const settle = (p, ms = 900) => p.waitForTimeout(ms);
  const go = async (p, url) => { const r = await p.goto(base + url); await p.waitForLoadState('networkidle').catch(() => {}); return r; };
  const text = (p) => p.locator('body').innerText();

  async function tc(id, module, title, expected, role, fn) {
    const t0 = Date.now();
    const errBefore = jsErrors.length;
    let status = 'PASS', actual = '', page;
    try {
      page = await as(role);
      actual = (await fn(page)) || 'As expected.';
    } catch (e) {
      status = 'FAIL';
      actual = e.message.split('\n')[0].slice(0, 400);
    }
    const newErrors = jsErrors.slice(errBefore).map((e) => e.msg);
    if (status === 'PASS' && newErrors.length) { status = 'FAIL'; actual += ` | JS errors: ${newErrors.join('; ').slice(0, 300)}`; }
    if (page) await page.screenshot({ path: path.join(out, `${id}.jpg`), type: 'jpeg', quality: 60 }).catch(() => {});
    results.push({ id, module, title, expected, role, status, actual, ms: Date.now() - t0 });
    console.log(`${status === 'PASS' ? '✓' : '✗'} ${id} ${title}${status === 'PASS' ? '' : ' — ' + actual}`);
  }

  async function signature(p) {
    await p.locator('canvas[aria-label="Signature pad"]').scrollIntoViewIfNeeded();
    const box = await p.locator('canvas[aria-label="Signature pad"]').boundingBox();
    await p.mouse.move(box.x + 20, box.y + 60); await p.mouse.down();
    for (let i = 1; i <= 12; i++) await p.mouse.move(box.x + 20 + i * 18, box.y + 60 + (i % 2 ? -25 : 25), { steps: 3 });
    await p.mouse.up(); await settle(p, 500);
  }
  async function pickDates(p, from, to) {
    await p.fill('#startDate', from); await p.dispatchEvent('#startDate', 'change'); await settle(p, 700);
    await p.fill('#endDate', to); await p.dispatchEvent('#endDate', 'change'); await settle(p, 1200);
  }

  // ── M1 Storefront & catalogue ──────────────────────────────────────────
  const M1 = 'M1 Storefront & catalogue';
  await tc('SIT-01-01', M1, 'Landing page loads with hero, rows and Top 10', 'HTTP 200; hero billboard, ≥3 car rows incl. "Top 10 this month"', 'guest', async (p) => {
    const r = await go(p, '/'); assert(r.status() === 200, `HTTP ${r.status()}`);
    const rows = await p.locator('section[aria-label]').count();
    assert(rows >= 3, `only ${rows} rows`); assert((await text(p)).includes('Top 10'), 'no Top 10 row');
    return `${rows} rows rendered.`;
  });
  await tc('SIT-01-02', M1, 'Browse filter by type (SUV)', 'Only SUVs in the results grid', 'guest', async (p) => {
    await go(p, '/cars?type=SUV');
    const names = await p.locator('a[aria-label*="per day"]').evaluateAll((a) => a.map((x) => x.getAttribute('aria-label')));
    assert(names.length > 0, 'no results'); assert(names.every((n) => /X-Trail|CR-V/.test(n)), `non-SUV in results: ${names.join(', ')}`);
    return `${new Set(names).size} SUVs listed.`;
  });
  await tc('SIT-01-03', M1, 'Car page renders 3D showroom and availability calendar', 'WebGL canvas appears; 6-week availability grid shown', 'guest', async (p) => {
    await go(p, `/cars/${fx.cars['X-Trail']}`);
    await p.waitForSelector('canvas', { timeout: 15000 });
    assert((await text(p)).includes('Availability — next 6 weeks'), 'no availability calendar');
  });
  await tc('SIT-01-04', M1, 'Weekly/monthly rate badges and deposit note on car page', 'Badges "Weekly rate · 10% off 7+ days" and "Monthly…"; deposit amount stated', 'guest', async (p) => {
    await go(p, `/cars/${fx.cars['X-Trail']}`);
    const t = await text(p);
    assert(t.includes('Weekly rate · 10% off 7+ days') && t.includes('Monthly rate'), 'rate badges missing');
    assert(/Refundable deposit RM 300/.test(t), 'SUV deposit RM 300 not shown');
  });
  await tc('SIT-01-05', M1, 'Legal pages', '/terms and /privacy return 200 with content', 'guest', async (p) => {
    for (const u of ['/terms', '/privacy']) { const r = await go(p, u); assert(r.status() === 200, `${u} HTTP ${r.status()}`); }
  });
  await tc('SIT-01-06', M1, 'Mobile layout (390 px) has no horizontal scroll', 'Landing, browse and car pages fit the viewport', 'guest', async (p) => {
    await p.setViewportSize({ width: 390, height: 844 });
    const bad = [];
    for (const u of ['/', '/cars', `/cars/${fx.cars.Myvi}`]) {
      await go(p, u);
      if (await p.evaluate(() => document.documentElement.scrollWidth > window.innerWidth + 1)) bad.push(u);
    }
    await p.setViewportSize({ width: 1366, height: 900 });
    assert(!bad.length, `horizontal scroll on ${bad.join(', ')}`);
  });

  // ── M2 Accounts & access ───────────────────────────────────────────────
  const M2 = 'M2 Accounts & access';
  const newEmail = `sit.reg.${Date.now()}@easycar.test`;
  await tc('SIT-02-01', M2, 'Register a new customer account', 'Account created and signed in to the customer home', 'reg', async (p) => {
    await go(p, '/register');
    await p.fill('input[name=name]', 'Farah SIT Register');
    await p.fill('input[name=email]', newEmail);
    await p.fill('input[name=password]', 'password123'); await p.fill('input[name=password_confirmation]', 'password123');
    await Promise.all([p.waitForLoadState('networkidle'), p.click('button:has-text("Create account")')]); await settle(p);
    assert(/\/home|\/$/.test(new URL(p.url()).pathname), `landed on ${p.url()}`);
    assert((await text(p)).includes('Farah'), 'greeting missing');
  });
  await tc('SIT-02-02', M2, 'Wrong password is rejected', 'Stays on login with a credentials error', 'guest', async (p) => {
    await go(p, '/login');
    await p.fill('input[name=email]', 'sit.verified@easycar.test'); await p.fill('input[name=password]', 'wrong-password');
    await Promise.all([p.waitForLoadState('networkidle'), p.click('button:has-text("Sign in")')]);
    assert(p.url().includes('/login'), 'left the login page');
    assert(/do not match|credentials/i.test(await text(p)), 'no error message');
  });
  await tc('SIT-02-03', M2, 'Role-based landing (admin → console)', 'Admin lands on /admin/dashboard', 'admin', async (p) => {
    await go(p, '/home'); assert(p.url().includes('/admin/dashboard'), `admin at ${p.url()}`);
  });
  await tc('SIT-02-04', M2, 'Customer cannot open the admin console', 'HTTP 403 on /admin/dashboard and /admin/pricing', 'customer', async (p) => {
    for (const u of ['/admin/dashboard', '/admin/pricing']) { const r = await go(p, u); assert(r.status() === 403, `${u} HTTP ${r.status()}`); }
  });
  await tc('SIT-02-05', M2, 'Customer updates their profile', 'Success message and new name shown', 'reg', async (p) => {
    await go(p, '/profile');
    await p.fill('input[name=name]', 'Farah SIT Updated');
    await Promise.all([p.waitForLoadState('networkidle'), p.click('button:has-text("Save profile")')]);
    assert((await p.inputValue('input[name=name]')) === 'Farah SIT Updated', 'name not saved');
  });

  // ── M3 Driver verification ─────────────────────────────────────────────
  const M3 = 'M3 Driver verification (KYC)';
  const img = (n) => path.join(root, `public/images/cars/${n}.jpeg`);
  await tc('SIT-03-01', M3, 'Customer submits licence and IC for verification', 'Status "Under review" after submit', 'reg', async (p) => {
    await go(p, '/account/verification');
    await p.fill('#licence_number', 'D 7712345'); await p.fill('#licence_expiry', day(900));
    await p.fill('#id_number', '950101-14-5566'); await p.fill('#date_of_birth', '1995-01-01');
    await p.setInputFiles('#licence_front', img('almera')); await p.setInputFiles('#id_document', img('city'));
    await p.check('input[name=consent]');
    await Promise.all([p.waitForLoadState('networkidle'), p.click('button:has-text("Submit for verification")')]); await settle(p);
    assert(/under review|being reviewed|pending/i.test(await text(p)), 'not under review');
  });
  await tc('SIT-03-02', M3, 'Branch staff verifies the driver', 'Profile moves to Verified; customer sees Verified', 'staff', async (p) => {
    await go(p, '/ops/verifications');
    await p.locator('tr', { hasText: 'Farah SIT Updated' }).locator('a:has-text("Review")').click(); await p.waitForLoadState('networkidle');
    await Promise.all([p.waitForLoadState('networkidle'), p.click('button:has-text("Save decision")')]); await settle(p);
    const reg = await as('reg'); await go(reg, '/account/verification');
    assert(/verified/i.test(await text(reg)), 'customer not shown as verified');
  });

  // ── M4 Booking flow ────────────────────────────────────────────────────
  const M4 = 'M4 Booking flow';
  let newBookingUrl = null;
  await tc('SIT-04-01', M4, 'Minimum 2-day notice is enforced', 'Error "Bookings must start at least 2 days from today."', 'customer', async (p) => {
    await go(p, `/bookings/create?car=${fx.cars['X-Trail']}`);
    await pickDates(p, day(1), day(3));
    await p.check('input[wire\\:model="acceptTerms"]'); await p.click('button:has-text("Reserve")'); await settle(p, 1200);
    assert((await text(p)).includes('at least 2 days'), 'no notice error');
  });
  await tc('SIT-04-02', M4, 'Invalid promo code is rejected with a reason', 'Inline error under the promo field; total unchanged', 'customer', async (p) => {
    await go(p, `/bookings/create?car=${fx.cars['X-Trail']}`);
    await pickDates(p, day(30), day(32));
    await p.fill('#promo', 'NOTACODE'); await p.click('button:has-text("Apply")'); await settle(p);
    assert((await text(p)).includes("This promo code isn't valid."), 'no promo error');
  });
  await tc('SIT-04-03', M4, 'Terms of Rental must be accepted', 'Error "Please accept the Terms of Rental to continue."', 'customer', async (p) => {
    await p.click('button:has-text("Reserve")'); await settle(p, 1200);
    assert((await text(p)).includes('Please accept the Terms of Rental'), 'no terms error');
  });
  await tc('SIT-04-04', M4, 'Reserve with an extra and promo WEEKEND20', 'Booking created; page shows rental, CDW extra, RM 20 discount and deposit note', 'customer', async (p) => {
    await p.click('button[aria-label="Full protection (CDW)"]'); await settle(p);
    await p.fill('#promo', 'weekend20'); await p.click('button:has-text("Apply")'); await settle(p);
    await p.check('input[wire\\:model="acceptTerms"]'); await settle(p, 300);
    await p.click('button:has-text("Reserve")'); await p.waitForURL(/\/bookings\/\d+$/, { timeout: 15000 }); await settle(p);
    newBookingUrl = new URL(p.url()).pathname;
    const t = await text(p);
    assert(t.includes('Full protection (CDW)'), 'extra missing'); assert(/Discount \(WEEKEND20\)/.test(t), 'discount missing');
    assert(/Refundable deposit of RM 300\.00/.test(t), 'deposit note missing');
    return `Created ${newBookingUrl}.`;
  });
  await tc('SIT-04-05', M4, 'At most 2 cars per booking', 'Selecting a third car shows "You can select at most 2 cars."', 'customer', async (p) => {
    await go(p, '/bookings/create');
    await pickDates(p, day(60), day(62));
    const cards = p.locator('button[wire\\:click^="toggleCar"]');
    for (let i = 0; i < 3; i++) { await cards.nth(i).click(); await settle(p, 600); }
    assert((await text(p)).includes('at most 2 cars'), 'no limit error');
  });

  // ── M5 Payments & invoices ─────────────────────────────────────────────
  const M5 = 'M5 Payments & invoices';
  await tc('SIT-05-01', M5, 'Declined sandbox payment leaves booking unpaid', 'Payment failed message; booking still unpaid with Pay button', 'customer', async (p) => {
    await go(p, newBookingUrl);
    await p.click('button:has-text("Pay RM")'); await p.waitForURL(/sandbox/, { timeout: 15000 });
    await p.click('button:has-text("Decline")'); await settle(p, 2500);
    await go(p, newBookingUrl);
    const t = await text(p); assert(/Unpaid|Payment failed|failed/i.test(t), 'not shown as unpaid'); assert(t.includes('Pay RM'), 'no pay button');
  });
  await tc('SIT-05-02', M5, 'Successful sandbox payment marks booking paid and issues invoice', 'Paid badge; invoice INV-… listed', 'customer', async (p) => {
    await go(p, newBookingUrl);
    await p.click('button:has-text("Pay RM")'); await p.waitForURL(/sandbox/, { timeout: 15000 });
    await p.click('button:has-text("Approve payment")'); await settle(p, 3000);
    await go(p, newBookingUrl);
    const t = await text(p); assert(/\bPaid\b/.test(t), 'not paid');
    const inv = p.locator('a[href*="/invoices/"]', { hasText: 'Invoice' });
    assert((await inv.count()) >= 1, 'no invoice link');
    await inv.first().click(); await p.waitForLoadState('networkidle');
    const number = (await text(p)).match(/INV-\d{4}-\d{6}/);
    assert(number, 'invoice page has no INV number');
    return `Invoice ${number[0]}.`;
  });
  await tc('SIT-05-03', M5, 'Invoice PDF downloads', 'application/pdf response with %PDF header', 'customer', async (p) => {
    const href = await p.locator('a[href$="/pdf"]').first().getAttribute('href');
    const r = await p.request.get(href); const body = await r.body();
    assert(r.headers()['content-type'].includes('pdf'), `content-type ${r.headers()['content-type']}`);
    assert(body.slice(0, 4).toString() === '%PDF', 'not a PDF');
    return `${Math.round(body.length / 1024)} KB PDF.`;
  });
  await tc('SIT-05-04', M5, 'Cancelling a paid booking refunds it with a credit note', 'Booking cancelled; refund recorded; CN-… credit note', 'customer', async (p) => {
    await go(p, newBookingUrl);
    await p.click('button:has-text("Cancel booking")'); await settle(p, 500);
    await Promise.all([p.waitForLoadState('networkidle'), p.locator('.fixed button:has-text("Cancel booking"), [role=dialog] button:has-text("Cancel booking")').last().click()]); await settle(p, 1500);
    await go(p, newBookingUrl);
    const t = await text(p); assert(/cancelled/i.test(t), 'not cancelled'); assert(/Refunded/i.test(t), 'no refund shown');
    assert((await p.locator('a[href*="/invoices/"]', { hasText: 'Credit note' }).count()) >= 1, 'no credit note');
  });

  // ── M6 Approvals & admin bookings ──────────────────────────────────────
  const M6 = 'M6 Approvals & admin bookings';
  await tc('SIT-06-01', M6, 'Approval blocked for an unverified driver', 'Approve unavailable; reason about driver verification shown', 'admin', async (p) => {
    await go(p, `/admin/bookings/${fx.blocked.id}`);
    const t = await text(p); assert(/verif/i.test(t), 'no verification reason');
    assert((await p.locator('button:has-text("Approve")').count()) === 0, 'Approve button still offered');
  });
  await tc('SIT-06-02', M6, 'Admin approves a verified, paid pending booking', 'Status becomes Confirmed/Approved', 'admin', async (p) => {
    await go(p, `/admin/bookings/${fx.pending.id}`);
    await p.click('button:has-text("Approve")'); await settle(p, 500);
    await Promise.all([p.waitForLoadState('networkidle'), p.click('button:has-text("Approve booking")')]); await settle(p);
    assert(/approved|confirmed/i.test(await text(p)), 'not approved');
  });
  await tc('SIT-06-03', M6, 'Admin rejects a booking with a reason', 'Status Rejected with the reason shown', 'admin', async (p) => {
    await go(p, `/admin/bookings/${fx.blocked.id}`);
    await p.click('button:has-text("Reject")'); await settle(p, 500);
    await p.fill('#rejection_reason', 'SIT: driver documents not provided in time.');
    await Promise.all([p.waitForLoadState('networkidle'), p.click('button:has-text("Reject booking")')]); await settle(p);
    const t = await text(p); assert(/rejected|not approved/i.test(t) && t.includes('SIT: driver documents'), 'not rejected');
  });
  await tc('SIT-06-04', M6, 'Bookings CSV export', 'CSV file with header row and SIT references', 'admin', async (p) => {
    const r = await p.request.get(`${base}/admin/bookings/export`); const body = (await r.body()).toString();
    assert(r.status() === 200 && /csv/.test(r.headers()['content-type']), `HTTP ${r.status()} ${r.headers()['content-type']}`);
    assert(body.includes(fx.pending.reference), 'reference missing');
    return `${body.trim().split('\n').length - 1} rows.`;
  });

  // ── M7 Rental operations ───────────────────────────────────────────────
  const M7 = 'M7 Rental operations & deposits';
  await tc('SIT-07-01', M7, 'Operations board shows today’s pick-up and return', 'Bangi board lists the Myvi pick-up and the Almera return due', 'staff', async (p) => {
    await go(p, '/ops');
    const t = await text(p); assert(t.includes(fx.pickup.reference) && t.includes(fx.onTrip.reference), 'references missing on board');
  });
  await tc('SIT-07-02', M7, 'Pick-up requires the security deposit', 'Submitting without a deposit shows "Record how the security deposit was taken."', 'staff', async (p) => {
    await go(p, `/ops/bookings/${fx.pickup.id}/cars/${fx.cars.Myvi}/pickup`);
    await p.fill('#odometer', '9800'); await p.check('input[wire\\:model="licenceChecked"]');
    await p.fill('#signedByName', 'Nur Aisyah Verified'); await signature(p);
    await p.click('button:has-text("Hand over keys")'); await settle(p, 1500);
    assert((await text(p)).includes('Record how the security deposit was taken.'), 'deposit not required');
  });
  await tc('SIT-07-03', M7, 'Complete pick-up with card pre-auth deposit', 'Handover report opens; deposit Held on the booking', 'staff', async (p) => {
    await p.selectOption('#depositMethod', 'card_hold'); await p.fill('#depositReference', 'SIT-AUTH-778');
    await p.click('button:has-text("Hand over keys")'); await p.waitForURL(/\/inspections\/\d+/, { timeout: 15000 });
    await go(p, `/staff/bookings/${fx.pickup.id}`);
    const t = await text(p); assert(t.includes('Security deposit') && t.includes('Held') && t.includes('SIT-AUTH-778'), 'deposit not held');
  });
  await tc('SIT-07-04', M7, 'Return with fuel short creates a fuel charge', 'Check-in completes; RM 30.00 fuel charge (2/8 short)', 'staff', async (p) => {
    await go(p, `/ops/bookings/${fx.onTrip.id}/cars/${fx.cars.Almera}/return`);
    await p.fill('#odometer', '20350'); await p.click('button[aria-label="6/8"]'); await settle(p, 800);
    await p.fill('#signedByName', 'Nur Aisyah Verified'); await signature(p);
    await p.click('button:has-text("Complete check-in")'); await p.waitForURL(/\/inspections\/\d+/, { timeout: 15000 });
    await go(p, `/staff/bookings/${fx.onTrip.id}`);
    const t = await text(p); assert(/Fuel/i.test(t) && t.includes('RM 30.00'), 'fuel charge missing');
  });
  await tc('SIT-07-05', M7, 'Deposit settles the extra charges and the rest is released', 'Deposit "Part applied, rest released"; nothing outstanding', 'staff', async (p) => {
    p.once('dialog', (d) => d.accept());
    await Promise.all([p.waitForLoadState('networkidle'), p.click('button:has-text("Use for charges")')]); await settle(p);
    const t = await text(p); assert(t.includes('Part applied, rest released'), 'deposit not applied'); assert(!/RM [\d.]+ due/.test(t), 'still outstanding');
  });
  await tc('SIT-07-06', M7, 'Handover report privacy', 'Owner can open the report; another customer gets 403', 'customer', async (p) => {
    await go(p, `/bookings/${fx.onTrip.id}`);
    const href = await p.locator('a[href*="/inspections/"]').first().getAttribute('href');
    assert((await p.request.get(href)).status() === 200, 'owner cannot open');
    const other = await as('newcust'); assert((await other.request.get(href)).status() === 403, 'other customer not blocked');
  });

  // ── M8 Pricing & revenue ───────────────────────────────────────────────
  const M8 = 'M8 Pricing & revenue';
  await tc('SIT-08-01', M8, 'Admin creates a seasonal rule and sees it in the rate preview', 'Rule listed as Live; preview days carry the new rate', 'admin', async (p) => {
    await go(p, '/admin/pricing/create');
    await p.fill('input[name=name]', 'SIT Season'); await p.selectOption('select[name=kind]', 'season');
    await p.fill('input[name=starts_on]', day(20)); await p.fill('input[name=ends_on]', day(24)); await p.fill('input[name=adjustment_percent]', '15');
    await Promise.all([p.waitForLoadState('networkidle'), p.click('button:has-text("Create rule")')]);
    await go(p, `/admin/pricing?car=${fx.cars.Myvi}`);
    const t = await text(p); assert(t.includes('SIT Season') && t.includes('+15%'), 'rule not listed');
    assert((await p.locator('[title*="SIT Season"]').count()) >= 1, 'not in preview');
  });
  await tc('SIT-08-02', M8, 'Booking flow explains weekend and holiday pricing', 'Car line notes "Weekend +10%" / "Year-end holidays +20%"', 'customer', async (p) => {
    await go(p, `/bookings/create?car=${fx.cars['X-Trail']}`);
    await pickDates(p, day(30), day(32));
    const t = await text(p); assert(/Weekend \+10%|Year-end holidays \+20%/.test(t), 'no pricing notes');
  });
  await tc('SIT-08-03', M8, '7-day rental gets the weekly rate', 'Note "Weekly rate −10%" on the car line', 'customer', async (p) => {
    await go(p, `/bookings/create?car=${fx.cars['X-Trail']}`);
    await pickDates(p, day(40), day(46));
    assert((await text(p)).includes('Weekly rate −10%'), 'no weekly rate');
  });
  await tc('SIT-08-04', M8, 'Rewards page shows points from the completed trip', `Balance ${fx.points} pts with a "Trip completed" entry`, 'customer', async (p) => {
    await go(p, '/rewards');
    const t = await text(p); assert(t.includes('Trip completed'), 'no earn entry'); assert(t.includes(fx.points.toLocaleString('en')), 'balance mismatch');
  });
  await tc('SIT-08-05', M8, 'Using points lowers the booking total', 'Toggling "Use N Rewards points" adds a Rewards line and reduces the total', 'customer', async (p) => {
    await go(p, `/bookings/create?car=${fx.cars.Bezza}`);
    await pickDates(p, day(50), day(52));
    const before = await p.locator('aside .display').last().innerText();
    await p.check('input[wire\\:model\\.live="usePoints"]'); await settle(p, 1200);
    const after = await p.locator('aside .display').last().innerText();
    assert((await text(p)).includes('Rewards ('), 'no rewards line'); assert(before !== after, `total unchanged (${before})`);
    return `${before} → ${after}.`;
  });
  await tc('SIT-08-06', M8, 'Admin adjusts a customer’s points', 'Balance increases by 100 with the reason in history', 'admin', async (p) => {
    await go(p, `/admin/users/${fx.verifiedId}/edit`);
    const before = Number((await p.locator('#rewards').innerText()).match(/([\d,]+) points/)[1].replace(/,/g, ''));
    assert((await p.inputValue('#userLevel')) === '0', 'customer shown with a non-customer role (DEF-01)');
    await p.fill('#rewards input[name=points]', '100'); await p.fill('#rewards input[name=reason]', 'SIT goodwill credit');
    await Promise.all([p.waitForLoadState('networkidle'), p.click('#rewards button:has-text("Apply")')]);
    const t = await text(p); assert(t.includes('SIT goodwill credit') && t.includes(`${(before + 100).toLocaleString('en')} points`), `balance not ${before + 100}`);
    return `${before} → ${before + 100} points.`;
  });

  // ── M9 Customer experience ─────────────────────────────────────────────
  const M9 = 'M9 Customer experience';
  await tc('SIT-09-01', M9, 'Add a car to My List', 'Heart toggles on; car appears on /my-list', 'customer', async (p) => {
    await go(p, '/cars');
    const tile = p.locator('.group\\/tile').first(); await tile.hover(); await tile.locator('form button').click(); await settle(p);
    assert((await tile.locator('form button').getAttribute('aria-pressed')) === 'true', 'heart not pressed');
    await go(p, '/my-list'); assert((await p.locator('.group\\/tile').count()) >= 1, 'My List empty');
  });
  await tc('SIT-09-02', M9, 'Review a completed trip', 'Review posted and visible on the car page with rating', 'customer', async (p) => {
    await go(p, `/bookings/${fx.done.id}`);
    await p.locator('#reviews button[aria-label="5 stars"]').click();
    await p.fill('#reviews input[name=title]', 'SIT review: spotless car'); await p.fill('#reviews textarea[name=body]', 'Quick pick-up and a clean car.');
    await Promise.all([p.waitForLoadState('networkidle'), p.click('#reviews button:has-text("Post review")')]);
    await go(p, `/cars/${fx.cars.City}`);
    const t = await text(p); assert(t.includes('SIT review: spotless car') && t.includes('verified review'), 'review not on car page');
  });
  await tc('SIT-09-03', M9, 'Staff reply to a review appears publicly', 'Reply shown under the review on the car page', 'admin', async (p) => {
    await go(p, '/ops/reviews');
    const card = p.locator('article', { hasText: 'SIT review: spotless car' });
    await card.getByRole('button', { name: 'Reply', exact: true }).click(); await card.locator('textarea[name=reply]').fill('Thank you — see you on your next trip!');
    await Promise.all([p.waitForLoadState('networkidle'), card.locator('button:has-text("Post reply")').click()]);
    const g = await as('guest'); await go(g, `/cars/${fx.cars.City}`);
    assert((await text(g)).includes('see you on your next trip'), 'reply not public');
  });
  await tc('SIT-09-04', M9, 'Admin-created promo code works at checkout', 'SITPROMO (15% off) applies a discount line in the booking flow', 'admin', async (p) => {
    await go(p, '/admin/promo-codes/create');
    await p.fill('input[name=code]', 'sitpromo'); await p.selectOption('select[name=type]', 'percent'); await p.fill('input[name=value]', '15');
    await Promise.all([p.waitForLoadState('networkidle'), p.click('button:has-text("Create code")')]);
    const c = await as('customer'); await go(c, `/bookings/create?car=${fx.cars.Saga}`);
    await pickDates(c, day(70), day(72));
    await c.fill('#promo', 'SITPROMO'); await c.click('button:has-text("Apply")'); await settle(c);
    assert((await text(c)).includes('Discount (SITPROMO)'), 'promo not applied');
  });
  await tc('SIT-09-05', M9, 'Hiding an extra removes it from checkout', 'GPS navigator no longer offered after admin unticks "Offer at checkout"', 'admin', async (p) => {
    await go(p, '/admin/addons'); await p.locator('tr', { hasText: 'GPS navigator' }).locator('a:has-text("Edit")').click(); await p.waitForLoadState('networkidle');
    await p.uncheck('input[type=checkbox][name=is_active]');
    await Promise.all([p.waitForLoadState('networkidle'), p.click('button:has-text("Save changes")')]);
    const c = await as('customer'); await go(c, `/bookings/create?car=${fx.cars.Saga}`); await pickDates(c, day(70), day(72));
    assert(!(await text(c)).includes('GPS navigator'), 'GPS still offered');
  });

  // ── M10 Consoles, scoping & API ────────────────────────────────────────
  const M10 = 'M10 Consoles, scoping & API';
  await tc('SIT-10-01', M10, 'Admin dashboard KPIs and charts render', 'KPI cards and charts load without JavaScript errors', 'admin', async (p) => {
    await go(p, '/admin/dashboard'); await settle(p, 2500);
    assert((await p.locator('.apexcharts-canvas, .apexcharts-svg').count()) > 0, 'no charts rendered');
  });
  await tc('SIT-10-02', M10, 'Staff only see their own branch', 'Bangi staff list includes Bangi bookings but not the Gombak (City) booking', 'staff', async (p) => {
    await go(p, '/staff/bookings');
    const t = await text(p); assert(t.includes(fx.pickup.reference), 'own branch booking missing'); assert(!t.includes(fx.done.reference), 'other branch booking visible');
    const r = await go(p, `/staff/bookings/${fx.done.id}`); assert(r.status() === 403 || r.status() === 404, `other branch booking HTTP ${r.status()}`);
  });
  await tc('SIT-10-03', M10, 'REST API: token login and own bookings', 'POST /api/v1/auth/login returns a token; /api/v1/bookings lists only own bookings; no token → 401', 'guest', async (p) => {
    const login = await p.request.post(`${base}/api/v1/auth/login`, { data: { email: 'sit.verified@easycar.test', password: 'password' }, headers: { Accept: 'application/json' } });
    assert(login.ok(), `login HTTP ${login.status()}`);
    const token = (await login.json()).data.token;
    const list = await p.request.get(`${base}/api/v1/bookings`, { headers: { Accept: 'application/json', Authorization: `Bearer ${token}` } });
    const refs = (await list.json()).data.map((b) => b.reference);
    assert(refs.includes(fx.done.reference) && !refs.includes(fx.blocked.reference), `unexpected list ${refs.join(',')}`);
    const anon = await p.request.get(`${base}/api/v1/bookings`, { headers: { Accept: 'application/json' } });
    assert(anon.status() === 401, `anonymous HTTP ${anon.status()}`);
    return `${refs.length} bookings returned.`;
  });

  // ── M11 Notifications ──────────────────────────────────────────────────
  const M11 = 'M11 Notifications';
  await tc('SIT-11-01', M11, 'Transactional emails are sent', 'Mail log has booking received, payment received, refund, verification and handover emails', 'guest', async () => {
    const log = fs.readFileSync(path.join(root, 'storage/logs/laravel.log'), 'utf8');
    const unfolded = log.replace(/\r?\n[ \t]+/g, ' ');
    const decode = (s) => s.replace(/=\?utf-8\?Q\?([^?]*)\?=/gi, (_, q) => Buffer.from(q.replace(/_/g, ' ').replace(/=([0-9A-F]{2})/gi, (__, h) => String.fromCharCode(parseInt(h, 16))), 'latin1').toString('utf8'));
    const subjects = [...unfolded.matchAll(/^Subject: (.+)$/gm)].map((m) => decode(m[1]));
    const need = { 'booking received': /received — EasyCar/, 'payment received': /Payment received/, refund: /Refund issued/, verified: /verified to drive/, pickup: /picked up/, return: /returned/ };
    const missing = Object.entries(need).filter(([, re]) => !subjects.some((s) => re.test(s))).map(([k]) => k);
    assert(!missing.length, `missing: ${missing.join(', ')}`);
    return `${subjects.length} emails logged.`;
  });

  fs.writeFileSync(path.join(out, 'results.json'), JSON.stringify({ base, ranAt: new Date().toISOString(), fixtures: fx, results, jsErrors }, null, 1));
  const failed = results.filter((r) => r.status !== 'PASS').length;
  console.log(`\n${results.length - failed}/${results.length} passed`);
  await browser.close();
  process.exit(failed ? 1 : 0);
})();
