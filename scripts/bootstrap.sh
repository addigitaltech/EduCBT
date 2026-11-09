#!/usr/bin/env bash
#
# EduCBT bootstrap
# ----------------
# This repository contains only the project's own source files (app/, routes/,
# database/, resources/, tests/ ...). The standard Laravel framework files are NOT
# committed. This script creates them from the official Laravel skeleton, without
# overwriting anything that is already in the repository, then installs the
# dependencies and (optionally) builds the assets and runs the tests.
#
# Usage:
#   bash scripts/bootstrap.sh                # full setup, build assets, run tests
#   bash scripts/bootstrap.sh --no-build     # skip the front-end build
#   bash scripts/bootstrap.sh --no-tests     # skip the test run
#   bash scripts/bootstrap.sh --ci           # non-interactive (used by GitHub Actions)
#
# Requirements: PHP 8.3+ (with the sodium extension), Composer 2, Node 20+ and npm.

set -euo pipefail

BUILD=1
TESTS=1
CI=0
for arg in "$@"; do
  case "$arg" in
    --no-build) BUILD=0 ;;
    --no-tests) TESTS=0 ;;
    --ci) CI=1 ;;
    -h|--help) sed -n '2,20p' "$0"; exit 0 ;;
    *) echo "Unknown option: $arg" >&2; exit 2 ;;
  esac
done

cd "$(dirname "$0")/.."

say() { printf '\n==> %s\n' "$*"; }
need() { command -v "$1" >/dev/null 2>&1 || { echo "Missing required tool: $1" >&2; exit 1; }; }

need php
need composer
need node
need npm

php -r 'exit(version_compare(PHP_VERSION, "8.3.0", ">=") ? 0 : 1);' || { echo "PHP 8.3 or newer is required." >&2; exit 1; }
php -m | grep -qi '^sodium$' || echo "WARNING: the PHP sodium extension is missing. It is needed from Phase 6 (signed licences and updates)." >&2

# 1. Framework skeleton (only when it has not been created yet) -------------------
if [ ! -f artisan ]; then
  say "Creating the Laravel skeleton (existing project files are kept)"
  TMP="$(mktemp -d)"
  composer create-project laravel/laravel "$TMP/skeleton" --prefer-dist --no-interaction --no-scripts
  if command -v rsync >/dev/null 2>&1; then
    rsync -a --ignore-existing "$TMP/skeleton/" ./
  else
    cp -a -n "$TMP/skeleton/." ./ || true
  fi
  rm -rf "$TMP"

  # Remove skeleton files that this project replaces.
  rm -f database/migrations/0001_01_01_000000_create_users_table.php \
        app/Models/User.php \
        tests/Feature/ExampleTest.php tests/Unit/ExampleTest.php \
        routes/web.php resources/views/welcome.blade.php
  rmdir app/Models 2>/dev/null || true
fi

# 2. PHP dependencies -------------------------------------------------------------
say "Installing PHP dependencies"
composer config --no-plugins allow-plugins.pestphp/pest-plugin true
composer config --no-plugins allow-plugins.php-http/discovery true
composer install --no-interaction --prefer-dist
composer require --dev pestphp/pest pestphp/pest-plugin-laravel larastan/larastan \
  --with-all-dependencies --no-interaction

# 3. Environment ------------------------------------------------------------------
if [ ! -f .env ]; then
  say "Creating .env from .env.example (school profile). For central, copy .env.central.example instead."
  cp .env.example .env
fi
php artisan key:generate --force --no-interaction
mkdir -p database storage/logs storage/framework/{cache,sessions,views} bootstrap/cache
[ -f database/database.sqlite ] || touch database/database.sqlite

# 4. Front-end --------------------------------------------------------------------
say "Installing front-end dependencies"
npm install --no-audit --no-fund
npm install --save --no-audit --no-fund lucide alpinejs @fontsource-variable/inter

node -e '
  const p = require("./package.json");
  const v = (p.devDependencies && p.devDependencies.tailwindcss) || (p.dependencies && p.dependencies.tailwindcss) || "";
  if (!/(^|[^0-9])4/.test(v)) {
    console.error("WARNING: Tailwind CSS 4 is expected (found \"" + v + "\"). The stylesheet uses the v4 import syntax.");
  }
'

if [ "$BUILD" -eq 1 ]; then
  say "Building production assets"
  npm run build
fi

# 5. Tests ------------------------------------------------------------------------
if [ "$TESTS" -eq 1 ]; then
  say "Running the test suite"
  ./vendor/bin/pest
fi

say "Done."
if [ "$CI" -eq 0 ]; then
  cat <<'MSG'

Next steps on a real installation:
  php artisan migrate
  php artisan app:install
See docs/INSTALL.md for cron/worker setup and production settings.
MSG
fi
