# AGENTS.md

Laravel 12 app: **BHCIS System Sta. Ana** — Barangay Health Center Information System (maternal tracking, child & adult immunization, consultations & referrals, other barangay services; capstone, DOH-aligned but not affiliated with DOH). PHP 8.2+, Blade + Tailwind v4, session-based auth (custom `AuthController`, no Breeze/Jetstream/Filament).

## Commands

- `composer run dev` — runs `php artisan serve`, `queue:listen`, `pail`, and `npm run dev` together via concurrently
- `composer run test` — `config:clear` + `php artisan test` (PHPUnit; do NOT use Pest, convert any Pest tests)
- Single test: `php artisan test --compact --filter=testName`
- `vendor/bin/pint --dirty` before finalizing any PHP changes (never use `--test`)
- `composer run phpstan` (or `vendor/bin/phpstan analyse --no-progress --memory-limit=1G`) once as a final verification gate after PHP changes — same pattern as running tests; do NOT iterate on it interactively
- `npm run build` — required after blade/js changes; ViteException → run this or `composer run dev`
- `composer setup` — full fresh setup (composer install, .env, key, migrate, npm build)

## Architecture notes

- Validation lives in `app/Http/Requests/` Form Request classes, not inline in controllers
- Global helpers autoloaded from `app/Helpers/helpers.php` (`user()`) and `app/Helpers/BreadcrumbHelper.php`
- PDFs go through `app/Services/PdfService.php` (spatie/laravel-pdf + browsershot/puppeteer — needs node_modules installed)
- ICD diagnosis lookup: remote WHO ICD API via `BHCIS_ICD_API_*` env vars; falls back to local `diagnosis_lookup` table when disabled
- Roles/permissions are data-driven (`Permission` model + `hasPermission()` helper); always permission-gate nav items and actions

## Frontend conventions

- Read `.opencode/skills/bhccr-ui-style/SKILL.md` before touching any view — it is the authoritative style guide
- Use CSS variables from `resources/views/layouts/app.blade.php` (`var(--primary)` DOH green `#0d4a3c`, `--ink`, etc.); never hardcode brand colors
- Poppins font; never Inter/Roboto/Arial
- SweetAlert2 (`Swal.fire`) for confirmations/errors, not native dialogs
- Two distinct UI modes: interactive screens (`@extends('layouts.app')`) vs. DOH print/PDF forms (black 1px borders, fixed grids — never app-shell tokens)

## On-demand skills

- Load `security-review` skill before auth/input/API/payment/sensitive-feature changes; `coding-standards` and `tdd-workflow` for new code. These are invoked on demand, not always-loaded.

## Docs

- `docs/AGENTS.md` — Laravel Boost guidelines (uses `artisan boost:mcp` MCP server per `.mcp.json`)
- `docs/database_and_routes.md` — DB schema + routes inventory
- `docs/security/` — security audit and hardening notes; `docs/CLAUDE.md`, `docs/REFACTOR_*` for related guidance

## Tooling

- `cloudflared` (Cloudflare Tunnel) is installed at `~/.local/bin/cloudflared` and on PATH. If missing/corrupt, reinstall: `curl -sL https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-amd64 -o ~/.local/bin/cloudflared && chmod +x ~/.local/bin/cloudflared`. Verify with `cloudflared --version` (downloads smaller than ~40MB are truncated - re-download).

Tests use in-memory sqlite (`phpunit.xml`). Don't create docs files unless asked.
