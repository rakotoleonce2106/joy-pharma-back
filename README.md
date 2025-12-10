# 🏥 Joy Pharma Backend API

API REST moderne pour la plateforme Joy Pharma, construite avec **Symfony 7.2**, **API Platform 4** et **FrankenPHP**.

[![Symfony](https://img.shields.io/badge/Symfony-7.2-000000.svg?style=flat&logo=symfony)](https://symfony.com)
[![API Platform](https://img.shields.io/badge/API%20Platform-4.1-38A3A5.svg)](https://api-platform.com)
[![PHP](https://img.shields.io/badge/PHP-8.3+-777BB4.svg?style=flat&logo=php)](https://php.net)
[![Docker](https://img.shields.io/badge/Docker-Ready-2496ED.svg?style=flat&logo=docker)](https://docker.com)

## 🚀 Démarrage Rapide

### Prérequis

- Docker Desktop 20.10+ ([Installer Docker](https://docs.docker.com/get-docker/))
- Docker Compose V2.10+

### Installation en 3 Commandes

```bash
# 1. Construire les images
docker compose build --pull --no-cache

# 2. Démarrer l'application
docker compose up -d

# 3. Initialiser la base de données
docker compose exec php bin/console doctrine:database:create
docker compose exec php bin/console doctrine:migrations:migrate --no-interaction
```

**Ou avec Makefile** :
```bash
make start
```

### Accès

- 🌐 **Application** : https://localhost
- 📖 **Documentation API** : https://localhost/docs
- 🔍 **Elasticsearch** : http://localhost:9200
- 🗄️ **PostgreSQL** : localhost:5432

> ⚠️ Acceptez le certificat SSL auto-signé dans votre navigateur (normal en développement)

**Guide complet** : [docs/quickstart.md](docs/quickstart.md)

## 🏗️ Architecture

### Stack Technologique

| Composant         | Technologie                    | Version  |
|-------------------|--------------------------------|----------|
| **Framework**     | Symfony                        | 7.2      |
| **API**           | API Platform                   | 4.1      |
| **Serveur**       | FrankenPHP + Caddy             | Latest   |
| **Base de données** | PostgreSQL                   | 16       |
| **Recherche**     | Elasticsearch                  | 8.11     |
| **Temps réel**    | Mercure                        | Intégré  |
| **Authentication**| JWT (Lexik)                    | 3.1      |

### Services Docker

```yaml
services:
  php:          # Application Symfony + FrankenPHP
  database:     # PostgreSQL 16
  elasticsearch: # Elasticsearch 8.11
```

## ✨ Fonctionnalités

### API REST & GraphQL
- 🔐 Authentication JWT avec refresh tokens
- 📱 API REST complète (CRUD)
- 🔍 Recherche Elasticsearch intégrée
- 📄 Documentation OpenAPI/Swagger automatique
- ✅ Validation avancée des données
- 🌍 Support multi-langue (i18n)

### Performance
- ⚡ **FrankenPHP Worker Mode** : 15x plus rapide
- 🚀 HTTP/2 et HTTP/3 natifs
- 💨 Early Hints pour l'optimisation
- 🗜️ Compression Zstandard/Brotli/Gzip
- 📦 OPcache optimisé
- 🔄 Mercure pour le temps réel

### Sécurité
- 🔒 HTTPS automatique (Let's Encrypt)
- 🔑 JWT Authentication
- 🛡️ CORS configuré
- 🔐 Rate limiting (API Platform)
- ✅ Validation stricte des entrées

### Développement
- 🐛 XDebug intégré
- 📝 Logs structurés
- 🔄 Hot-reload du code
- 🧪 Tests automatisés
- 📊 Profiler Symfony

## 📖 Documentation

### Guides
- 🚀 [Guide de Démarrage Rapide](docs/quickstart.md)
- 🐳 [Documentation Docker](DOCKER.md)
- 🐳 [Docker Détaillé](docs/docker.md)

### API Documentation
- 📖 OpenAPI/Swagger : https://localhost/docs
- 🔗 GraphQL Playground : https://localhost/graphql (si activé)

## 🛠️ Commandes Utiles

### Avec Makefile

```bash
make help              # Affiche toutes les commandes
make up                # Démarre l'application
make down              # Arrête l'application
make logs              # Affiche les logs
make shell             # Accède au shell PHP
make db-migrate        # Execute les migrations
make cache-clear       # Vide le cache
make admin-create      # Crée un admin
make tests             # Execute les tests
```

### Symfony Console

```bash
# Via Docker
docker compose exec php bin/console [command]

# Exemples
docker compose exec php bin/console debug:router
docker compose exec php bin/console doctrine:migrations:list
docker compose exec php bin/console app:create-admin-user
```

### Base de Données

```bash
# Créer la base
make db-create

# Migrations
make db-migrate

# Reset complet
make db-reset

# Backup
make db-backup
```

## 🔧 Configuration

### Variables d'Environnement

Copiez `.env.example` vers `.env` et modifiez :

```env
# Application
APP_ENV=dev
APP_SECRET=VotreSecretUnique32Caracteres!!

# Base de données
DATABASE_URL=postgresql://app:password@database:5432/app

# JWT
JWT_PASSPHRASE=votre_passphrase
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem

# Elasticsearch
ELASTICSEARCH_URL=http://elasticsearch:9200

# Mercure
CADDY_MERCURE_JWT_SECRET=VotreSecretMercure
```

### Générer les Clés JWT

```bash
make jwt-generate
# ou
docker compose exec php bin/console lexik:jwt:generate-keypair --overwrite
```

## 🧪 Tests

```bash
# Tous les tests
make tests

# Tests spécifiques
docker compose exec php bin/phpunit tests/Unit
docker compose exec php bin/phpunit tests/Functional
```

## 🐛 Débogage

### XDebug

Activez XDebug :
```bash
XDEBUG_MODE=debug docker compose up -d
```

Configuration IDE :
- Host : `localhost`
- Port : `9003`
- Path mapping : `/app` → votre répertoire local

### Logs

```bash
# Tous les logs
docker compose logs -f

# Logs PHP uniquement
docker compose logs -f php

# Logs de la base de données
docker compose logs -f database
```

## 🚢 Déploiement Production

### Build Production

```bash
# Avec Make
make prod-build
make prod-up

# Manuel
docker compose -f compose.yaml -f compose.prod.yaml build --no-cache
docker compose -f compose.yaml -f compose.prod.yaml up -d
```

### Configuration Production

Créez `.env.prod` avec :

```env
APP_ENV=prod
APP_SECRET=un_secret_vraiment_long_et_unique
DATABASE_URL=postgresql://user:pass@host:5432/dbname
SERVER_NAME=api.votre-domaine.com
MERCURE_JWT_SECRET=secret_prod_unique
```

### Mode Worker FrankenPHP

En production, FrankenPHP utilise automatiquement le **Worker Mode** pour des performances optimales :
- Application pré-chargée en mémoire
- Pas de redémarrage à chaque requête
- Performances jusqu'à **15x supérieures**

## 📦 Structure du Projet

```
.
├── bin/                    # Scripts exécutables
├── config/                 # Configuration Symfony
├── docs/                   # Documentation
├── frankenphp/            # Configuration FrankenPHP/Caddy
├── public/                # Point d'entrée web
├── src/
│   ├── ApiResource/       # Ressources API Platform
│   ├── Controller/        # Contrôleurs
│   ├── Entity/            # Entités Doctrine
│   ├── Repository/        # Repositories
│   ├── Service/           # Services métier
│   └── ...
├── Dockerfile             # Image Docker
├── compose.yaml           # Config Docker base
├── compose.override.yaml  # Config Docker dev
├── compose.prod.yaml      # Config Docker prod
├── Makefile              # Commandes simplifiées
└── README.md             # Ce fichier
```

## ❓ Support & Troubleshooting

### Problèmes Courants

**Port 80/443 déjà utilisé** :
```env
HTTP_PORT=8080
HTTPS_PORT=8443
```

**Erreur de connexion à la base** :
```bash
docker compose logs database
docker compose restart database
```

**Reset complet** :
```bash
make clean
make start
```

### Logs de Débogage

```bash
docker compose logs --tail=100 php
docker compose exec php tail -f var/log/dev.log
```

## 🔗 Liens Utiles

- [Symfony Documentation](https://symfony.com/doc/current/index.html)
- [API Platform](https://api-platform.com/docs/)
- [FrankenPHP](https://frankenphp.dev/)
- [Symfony Docker](https://github.com/dunglas/symfony-docker)
- [Docker Compose](https://docs.docker.com/compose/)

## 📝 License

Ce projet est sous licence MIT.

## 🙏 Crédits

### Infrastructure Docker
Basé sur [symfony-docker](https://github.com/dunglas/symfony-docker) par [Kévin Dunglas](https://dunglas.dev)

### Technologies Utilisées
- [Symfony](https://symfony.com) - Framework PHP
- [API Platform](https://api-platform.com) - Framework API REST & GraphQL
- [FrankenPHP](https://frankenphp.dev) - Serveur d'application PHP moderne
- [Caddy](https://caddyserver.com) - Serveur web avec HTTPS automatique
- [PostgreSQL](https://postgresql.org) - Base de données relationnelle
- [Elasticsearch](https://elastic.co) - Moteur de recherche

---

**Développé avec ❤️ pour Joy Pharma**
