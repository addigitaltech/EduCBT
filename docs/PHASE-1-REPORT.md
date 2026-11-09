# Phase 1 report - Foundation

**Status: written, NOT tested.** No PHP or Composer was available where this code was written, so none of it has been run. Treat every item below as untested until the GitHub Actions jobs in `.github/workflows/ci.yml` have run and passed. The first run is expected to surface fixes (see "Known risks").

## Objective

A runnable Laravel application that boots as either profile (central or school), with secure sign-in, roles and permissions, audit logging, settings, a responsive shell that follows the UI/UX specification, health checks, and a CI pipeline.

## What was written, by increment

| # | Increment | State | Notes |
|---|---|---|---|
| 1.1 | Repository and tooling | Written, untested | Bootstrap script creates the framework files; Pest, Larastan, Pint, GitHub Actions (SQLite, MariaDB 10.6, MySQL 8, both profiles), `.env` examples per profile, README, install guide. |
| 1.2 | Profile system | Written, untested | `APP_PROFILE` enum, profile service provider (routes and migrations per profile), installation-record check on every web request, separate `shared`/`central`/`school` migration folders. |
| 1.3 | Foundation migrations | Written, untested | Part 1 tables: `system_identity`, `users` (+ `password_reset_tokens`, `sessions`), roles/permissions and pivots, `audit_logs`, `app_settings`. Cache and jobs tables come from the Laravel skeleton. Taxonomy tables (migration step 8) belong to Phase 2/3. |
| 1.4 | Authentication | Written, untested | Sign-in by sign-in ID, one generic failure message, per-account and per-address rate limits, timed account lockout, temporary-password forced change, password change, safe sign-out, session regeneration. |
| 1.5 | Access control | Written, untested | Permission registry in code, Gate-based checks on every route, built-in roles per profile, locked administrator role, custom roles, no handing out permissions you do not hold, last-administrator protection. |
| 1.6 | Audit | Written, untested | Append-only log with secret redaction, request id, actor snapshot; viewer with filters. Entries written explicitly by actions (sign-in, lockout, users, roles, settings, install). |
| 1.7 | Settings | Written, untested | Encrypted-capable settings service; security (attempt limit, lockout time, minimum password length) and appearance (display name, accent) pages. |
| 1.8 | UI shell | Written, untested, never viewed in a browser | Short sidebar (Dashboard, System health, Settings) with Settings as one workspace with tabs; drawer menu on phones; stacked tables on phones; Lucide icons with visible labels; self-hosted font; empty and error states; confirmations for destructive actions. |
| 1.9 | Health and operations | Written, untested | Public `/health`, System health page, scheduler heartbeat, cron-friendly queue draining, `php artisan app:install`. |

## Tests written (not yet run)

84 test functions (more once datasets expand): profile boot and isolation, sign-in and lockout, password rules, permission matrix across every settings and system route, user and role management rules, settings, audit immutability and redaction, health checks, installer, catalog consistency, and architecture rules (profile isolation, thin controllers, final actions).

## Not done in Phase 1 (decisions or later work)

- **Phone-width browser checks** (Playwright) for the shell and sign-in were in the Phase 1 plan but are **not written**. Proposal: add them at the first checkpoint, after Phase 3.
- **Self-service password reset by email** is not implemented; administrators reset passwords and issue a one-time temporary password. This needs mail settings that are not yet defined. Please confirm this is acceptable for now.
- Content-Security-Policy beyond `frame-ancestors`, the icon bundle trim, the audit retention pruning command, and a database trigger on audit logs are planned for Phase 9.
- No user import, no student or guardian accounts (Phase 2).

## Assumptions and known risks (please read)

- The bootstrap script assumes the current Laravel skeleton uses **Tailwind CSS 4**, Vite, and `AUTH_MODEL` in `config/auth.php`. It warns if Tailwind is not v4.
- Package names and versions (Pest, Larastan, Lucide, Alpine, fontsource Inter) were not checked against a live registry.
- Some Lucide icon names may not exist in the installed version. Missing icons render nothing and never hide meaning, because every icon has a visible text label.
- Some tests rely on framework behaviour that was reasoned about but not executed (for example reading flashed session data after a request). Expect a few test fixes on first run.
- The style check (Pint) and static analysis (Larastan, level 5) are set to **non-blocking** for this phase.

## How to verify

1. Push this branch. GitHub Actions runs the jobs automatically.
2. Locally: `bash scripts/bootstrap.sh`, then `APP_PROFILE=school ./vendor/bin/pest` and `APP_PROFILE=central ./vendor/bin/pest`.
3. Try it: `php artisan migrate && php artisan app:install && php artisan serve`.

## Next

Phase 2 (School foundation: sessions and terms, classes, subjects, staff, students, guardians, enrolments, imports) starts only after you approve this phase boundary.
