# Multi-stage Dockerfile pour Joy Pharma Backend
# Utilise FrankenPHP avec Symfony 7.2

ARG PHP_VERSION=8.3
ARG FRANKENPHP_VERSION=1.0
FROM dunglas/frankenphp:${FRANKENPHP_VERSION}-php${PHP_VERSION}-bookworm AS frankenphp_base

# Installer les dépendances système nécessaires
RUN install-php-extensions \
    opcache \
    pdo_pgsql \
    intl \
    zip \
    gd \
    exif \
    pcntl \
    redis \
    @composer

# Stage de développement
FROM frankenphp_base AS frankenphp_dev

ENV APP_ENV=dev \
    APP_DEBUG=1 \
    PHP_INI_SCAN_DIR=/usr/local/etc/php/conf.d:/usr/local/etc/php/conf.d/custom

# Copier la configuration PHP commune et pour le développement
COPY frankenphp/conf.d/10-app.ini /usr/local/etc/php/conf.d/custom/
COPY frankenphp/conf.d/20-app.dev.ini /usr/local/etc/php/conf.d/custom/

# Stage de production
FROM frankenphp_base AS frankenphp_prod

ENV APP_ENV=prod \
    APP_DEBUG=0 \
    PHP_INI_SCAN_DIR=/usr/local/etc/php/conf.d:/usr/local/etc/php/conf.d/custom

# Copier la configuration PHP commune et pour la production
COPY frankenphp/conf.d/10-app.ini /usr/local/etc/php/conf.d/custom/
COPY frankenphp/conf.d/20-app.prod.ini /usr/local/etc/php/conf.d/custom/

# Créer le répertoire de l'application
WORKDIR /app

# Copier les fichiers de configuration Composer et installer les dépendances
COPY composer.json composer.lock symfony.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --optimize-autoloader

# Copier le reste du code source
COPY . .

# Finaliser l'installation de Composer
RUN composer dump-autoload --optimize --classmap-authoritative --no-dev

# Configurer les permissions
RUN chown -R www-data:www-data /app/var /app/public/images

# Copier les fichiers de configuration FrankenPHP
COPY frankenphp/Caddyfile /etc/caddy/Caddyfile
COPY frankenphp/worker.Caddyfile /etc/caddy/worker.Caddyfile
COPY frankenphp/worker.php /app/worker.php

# Exposer les ports
EXPOSE 80 443 443/udp

# Healthcheck
HEALTHCHECK --interval=30s --timeout=3s --start-period=40s --retries=3 \
    CMD php -r "echo file_get_contents('http://localhost/health.php') ? 'OK' : 'FAIL';" || exit 1

# Point d'entrée
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["frankenphp", "worker", "--config", "/etc/caddy/worker.Caddyfile"]

