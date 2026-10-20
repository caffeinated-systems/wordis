---
status: accepted
---

# Players sign in through WorkOS AuthKit, not a login we build

Signing in is optional and exists so a player's games are kept. We use WorkOS AuthKit through Laravel's `laravel/workos` package instead of building email and password login with Fortify. AuthKit sends its own password-reset, verification and magic-link emails, so signing in doesn't wait on us setting up mail. Its free tier covers up to a million users, and adding a method like Google is a dashboard switch, not code.

## Consequences

- Signing in and registering happen on WorkOS's hosted page, and nobody can sign in while WorkOS is down. Guests can still play.
- Users are matched by `workos_id`, and we store no passwords. Moving off WorkOS later means migrating every account.
- Each signed-in request checks the WorkOS session token, refreshing it with WorkOS when it expires.
