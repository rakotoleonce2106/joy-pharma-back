# 🐳 Docker - Joy Pharma Backend

Application Symfony dockerisée avec **FrankenPHP** pour des performances optimales.

## 🚀 Démarrage Rapide

### Installation

```bash
# 1. Construire les images
docker compose build --pull --no-cache

# 2. Démarrer l'application
docker compose up --wait

# 3. Accéder à l'application
# Ouvrez https://localhost dans votre navigateur
# Acceptez le certificat TLS auto-généré
```

### Arrêt

```bash
docker compose down --remove-orphans
```

## 📋 Prérequis

- **Docker Engine** 20.10+
- **Docker Compose** V2.10+

## 🏗️ Architecture

### Stack Technologique

- **FrankenPHP** : Serveur d'application PHP moderne
- **Caddy** : Serveur web avec HTTPS automatique
- **PostgreSQL 16** : Base de données
- **Symfony 7.2** : Framework PHP
- **API Platform 4** : API REST/GraphQL

### Services

| Service    | Description                          | Port(s)       |
|------------|--------------------------------------|---------------|
| `php`      | Application Symfony + FrankenPHP     | 80, 443       |
| `database` | PostgreSQL 16                        | 5432 (dev)    |

## 📁 Structure

```
.
├── Dockerfile                    # Image de l'application
├── compose.yaml                  # Configuration base
├── compose.override.yaml         # Surcharges développement
├── compose.prod.yaml            # Surcharges production
├── .dockerignore                # Exclusions build
├── frankenphp/
│   ├── Caddyfile                # Configuration Caddy
│   ├── worker.Caddyfile         # Configuration Worker
│   ├── worker.php               # Point d'entrée Worker
│   └── conf.d/                  # Configuration PHP
│       ├── 10-app.ini           # Config commune
│       ├── 20-app.dev.ini       # Config développement
│       └── 20-app.prod.ini      # Config production
└── docs/
    └── docker.md                # Documentation détaillée
```

## 🛠️ Commandes Essentielles

### Symfony

```bash
# Console Symfony
docker compose exec php bin/console

# Migrations
docker compose exec php bin/console doctrine:migrations:migrate

# Vider le cache
docker compose exec php bin/console cache:clear

# Créer un admin
docker compose exec php bin/console app:create-admin-user
```

### Composer

```bash
# Installer une dépendance
docker compose exec php composer require vendor/package

# Mettre à jour
docker compose exec php composer update
```

### Base de Données

```bash
# Créer la base
docker compose exec php bin/console doctrine:database:create

# Exécuter les migrations
docker compose exec php bin/console doctrine:migrations:migrate --no-interaction

# Backup
docker compose exec database pg_dump -U app app > backup.sql

# Restaurer
docker compose exec -T database psql -U app app < backup.sql
```

### Logs

```bash
# Tous les services
docker compose logs -f

# Service PHP uniquement
docker compose logs -f php

# 100 dernières lignes
docker compose logs --tail=100 php
```

## 🔧 Développement

### Configuration

Le fichier `compose.override.yaml` est automatiquement utilisé en développement et active :

- ✅ Hot-reload du code source
- ✅ XDebug disponible
- ✅ Port PostgreSQL exposé
- ✅ Configuration PHP optimisée pour le dev

### XDebug

**Activer XDebug** :

```bash
XDEBUG_MODE=debug docker compose up -d
```

**Configuration IDE** :
- Host : `localhost`
- Port : `9003`
- IDE Key : `PHPSTORM`
- Path mapping : `/app` → votre chemin local

**Modes disponibles** :
```bash
XDEBUG_MODE=off       # Désactivé (défaut)
XDEBUG_MODE=debug     # Débogage
XDEBUG_MODE=coverage  # Couverture de code
XDEBUG_MODE=profile   # Profilage
```

### Accéder au Conteneur

```bash
# Shell utilisateur
docker compose exec php sh

# Shell root
docker compose exec -u root php sh
```

## 🚢 Production

### Déploiement

```bash
# Build production
docker compose -f compose.yaml -f compose.prod.yaml build --no-cache

# Démarrer en production
docker compose -f compose.yaml -f compose.prod.yaml up -d
```

### Variables d'Environnement Critiques

```env
APP_ENV=prod
APP_SECRET=votre_secret_unique_32_caracteres
DATABASE_URL=postgresql://user:pass@host:5432/dbname
SERVER_NAME=votre-domaine.com
MERCURE_JWT_SECRET=secret_mercure_unique
```

### Mode Worker FrankenPHP

En production, le mode Worker de FrankenPHP est activé automatiquement :

- ⚡ **Performances 15x supérieures**
- 🚀 Application pré-chargée en mémoire
- 💾 Réduction de la latence
- 🔄 Pas de redémarrage à chaque requête

## 🔒 Sécurité

### Certificats TLS

**Développement** : Certificats auto-signés générés automatiquement

**Production** : Certificats Let's Encrypt gratuits avec renouvellement automatique

### HTTPS

HTTPS est **toujours activé** grâce à Caddy :
- HTTP/2 et HTTP/3 natifs
- Redirection automatique HTTP → HTTPS
- Early Hints pour l'optimisation

## 🎯 Fonctionnalités

### Mercure (Temps Réel)

Hub Mercure intégré pour les notifications push :

```yaml
MERCURE_URL: http://php/.well-known/mercure
MERCURE_PUBLIC_URL: https://localhost/.well-known/mercure
```

### HTTP/3

Support natif du protocole QUIC pour des connexions plus rapides.

### Compression

Compression automatique avec :
- Zstandard (le plus efficace)
- Brotli
- Gzip (fallback)

## 🐛 Résolution de Problèmes

### Port déjà utilisé

Modifiez les ports dans `.env` :

```env
HTTP_PORT=8080
HTTPS_PORT=8443
```

### Reset complet

```bash
# ⚠️ Supprime toutes les données
docker compose down -v
docker compose build --no-cache
docker compose up -d
```

### Erreurs de permission

```bash
# Reconstruire proprement
docker compose down
docker compose build --no-cache
docker compose up -d
```

## 📊 Volumes

| Volume           | Description                    |
|------------------|--------------------------------|
| `caddy_data`     | Certificats TLS               |
| `caddy_config`   | Configuration Caddy           |
| `database_data`  | Données PostgreSQL            |

## 🔄 Mise à Jour

```bash
# Mettre à jour les images
docker compose pull

# Reconstruire
docker compose build --pull

# Redémarrer
docker compose up -d
```

## 📚 Documentation Complète

Pour plus de détails, consultez [`docs/docker.md`](docs/docker.md)

## 🔗 Liens Utiles

- [FrankenPHP](https://frankenphp.dev/)
- [Symfony Docker](https://github.com/dunglas/symfony-docker)
- [Caddy Server](https://caddyserver.com/)
- [API Platform](https://api-platform.com/)

## 📝 License

MIT

---

**Fait avec ❤️ pour Joy Pharma**

