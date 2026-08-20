# NOTE: paste this into your Forge site's deploy script — it does not run
# on its own. Replace the placeholder path below with your actual domain
# before use; the $CREATE_RELEASE()/$ADD_ENV()/$ACTIVATE_RELEASE()/
# $RESTART_QUEUES() macros are provided by Forge's zero-downtime deployment
# feature (TSD Section 6.5) and only exist in that environment.

cd /home/forge/easycar.example.com

$CREATE_RELEASE()
cd $NEW_RELEASE_PATH || exit 1

$ADD_ENV()

composer install --no-dev --optimize-autoloader --no-interaction

npm ci
npm run build

php artisan migrate --force

php artisan config:cache
php artisan route:cache
php artisan view:cache

$ACTIVATE_RELEASE()
$RESTART_QUEUES()

# Reload PHP-FPM so OPcache picks up the new release's code
( flock -w 10 9 || exit 1
    echo 'Restarting FPM...'; sudo -S service $FORGE_PHP_FPM reload ) 9>/tmp/fpmlock
