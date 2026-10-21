---
status: accepted
---

# Players sign in through WorkOS AuthKit, not a login we build

Signing in is optional and exists so a player's games are kept on an account. We use WorkOS AuthKit through Laravel's `laravel/workos` package instead of building email and password login with Fortify. AuthKit sends its own password-reset, verification and magic-link emails, so signing in doesn't wait on us setting up mail. Its free tier covers the first million monthly active users, and adding a method like Google is a dashboard setting, not code.

## Consequences

- Signing in and registering happen on WorkOS's hosted page.
- Whenever a WorkOS refresh fails (an outage, a revoked session, or the session reaching its maximum length), the package's session middleware signs the player out, wipes the whole session and redirects to `/`. Guests keep playing. An account's game must never live only in the session, and a JSON route behind that middleware gets a redirect instead of JSON.
- How long a player stays signed in is set in two places: Laravel's `SESSION_LIFETIME` and the session length in the WorkOS dashboard. Both are set to 30 days.
- Accounts are matched by `workos_id`, and we store no passwords. Moving off WorkOS later means migrating every account.
- Email is not unique and never matches accounts: a player who recreates their WorkOS account gets a new account here, and their old Games stay on the old one.
