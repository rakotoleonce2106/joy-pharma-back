# Makefile pour Joy Pharma Backend
.PHONY: help install up down logs shell db-migrate db-migration-create db-create composer-install tests clean

.DEFAULT_GOAL := help

help: ## Affiche cette aide
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

install: ## Installe les dépendances Composer
	composer install

update: ## Met à jour les dépendances Composer
	composer update

require: ## Installe un package Composer (usage: make require package=vendor/package)
	composer require $(package)

db-create: ## Crée la base de données
	php bin/console doctrine:database:create --if-not-exists

db-migration-create: ## Crée une nouvelle migration (usage: make db-migration-create name=MigrationName)
	php bin/console doctrine:migrations:generate

db-migrate: ## Execute les migrations
	php bin/console doctrine:migrations:migrate --no-interaction

db-reset: ## Réinitialise la base de données
	php bin/console doctrine:database:drop --force --if-exists
	php bin/console doctrine:database:create
	php bin/console doctrine:migrations:migrate --no-interaction

db-backup: ## Sauvegarde la base de données
	php bin/console doctrine:query:sql "SELECT * FROM pg_dump -U app app" > backup_$(shell date +%Y%m%d_%H%M%S).sql
	@echo "Backup créé: backup_$(shell date +%Y%m%d_%H%M%S).sql"

cache-clear: ## Vide le cache Symfony
	php bin/console cache:clear

cache-warmup: ## Préchauffe le cache Symfony
	php bin/console cache:warmup

jwt-generate: ## Génère les clés JWT
	php bin/console lexik:jwt:generate-keypair --overwrite

admin-create: ## Crée un utilisateur admin
	php bin/console app:create-admin-user

elasticsearch-reindex: ## Réindexe Elasticsearch
	php bin/console app:reindex-products

tests: ## Execute les tests
	php bin/phpunit

clean: ## Nettoie le cache
	php bin/console cache:clear
	rm -rf var/cache/*

start: install db-create db-migrate ## Installation complète et démarrage

reset: clean start ## Réinitialisation complète

prod-install: ## Installation pour la production
	composer install --no-dev --optimize-autoloader

prod-cache: ## Cache de production
	php bin/console cache:clear --env=prod
	php bin/console cache:warmup --env=prod
