# Production image: FrankenPHP (Caddy + PHP) serving public/ on :8080.
# The same image runs the web app, the queue worker and the scheduler (compose.prod.yaml).
FROM dunglas/frankenphp:1.13-php8.5 AS base

# imagick + ghostscript: OCR renders uploaded PDF pages (GradeController::readScan).
RUN apt-get update \
    && apt-get install -y --no-install-recommends ghostscript \
    && rm -rf /var/lib/apt/lists/* \
    && install-php-extensions pdo_pgsql intl zip pcntl opcache imagick \
    && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

WORKDIR /app

# Build stage needs PHP and Node together: the wayfinder Vite plugin runs artisan during `pnpm build`.
FROM base AS build

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN curl -fsSL https://deb.nodesource.com/setup_24.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && npm install -g corepack \
    && corepack enable

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

COPY package.json pnpm-lock.yaml pnpm-workspace.yaml .npmrc ./
RUN pnpm install --frozen-lockfile

COPY . .
RUN composer dump-autoload --optimize --no-dev \
    && pnpm build \
    && rm -rf node_modules

FROM base

ENV SERVER_NAME=:8080 \
    APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    XDG_CACHE_HOME=/tmp/cache

COPY --from=build --chown=www-data:www-data /app /app

RUN chown -R www-data:www-data /config/caddy /data/caddy

# Boot steps from the README's "Start from zero", run on every container start.
# The web container (default CMD) migrates and seeds; worker/scheduler (any other command) only cache config.
# Every seeder is idempotent, and DatabaseSeeder adds the demo accounts and grades only when APP_ENV=local.
COPY docker/entrypoint.sh /usr/local/bin/app-entrypoint

ENTRYPOINT ["app-entrypoint"]
# ENTRYPOINT resets the base image CMD, so restate it.
CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile", "--adapter", "caddyfile"]

USER www-data

EXPOSE 8080

HEALTHCHECK --interval=10s --timeout=3s --start-period=30s --retries=5 \
    CMD curl -fsS http://localhost:8080/up || exit 1
