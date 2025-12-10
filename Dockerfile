#syntax=docker/dockerfile:1.4

# Versions
FROM dunglas/frankenphp:1-php8.3 AS frankenphp_upstream

# The different stages of this Dockerfile are meant to be built into separate images
# https://docs.docker.com/docker/step-by-step/

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
	curl \
	bash \
	&& rm -rf /var/lib/apt/lists/*

# Install Infisical CLI
RUN curl -1sLf 'https://dl.cloudsmith.io/public/infisical/infisical-cli/setup.deb.sh' | bash && \
	apt-get update && \
	apt-get install -y infisical && \
	rm -rf /var/lib/apt/lists/* && \
	which infisical && infisical --version

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

FROM frankenphp_base AS frankenphp_dev

ENV APP_ENV=dev XDEBUG_MODE=off
VOLUME /app/var/

RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

RUN set -eux; \
	install-php-extensions \
		xdebug \
	;

COPY --link frankenphp/conf.d/app.ini $PHP_INI_DIR/conf.d/

CMD [ "frankenphp", "run", "--config", "/etc/caddy/Caddyfile", "--watch" ]

FROM frankenphp_base AS frankenphp_prod

ENV APP_ENV=prod
ENV FRANKENPHP_CONFIG="import worker.Caddyfile"

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY --link frankenphp/conf.d/app.ini $PHP_INI_DIR/conf.d/
COPY --link frankenphp/worker.Caddyfile /etc/caddy/worker.Caddyfile

# Prevent the reinstallation of vendors at every changes in the source code
COPY --link composer.* symfony.* ./
RUN set -eux; \
	composer install --no-cache --prefer-dist --no-dev --no-autoloader --no-scripts --no-progress

# Copy sources
COPY --link . ./
RUN rm -rf frankenphp/

# Create dummy .env for build (real secrets injected at runtime via Infisical)
RUN echo "APP_ENV=prod" > .env && \
    echo "APP_SECRET=dummy_build_secret" >> .env && \
    echo "DATABASE_URL=postgresql://app:pass@localhost:5432/app?serverVersion=15&charset=utf8" >> .env && \
    echo "JWT_PASSPHRASE=dummy_passphrase" >> .env



RUN set -eux; \
	mkdir -p var/cache var/log; \
	composer dump-autoload --classmap-authoritative --no-dev; \
	composer run-script --no-dev post-install-cmd; \
	chmod +x bin/console; sync;

# Use Infisical to inject secrets at runtime
CMD ["infisical", "run", "--projectId", "${INFISICAL_PROJECT_ID}", "--env", "${INFISICAL_ENV_SLUG}", "--", "frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
