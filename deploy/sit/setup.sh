#!/usr/bin/env bash
# Builds (or rebuilds) the SIT environment from scratch:
# fresh database, demo data + SIT scenarios, assets.
#   deploy/sit/setup.sh            then   php artisan serve --env=sit --port=8100
set -euo pipefail
cd "$(dirname "$0")/../.."

[ -f .env.sit ] || { cp deploy/sit/env.sit.example .env.sit; php artisan key:generate --env=sit --force; }
: > database/sit.sqlite
php artisan config:clear
php artisan migrate:fresh --seed --force --env=sit
php artisan db:seed --class='Database\Seeders\SitScenarioSeeder' --force --env=sit
php artisan storage:link >/dev/null 2>&1 || true
[ -f public/build/manifest.json ] || npm run build
echo "SIT ready. Start it with: php artisan serve --env=sit --port=8100"
