---
status: accepted
---

# Production runs on Laravel Cloud Serverless Postgres, not Cloud MySQL

On 27 November 2026 production moved from a Laravel Cloud MySQL 8.4 cluster to a Laravel Cloud Serverless Postgres 18 cluster. The reasons are the lower cost of a database that scales to zero, point-in-time restore (the MySQL cluster had only daily snapshots), and running the same setup as our other Cloud apps. The app has no raw SQL, and `config/database.php` already had a `pgsql` connection that reads the `DB_*` variables Cloud sets when a cluster is attached, so the move needed no code change.

## Consequences

- The tests and local development stay on SQLite, so nothing tests Postgres before production. `lockForUpdate()` row locks are only exercised in production, and they behave differently there: Postgres takes no gap lock, so two devices making the very first guess of a Game at the same moment can overwrite each other instead of one getting an error.
- After the database has been idle for 5 minutes it suspends, so the next request waits about a second while it wakes. Nothing polls the database (no queue worker, no scheduler), so it really does go idle.
- Postgres compares strings exactly, while MySQL's `utf8mb4_unicode_ci` ignored case, accents and trailing spaces. No lookup depends on this today, since accounts are matched by `workos_id` (see ADR 0003), but a new lookup on user-typed text has to normalise it itself.
- Postgres has no unsigned integers, so `games.started_at` and `finished_at` overflow in January 2038 instead of 2106. Widen them before then.
- The data was copied once during a 3-minute maintenance window. Production sessions live in cookies, not the database, so nobody was logged out and guests kept their Game.
- For 7 days after the move the MySQL cluster is kept. Rolling back means copying users, games and answers back from Postgres before re-attaching MySQL; dropping the Postgres rows instead would pick a new answer for any day already played on Postgres.
