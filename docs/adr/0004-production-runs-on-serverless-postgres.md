---
status: accepted
---

# Production runs on Laravel Cloud Serverless Postgres, not Cloud MySQL

Production moved in November 2026 from a Laravel Cloud MySQL 8.4 cluster to a Laravel Cloud Serverless Postgres 18 cluster. The reasons were the lower cost of a database that scales to zero, point-in-time restore (the MySQL cluster had only daily snapshots), and running the same setup as our other Cloud apps. The app has no raw SQL, so the move needed no code change: Cloud sets the `DB_*` variables when the cluster is attached, and `config/database.php` already had the `pgsql` connection.

## Consequences

- The tests and local development stay on SQLite, so nothing tests Postgres before production. `lockForUpdate()` row locks and anything else specific to Postgres are only exercised in production.
- After the database has been idle for 5 minutes it suspends, so the next request waits about a second while it wakes.
- Postgres compares strings case-sensitively, unlike MySQL's default collation. No lookup depends on this today, since accounts are matched by `workos_id` (see ADR 0003), but a new lookup on user-typed text has to handle case itself.
- The data was copied once during a maintenance window, which ended every session.
