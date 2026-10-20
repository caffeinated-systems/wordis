<p align="center"><img src="public/assets/wordis-logo.svg" alt="Wordis" width="320"></p>

# Wordis

[![Tests](https://github.com/caffeinated-systems/wordis/actions/workflows/tests.yml/badge.svg)](https://github.com/caffeinated-systems/wordis/actions/workflows/tests.yml)
![Coverage ≥ 95%](https://img.shields.io/badge/coverage-%E2%89%A595%25-brightgreen)
![Type coverage ≥ 95%](https://img.shields.io/badge/type%20coverage-%E2%89%A595%25-brightgreen)
![PHPStan level max](https://img.shields.io/badge/PHPStan-level%20max-brightgreen)

A Wordle clone. You get five guesses to find a five-letter word, and the game taunts you every time you miss.

## How to play

Type a guess on your keyboard or the on-screen one, then press Enter. Each letter in the guess is marked:

- **Correct** (green): the letter is in the answer, in this position.
- **Present** (amber): the letter is in the answer, but somewhere else.
- **Absent**: the letter isn't in the answer, or every copy of it is already accounted for. Guessing `GEESE` against `TIGER` marks one `E` present and the other two absent.

The on-screen keyboard shows the best status you've found for each letter. Guesses must be real words from the dictionary; anything else is rejected. Find the answer within five guesses to win.

## Stack

- [Laravel 13](https://laravel.com) serves the page.
- [Alpine.js](https://alpinejs.dev) runs the game, written in TypeScript under `resources/js/`.
- [Vite](https://vite.dev) builds the assets.
- [Pest](https://pestphp.com) runs the feature and browser tests.

## Local setup

```shell
composer setup                 # install deps, create .env, migrate, build assets
php artisan boost:install      # optional: AI agent guidelines, skills and MCP config
composer dev                   # or: php artisan serve + npm run dev
```

Requires PHP 8.4+ and Node 20.19+ (Node 24 recommended).

Laravel Boost's generated files (`boost.json`, `.mcp.json`, `.claude/skills/*`) are gitignored. Run `php artisan boost:install` to create them, and `php artisan boost:update` to refresh them. `AGENTS.md` is tracked: its Workflow section is hand-written, and `boost:update` only rewrites the `<laravel-boost-guidelines>` block below it. `CLAUDE.md` imports it.

## Testing

```shell
npx playwright install chromium   # once, for the browser tests
npm run build                     # the tests use the built assets
php artisan test                  # feature and browser tests
npm run typecheck                 # TypeScript type check
```

The browser tests in `tests/Browser` play the game in a real Chromium against the answer `tiger`. CI runs the type check, the build and the full test suite on every pull request.

## Project docs

- [`GLOSSARY.md`](GLOSSARY.md): the game's vocabulary (answer, guess, dictionary).
- [`docs/adr/`](docs/adr/): architecture decision records.

## License

[MIT](LICENSE).
