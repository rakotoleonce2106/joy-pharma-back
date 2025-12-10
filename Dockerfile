#syntax=docker/dockerfile:1.4

# Versions
FROM dunglas/frankenphp:latest-php8.3 AS frankenphp_upstream

# The different stages of this Dockerfile are meant to be built into separate images
# https://docs.docker.com/develop/develop-images/multistage-build/#stop-at-a-specific-build-stage
# https://docs.docker.com/compose/compose-file/#target


# Base FrankenPHP image
FROM frankenphp_upstream AS frankenphp_base

WORKDIR /app

# persistent / runtime deps
# hadolint ignore=DL3008
RUN apt-get update && apt-get install -y --no-install-recommends \
	acl \
	file \
	gettext \
	git \
	&& rm -rf /var/lib/apt/lists/*

RUN set -eux; \
	install-php-extensions \
		@composer \
		apcu \
		intl \
		opcache \
		zip \
		pdo_pgsql \
	;

###> recipes ###
###< recipes ###

COPY --link frankenphp/conf.d/10-app.ini $PHP_INI_DIR/conf.d/
COPY --link frankenphp/conf.d/20-app.prod.ini $PHP_INI_DIR/conf.d/

COPY --link frankenphp/worker.php /app/frankenphp/worker.php
COPY --link frankenphp/Caddyfile /etc/caddy/Caddyfile
COPY --link frankenphp/worker.Caddyfile /etc/caddy/worker.Caddyfile
COPY --link frankenphp/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh

RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# prevent the reinstallation of vendors at every changes in the source code
COPY --link composer.* symfony.* ./
RUN set -eux; \
	composer install --no-cache --prefer-dist --no-dev --no-autoloader --no-scripts --no-progress

# copy sources
COPY --link . ./
RUN rm -Rf frankenphp/

RUN set -eux; \
	mkdir -p var/cache var/log config/jwt; \
	composer dump-autoload --classmap-authoritative --no-dev; \
	# Create minimal .env for build (real secrets injected by Infisical at runtime)
	echo "APP_ENV=prod" > .env; \
	echo "APP_SECRET=build_secret_replaced_at_runtime" >> .env; \
	echo "DATABASE_URL=postgresql://app:pass@database:5432/app?serverVersion=16&charset=utf8" >> .env; \
	composer dump-env prod; \
	composer run-script --no-dev post-install-cmd; \
	chmod +x bin/console; sync;

# Dev FrankenPHP image
FROM frankenphp_base AS frankenphp_dev

ENV APP_ENV=dev XDEBUG_MODE=off
VOLUME /app/var/

RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

COPY --link frankenphp/conf.d/20-app.dev.ini $PHP_INI_DIR/conf.d/

RUN set -eux; \
	install-php-extensions \
		xdebug \
	;

RUN rm $PHP_INI_DIR/conf.d/20-app.prod.ini; \
	composer install --prefer-dist --no-progress --no-interaction; \
	composer clear-cache

# Production FrankenPHP image
FROM frankenphp_base AS frankenphp_prod

ENV APP_ENV=prod
ENV FRANKENPHP_CONFIG="import worker.Caddyfile"

RUN set -eux; \
	composer dump-env prod; \
	composer run-script --no-dev post-install-cmd; \
	chmod +x bin/console; sync;

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]

