# EduCBT

A multi-school CBT, examination and academic-results platform for Nigerian schools.

One shared Laravel codebase runs in two profiles:

- **Central platform** - schools, question bank, subscriptions, releases and updates (cloud or shared hosting).
- **School installation** - students, classes, examinations and results on the school's own server, working with or without internet.

Each deployment has its own environment, database and storage, and is installed as exactly one profile.

## Status

| Phase | Scope | State |
|---|---|---|
| 0 | Discovery, data design, plans | Done (see `docs/design/`) |
| 1 | Foundation: profiles, sign-in, roles and permissions, audit, settings, responsive shell, health checks | **Written, not yet tested** (see `docs/PHASE-1-REPORT.md`) |
| 2 - 9 | School foundation through hardening | Planned |

Nothing in this repository is described as working, secure or tested until the CI jobs have run and passed. See `docs/PROCESS.md`.

## Getting started

```bash
bash scripts/bootstrap.sh        # creates the framework files, installs dependencies, builds assets, runs tests
php artisan migrate
php artisan app:install          # sets up this installation and creates the first administrator
php artisan serve
```

Choose the profile with `APP_PROFILE=school` or `APP_PROFILE=central` in `.env` before installing. A deployment cannot change profile afterwards.

See `docs/INSTALL.md` for production setup (cron, queue worker, HTTPS).

## Repository layout

- `app/Domain/` - business logic (Identity, Access, Audit, Settings, System). No web or profile code.
- `app/Profiles/` - code that exists in only one profile (`Central`, `School`).
- `app/Http/` - thin controllers, requests and middleware.
- `database/migrations/{shared,central,school}` - each profile loads `shared` plus its own folder.
- `docs/design/` - approved design documents; `docs/handover/` - the original handover package.
- `tests/` - Pest tests (Feature, Unit, Architecture). Run the suite once per profile with `APP_PROFILE=school` and `APP_PROFILE=central`.

## Contributing

Never commit secrets, production credentials or real student data. Work on a branch and open a pull request.
