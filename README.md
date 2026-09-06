# SplashPoint — PHP + MySQL Swimming Pool Booking System

Full rebuild of the front-end prototype into a real PHP + PDO + MySQL
application: member accounts, admin dashboard with CRUD, and sessions
that automatically refresh to full capacity every day.

## How the "daily auto-refresh" works

Instead of storing one `capacity` number that has to be manually reset,
the system separates two concepts:

- **`pool_schedules`** — the *recurring* daily time slots for a pool
  (e.g. "Deep Blue is open 07:00–12:00 and 14:00–19:00 every day").
  Managed by admins under **Schedules**.
- **`sessions`** — one row per pool + specific date + time slot. This is
  what people actually book against.

`includes/session_generator.php` runs at the top of `index.php` and
`admin/dashboard.php`. Every time it runs, it makes sure today's and
tomorrow's `sessions` rows exist for every active schedule — if a date
doesn't have a row yet, it creates one with `booked = 0` and the pool's
full capacity. So every new day starts completely open automatically.
**Nothing ever "resets"** — the only thing that changes `booked` is a
real row appearing in `bookings`.

## Login rules

- Browsing pools and sessions on the homepage: **no login required**.
- Clicking a session to book it: redirected to `member/login.php` (or
  `register.php`) if not logged in, then bounced straight back to
  finish the booking.
- Admin dashboard: completely separate login (`admins` table, own
  session key), so staff and customer accounts never mix.

## WAMPP setup

1. Copy the `pool-booking-php` folder into `C:/wamp64/www/`.
2. Open **phpMyAdmin** → Import → select `database/schema.sql`. This
   creates the `pool_booking` database, all tables, and seeds the 3
   pools + their recurring schedules (no sessions or accounts yet —
   those are created automatically / by you).
3. Open `config/db.php` — WAMPP defaults (`localhost`, `root`, no
   password) are already set, so you usually don't need to touch this.
4. Open `config/config.php` and set `BASE_URL` to match your folder
   name, e.g. `define('BASE_URL', '/pool-booking-php');` if you're
   opening the site at `http://localhost/pool-booking-php/`.
5. Visit `http://localhost/pool-booking-php/admin/setup_admin.php`
   **once** to create your admin account, then delete that file.
6. Visit `http://localhost/pool-booking-php/` — pools and sessions
   should appear (auto-generated for today/tomorrow on this first
   visit). Register a member account and try booking a session.
7. Log in at `admin/login.php` with the Admin ID + password you just
   created to see the dashboard, manage pools/schedules, and view all
   bookings.

## What's implemented (per your list)

1. **Admin login (by Admin ID) + full pool CRUD** — `admin/pools.php`
   (create/edit/deactivate pools), `admin/schedules.php` (add/pause/
   delete recurring time slots), `admin/bookings.php` (view every
   booking, filter by date/status, cancel any booking),
   `admin/dashboard.php` (today's stats + snapshot).
2. **Member register/login + profile** — `member/register.php`,
   `member/login.php`, `member/logout.php`, `member/profile.php`
   (edit name/phone, see your own booking history, cancel your own
   bookings). Browsing stays public; only booking requires login.
3. **Live, real data** — session lists on the homepage and gauges
   are queried fresh from MySQL on every page load, so as soon as a
   real booking happens, everyone sees the updated availability
   immediately. Daily capacity "refresh" is handled by the session
   generator described above — it's real, not a display trick.
4. See **"Suggested next steps"** below for extra ideas tied to your
   assignment rubric.

## Suggested next steps (not built yet — your call)

These aren't implemented, but are natural additions that would
strengthen the assignment report, roughly in order of effort:

- **CSRF tokens** on all POST forms (login, booking, admin actions) —
  a straightforward security add markers usually expect to see.
- **Booking cutoff / cancellation window** (e.g. can't cancel within
  1 hour of the session) — shows business-logic thinking.
- **Admin CSV export** of bookings for a date range — nice for the
  "reporting" angle in your write-up, and easy to build with PHP's
  `fputcsv()`.
- **Rate limiting on login** (lock out after N failed attempts) —
  pairs well with the "Secure" rubric criterion.
- **Email confirmation** via Amazon SES once you're on AWS — sends a
  real email with the booking reference instead of just showing it
  on screen.
- **Password reset flow** for members ("forgot password" — currently
  there isn't one).
- **Search/filter by date** on the homepage once you have more than
  2 days of sessions generated.
- **Session capacity edits per date** (e.g. admin reduces one specific
  day's capacity for maintenance) — currently capacity is inherited
  from the pool/schedule, not editable per individual session.

## Deploying to AWS later

Same idea as before — only `config/db.php` needs to change (point
`DB_HOST`/`DB_USER`/`DB_PASS` at your RDS instance), plus re-import
`database/schema.sql` into RDS and run `admin/setup_admin.php` once
there too. `config/config.php`'s `BASE_URL` may also need adjusting
depending on how you deploy (root vs. subfolder).
