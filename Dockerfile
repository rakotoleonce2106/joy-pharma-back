#syntax=docker/dockerfile:1

# Versions
FROM dunglas/frankenphp:1-php8.3 AS frankenphp_upstream

# The different stages of this Dockerfile are meant to be built into separate images
# https://docs.docker.com/develop/develop-images/multistage-build/#stop-at-a-specific-build-stage
# https://docs.docker.com/compose/compose-file/#target


# Base FrankenPHP image
FROM frankenphp_upstream AS frankenphp_base

WORKDIR /app

VOLUME /app/var/

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
	;

# https://getcomposer.org/doc/03-cli.md#composer-allow-superuser
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV COMPOSER_MEMORY_LIMIT=-1
ENV COMPOSER_NO_INTERACTION=1
ENV COMPOSER_DISABLE_XDEBUG_WARN=1

ENV PHP_INI_SCAN_DIR=":$PHP_INI_DIR/app.conf.d"

###> recipes ###
###> doctrine/doctrine-bundle ###
RUN install-php-extensions pdo_pgsql
###< doctrine/doctrine-bundle ###
###< recipes ###

COPY --link frankenphp/conf.d/10-app.ini $PHP_INI_DIR/app.conf.d/
COPY --link --chmod=755 frankenphp/docker-entrypoint.sh /usr/local/bin/docker-entrypoint
COPY --link frankenphp/Caddyfile /etc/caddy/Caddyfile

ENTRYPOINT ["docker-entrypoint"]

HEALTHCHECK --start-period=60s CMD curl -f http://localhost:2019/metrics || exit 1
CMD [ "frankenphp", "run", "--config", "/etc/caddy/Caddyfile" ]

# Dev FrankenPHP image
FROM frankenphp_base AS frankenphp_dev

ENV APP_ENV=dev XDEBUG_MODE=off

RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

RUN set -eux; \
	install-php-extensions \
		xdebug \
	;

COPY --link frankenphp/conf.d/20-app.dev.ini $PHP_INI_DIR/app.conf.d/

CMD [ "frankenphp", "run", "--config", "/etc/caddy/Caddyfile", "--watch" ]

# Prod FrankenPHP image
FROM frankenphp_base AS frankenphp_prod

ENV APP_ENV=prod
ENV FRANKENPHP_CONFIG="import worker.Caddyfile"

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY --link frankenphp/conf.d/20-app.prod.ini $PHP_INI_DIR/app.conf.d/
COPY --link frankenphp/worker.Caddyfile /etc/caddy/worker.Caddyfile

# prevent the reinstallation of vendors at every changes in the source code
COPY --link composer.json composer.lock ./

# Verify Composer installation and update if needed
RUN set -eux; \
	COMPOSER_BIN=$(which composer 2>/dev/null || echo /usr/local/bin/composer); \
	if [ ! -f "$COMPOSER_BIN" ]; then \
		echo "ERROR: Composer not found at $COMPOSER_BIN"; \
		find /usr -name composer 2>/dev/null || true; \
		exit 1; \
	fi; \
	php -d memory_limit=-1 "$COMPOSER_BIN" --version; \
	php -d memory_limit=-1 "$COMPOSER_BIN" self-update --2 || true; \
	php -d memory_limit=-1 "$COMPOSER_BIN" clear-cache || true

# Install dependencies (we'll regenerate autoloader later for optimization)
RUN set -eux; \
	COMPOSER_BIN=$(which composer 2>/dev/null || echo /usr/local/bin/composer); \
	php -d memory_limit=-1 "$COMPOSER_BIN" install \
		--prefer-dist \
		--no-dev \
		--no-scripts \
		--no-interaction \
		--no-progress \
		--verbose || { \
		echo "=== COMPOSER INSTALL FAILED ==="; \
		echo "Composer location: $COMPOSER_BIN"; \
		echo "PHP version:"; \
		php -v; \
		echo "PHP extensions:"; \
		php -m; \
		echo "Running composer diagnose..."; \
		php -d memory_limit=-1 "$COMPOSER_BIN" diagnose || true; \
		echo "Checking composer.json..."; \
		php -d memory_limit=-1 "$COMPOSER_BIN" validate --no-check-publish || true; \
		exit 1; \
	}

# copy sources
COPY --link . ./
RUN rm -Rf frankenphp/

RUN set -eux; \
	COMPOSER_BIN=$(which composer 2>/dev/null || echo /usr/local/bin/composer); \
	mkdir -p var/cache var/log; \
	php -d memory_limit=-1 "$COMPOSER_BIN" dump-autoload --classmap-authoritative --no-dev; \
	php -d memory_limit=-1 "$COMPOSER_BIN" dump-env prod; \
	php -d memory_limit=-1 "$COMPOSER_BIN" run-script --no-dev post-install-cmd; \
	chmod +x bin/console; sync;
