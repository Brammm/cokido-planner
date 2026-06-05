FROM composer:latest AS composer

FROM php:8.5-fpm-alpine AS base

RUN apk add --no-cache libzip-dev postgresql-dev \
    && docker-php-ext-install pdo pdo_pgsql zip

WORKDIR /var/www/app

EXPOSE 9000

CMD ["php-fpm"]

FROM base AS development

COPY --from=ghcr.io/php/pie:bin /pie /usr/bin/pie

RUN apk add --no-cache sqlite-dev && docker-php-ext-install pdo_sqlite

RUN apk add linux-headers $PHPIZE_DEPS && \
 pie install xdebug/xdebug:3.5.1 && \
 docker-php-ext-enable xdebug

COPY --from=composer /usr/bin/composer /usr/bin/composer

COPY .docker/php/99-development.ini $PHP_INI_DIR/conf.d/

CMD [ "php", "-S", "0.0.0.0:80", "-t", "public/" ]

#
## Production stage - no composer, files baked in
#FROM base AS production
#
#COPY .docker/php/99-production.ini /usr/local/etc/php/conf.d/
#
#COPY ./ /var/www/app
#
#RUN chown -R www-data:www-data /var/www/app/cache
