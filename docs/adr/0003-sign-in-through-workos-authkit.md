---
status: accepted
---

# Players sign in through WorkOS AuthKit, not a login we build

Signing in is optional and exists so a player's games are kept on an account. We use WorkOS AuthKit through Laravel's `laravel/workos` package instead of building email and password login with Fortify. AuthKit sends its own password-reset, verification and magic-link emails, so signing in doesn't wait on us setting up mail. Its free tier covers the first million monthly active users, and adding a method like Google is a dashboard setting, not code.

## Consequences

- Signing in and registering happen on WorkOS's hosted page.
- While WorkOS is down, nobody can sign in, and a signed-in player whose short-lived access token expires is signed out on their next request through the WorkOS session middleware: the package invalidates the whole session when the refresh fails. Guests keep playing, so an account's game must be saved before the session is the only place it lives.
- How long a player stays signed in is set in two places: Laravel's `SESSION_LIFETIME` and the session length in the WorkOS dashboard. Both need raising to keep players signed in for 30 days.
- Accounts are matched by `workos_id`, and we store no passwords. Moving off WorkOS later means migrating every account.
