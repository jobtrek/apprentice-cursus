#!/bin/sh
set -e

# README step 4 runs key:generate; in a container the key must come from the env (sessions die if it changes).
if [ -z "$APP_KEY" ]; then
    echo "APP_KEY is empty: run 'php artisan key:generate --show' and put the value in the stack's env." >&2
    exit 1
fi

case "$1" in
    -*|frankenphp)
        php artisan migrate --force --seed
        # README "Microsoft Entra SSO and account sync"; an Entra outage must not keep the app down.
        php artisan azure:sync || echo "azure:sync failed, the app starts without it (the daily schedule retries)." >&2
        ;;
esac

php artisan optimize
exec docker-php-entrypoint "$@"
