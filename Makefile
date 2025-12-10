# Makefile pour Joy Pharma Backend
.PHONY: help build up down logs shell db-migrate db-create composer-install tests clean

.DEFAULT_GOAL := help

help: ## Affiche cette aide
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

build: ## Construit les images Docker
	docker compose build --pull --no-cache

up: ## Démarre les conteneurs
	docker compose up -d

down: ## Arrête les conteneurs
	docker compose down --remove-orphans

restart: down up ## Redémarre les conteneurs

logs: ## Affiche les logs
	docker compose logs -f

logs-php: ## Affiche les logs PHP
	docker compose logs -f php

shell: ## Accède au shell du conteneur PHP
	docker compose exec php sh

shell-root: ## Accède au shell du conteneur PHP en root
	docker compose exec -u root php sh

db-create: ## Crée la base de données
	docker compose exec php bin/console doctrine:database:create --if-not-exists

db-migrate: ## Execute les migrations
	docker compose exec php bin/console doctrine:migrations:migrate --no-interaction

db-reset: ## Réinitialise la base de données
	docker compose exec php bin/console doctrine:database:drop --force --if-exists
	docker compose exec php bin/console doctrine:database:create
	docker compose exec php bin/console doctrine:migrations:migrate --no-interaction

db-backup: ## Sauvegarde la base de données
	docker compose exec database pg_dump -U app app > backup_$(shell date +%Y%m%d_%H%M%S).sql
	@echo "Backup créé: backup_$(shell date +%Y%m%d_%H%M%S).sql"

composer-install: ## Installe les dépendances Composer
	docker compose exec php composer install

composer-update: ## Met à jour les dépendances Composer
	docker compose exec php composer update

composer-require: ## Installe un package Composer (usage: make composer-require package=vendor/package)
	docker compose exec php composer require $(package)

cache-clear: ## Vide le cache Symfony
	docker compose exec php bin/console cache:clear

cache-warmup: ## Préchauffe le cache Symfony
	docker compose exec php bin/console cache:warmup

jwt-generate: ## Génère les clés JWT
	docker compose exec php bin/console lexik:jwt:generate-keypair --overwrite

admin-create: ## Crée un utilisateur admin
	docker compose exec php bin/console app:create-admin-user

elasticsearch-reindex: ## Réindexe Elasticsearch
	docker compose exec php bin/console app:reindex-products

tests: ## Execute les tests
	docker compose exec php bin/phpunit

clean: ## Nettoie tout (conteneurs, volumes, images)
	docker compose down -v
	docker system prune -a --volumes -f

ps: ## Liste les conteneurs en cours d'exécution
	docker compose ps

start: build up db-create db-migrate ## Installation complète et démarrage

reset: clean start ## Réinitialisation complète

prod-build: ## Build pour la production
	docker compose -f compose.yaml -f compose.prod.yaml build --no-cache

prod-up: ## Démarre en production
	docker compose -f compose.yaml -f compose.prod.yaml up -d

prod-down: ## Arrête la production
	docker compose -f compose.yaml -f compose.prod.yaml down

