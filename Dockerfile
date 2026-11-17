# Etapa 1: Compilación de assets frontend con Node (no llega a la imagen final)
FROM node:22-alpine AS frontend-builder
WORKDIR /app
COPY package.json package-lock.json* ./
RUN if [ -f package-lock.json ]; then npm ci; else npm install; fi
COPY resources ./resources
COPY vite.config.js ./
COPY tailwind.config.js* postcss.config.js* ./
RUN npm run build

# Etapa 2: Imagen de producción PHP 8.3-FPM limpia y sin Node.js
FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
    icu-dev \
    libzip-dev \
    oniguruma-dev \
    linux-headers \
    $PHPIZE_DEPS \
    && docker-php-ext-install pdo_mysql mbstring zip intl bcmath opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del $PHPIZE_DEPS linux-headers

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-scripts --no-autoloader --prefer-dist

COPY . .
COPY --from=frontend-builder /app/public/build ./public/build

RUN cp -r /var/www/html/public/build /var/www/html/build-dist \
    && ([ -f /var/www/html/.env ] || cp /var/www/html/.env.example /var/www/html/.env) \
    && composer dump-autoload --optimize \
    && php artisan key:generate --force \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/public /var/www/html/.env

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

USER www-data

EXPOSE 9000
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]