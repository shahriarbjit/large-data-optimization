# syntax=docker/dockerfile:1
#
# Targets:
#   app  runtime image (PHP-FPM, no dev packages) used by compose.yaml
#   dev  same PHP with dev packages, for `composer ci` in Docker (compose profile "test")

########## base: PHP 8.4 FPM with the extensions the app needs ##########
FROM php:8.4-fpm-alpine AS base

RUN apk add --no-cache icu-libs libzip \
 && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS icu-dev libzip-dev \
 && docker-php-ext-install -j"$(nproc)" pdo_mysql intl zip bcmath opcache \
 && apk del .build-deps

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/php/php.ini "$PHP_INI_DIR/conf.d/zz-app.ini"

WORKDIR /var/www/html

########## app: runtime image ##########
FROM base AS app

# Dependencies first, so code changes do not reinstall them.
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --no-progress

COPY . .
RUN composer dump-autoload --no-dev --optimize --no-interaction \
 && mkdir -p storage/framework/cache/data storage/framework/views storage/framework/sessions storage/logs storage/app \
 && chown -R www-data:www-data storage bootstrap/cache

COPY --chmod=755 docker/php/entrypoint.sh /usr/local/bin/app-entrypoint

ENTRYPOINT ["app-entrypoint"]
CMD ["php-fpm"]

########## dev: with dev packages (Pest, Larastan, Deptrac, Pint) ##########
FROM base AS dev

COPY composer.json composer.lock ./
RUN composer install --no-scripts --no-autoloader --prefer-dist --no-interaction --no-progress

COPY . .
# .env from the example: some test tooling reads the file. Real values come
# from compose.yaml and phpunit.xml (environment variables win over .env).
RUN composer dump-autoload --no-interaction \
 && cp .env.example .env \
 && mkdir -p storage/framework/cache/data storage/framework/views storage/framework/sessions storage/logs storage/app

CMD ["composer", "ci"]
