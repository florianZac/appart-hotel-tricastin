# ============================================================
# Stage 1 – Build des assets JS/CSS (Webpack Encore)
# ============================================================
FROM node:20-alpine AS assets

WORKDIR /app

COPY package.json package-lock.json* ./
# Si pas de package-lock, on accepte yarn.lock aussi
RUN npm ci --ignore-scripts 2>/dev/null || npm install --ignore-scripts

COPY webpack.config.js ./
COPY assets/ ./assets/

RUN npm run build

# ============================================================
# Stage 2 – Installation des dépendances PHP (Composer)
# ============================================================
FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-scripts \
    --no-interaction \
    --prefer-dist \
    --ignore-platform-reqs

# ============================================================
# Stage 3 – Image finale PHP 8.2 + Apache
# ============================================================
FROM php:8.2-apache AS app

# --- Extensions PHP nécessaires ----------------------------
RUN apt-get update && apt-get install -y \
        libicu-dev \
        libzip-dev \
        libxslt1-dev \
        zip \
        unzip \
    && docker-php-ext-install \
        pdo_mysql \
        intl \
        zip \
        opcache \
        xsl \
    && docker-php-ext-enable opcache \
    && rm -rf /var/lib/apt/lists/*

# --- Configuration Apache ----------------------------------
RUN a2enmod rewrite headers

COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

# --- Configuration PHP (prod) ------------------------------
COPY docker/php.ini /usr/local/etc/php/conf.d/app.ini

# --- Application -------------------------------------------
WORKDIR /var/www/html

# Copie du code source (hors vendor, node_modules, var, .git)
COPY --chown=www-data:www-data . .

# Supprime les dossiers qui ne doivent pas venir du host
RUN rm -rf vendor node_modules var/cache var/log public/build

# Injection des dépendances depuis les stages précédents
COPY --from=vendor  --chown=www-data:www-data /app/vendor ./vendor
COPY --from=assets  --chown=www-data:www-data /app/public/build ./public/build

# Répertoires var writables pour www-data
RUN mkdir -p var/cache var/log \
    && chown -R www-data:www-data var \
    && chmod -R 775 var

# --- Entrypoint --------------------------------------------
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["apache2-foreground"]