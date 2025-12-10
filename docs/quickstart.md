# 🚀 Guide de Démarrage Rapide - Joy Pharma Backend

Ce guide vous permet de démarrer rapidement avec Joy Pharma Backend en utilisant Docker.

## ⚡ Installation en 3 Étapes

### 1️⃣ Prérequis

Installez Docker Desktop :
- **macOS** : [Docker Desktop pour Mac](https://docs.docker.com/desktop/install/mac-install/)
- **Windows** : [Docker Desktop pour Windows](https://docs.docker.com/desktop/install/windows-install/)
- **Linux** : [Docker Engine](https://docs.docker.com/engine/install/)

Vérifiez l'installation :
```bash
docker --version
docker compose version
```

### 2️⃣ Configuration

Copiez le fichier d'environnement :
```bash
cp .env.example .env
```

Éditez `.env` et modifiez au minimum :
```env
# Changez ce secret en production
APP_SECRET=VotreSecretUnique32Caracteres!!

# Mot de passe de la base de données
POSTGRES_PASSWORD=VotreMotDePasseSecurise

# JWT Passphrase
JWT_PASSPHRASE=votre_passphrase_securisee

# Mercure secret
CADDY_MERCURE_JWT_SECRET=VotreSecretMercureUnique
```

### 3️⃣ Lancement

**Option A : Avec Make (recommandé)**
```bash
make start
```

**Option B : Sans Make**
```bash
# Construire les images
docker compose build --pull --no-cache

# Démarrer les conteneurs
docker compose up -d

# Créer la base de données
docker compose exec php bin/console doctrine:database:create

# Exécuter les migrations
docker compose exec php bin/console doctrine:migrations:migrate --no-interaction
```

### 4️⃣ Accès

Ouvrez votre navigateur : **https://localhost**

> ⚠️ Acceptez le certificat SSL auto-signé (normal en développement)

**API Documentation** : https://localhost/docs

## 🎯 Commandes Essentielles

### Avec Makefile

```bash
make help              # Affiche toutes les commandes disponibles
make up                # Démarre l'application
make down              # Arrête l'application
make logs              # Affiche les logs
make shell             # Accède au conteneur PHP
make db-migrate        # Execute les migrations
make cache-clear       # Vide le cache
make admin-create      # Crée un utilisateur admin
```

### Sans Makefile

```bash
# Démarrer/Arrêter
docker compose up -d
docker compose down

# Logs
docker compose logs -f

# Commandes Symfony
docker compose exec php bin/console [command]

# Shell
docker compose exec php sh
```

## 👤 Créer un Utilisateur Admin

```bash
# Avec Make
make admin-create

# Sans Make
docker compose exec php bin/console app:create-admin-user
```

Suivez les instructions pour créer votre compte administrateur.

## 🗄️ Base de Données

### Accès Direct

Si vous avez un client PostgreSQL (pgAdmin, DBeaver, etc.) :

```
Host:     localhost
Port:     5432
Database: app
User:     app
Password: (celui défini dans .env)
```

### Commandes

```bash
# Créer la base
make db-create

# Exécuter les migrations
make db-migrate

# Réinitialiser la base
make db-reset

# Backup
make db-backup
```

## 🔍 Elasticsearch

Elasticsearch est disponible sur : **http://localhost:9200**

Vérifier le statut :
```bash
curl http://localhost:9200/_cluster/health
```

Réindexer les produits :
```bash
make elasticsearch-reindex
# ou
docker compose exec php bin/console app:reindex-products
```

## 🐛 Déboguer avec XDebug

### Activer XDebug

```bash
# Stopper les conteneurs
docker compose down

# Redémarrer avec XDebug
XDEBUG_MODE=debug docker compose up -d
```

### Configuration IDE (PHPStorm/VSCode)

**PHPStorm** :
1. Settings → PHP → Servers
2. Name : `localhost`
3. Host : `localhost`
4. Port : `443`
5. Debugger : `Xdebug`
6. Use path mappings : ✅
   - `/app` → chemin local du projet

**VSCode** (launch.json) :
```json
{
    "name": "Listen for Xdebug",
    "type": "php",
    "request": "launch",
    "port": 9003,
    "pathMappings": {
        "/app": "${workspaceFolder}"
    }
}
```

## 🔒 JWT (Authentication)

### Générer les Clés JWT

```bash
# Avec Make
make jwt-generate

# Sans Make
docker compose exec php bin/console lexik:jwt:generate-keypair --overwrite
```

Les clés sont générées dans `config/jwt/` :
- `private.pem` : Clé privée
- `public.pem` : Clé publique

## 📦 Dépendances

### Installer un Package

```bash
# Avec Make
make composer-require package=vendor/package

# Sans Make
docker compose exec php composer require vendor/package
```

### Mettre à Jour

```bash
# Avec Make
make composer-update

# Sans Make
docker compose exec php composer update
```

## 🧪 Tests

```bash
# Avec Make
make tests

# Sans Make
docker compose exec php bin/phpunit
```

## 📝 Logs

```bash
# Tous les services
make logs

# PHP uniquement
make logs-php

# 100 dernières lignes
docker compose logs --tail=100 php
```

## 🛑 Arrêter l'Application

```bash
# Avec Make
make down

# Sans Make
docker compose down

# Avec suppression des volumes (⚠️ perte de données)
docker compose down -v
```

## 🔄 Réinitialiser Complètement

```bash
# Avec Make
make reset

# Sans Make
docker compose down -v
docker compose build --no-cache
docker compose up -d
docker compose exec php bin/console doctrine:database:create
docker compose exec php bin/console doctrine:migrations:migrate --no-interaction
```

## 🚢 Déploiement Production

### Build Production

```bash
# Avec Make
make prod-build
make prod-up

# Sans Make
docker compose -f compose.yaml -f compose.prod.yaml build --no-cache
docker compose -f compose.yaml -f compose.prod.yaml up -d
```

### Variables d'Environnement Critiques

Créez un fichier `.env.local` ou `.env.prod` avec :

```env
APP_ENV=prod
APP_SECRET=un_secret_vraiment_unique_et_long
DATABASE_URL=postgresql://user:pass@host:5432/dbname
SERVER_NAME=votre-domaine.com
MERCURE_JWT_SECRET=secret_mercure_production
CORS_ALLOW_ORIGIN='^https?://(www\.)?votre-domaine\.com$'
```

## ❓ Problèmes Courants

### Port 80/443 déjà utilisé

Modifiez les ports dans `.env` :
```env
HTTP_PORT=8080
HTTPS_PORT=8443
```

### Erreur "Cannot connect to database"

Attendez que PostgreSQL soit complètement démarré :
```bash
docker compose logs database
```

### Erreur de permission

```bash
# Reconstruire
docker compose down
docker compose build --no-cache
docker compose up -d
```

### Cache bloqué

```bash
make cache-clear
# ou
docker compose exec php bin/console cache:clear
```

## 📚 Documentation Complète

- **Docker** : [`DOCKER.md`](../DOCKER.md)
- **Docker Détaillé** : [`docs/docker.md`](docker.md)
- **API** : https://localhost/docs

## 🆘 Besoin d'Aide ?

### Vérifier l'état des services

```bash
docker compose ps
```

Tous les services doivent être "Up" et "healthy".

### Vérifier les logs

```bash
# Logs de tous les services
docker compose logs

# Logs PHP
docker compose logs php

# Logs Base de données
docker compose logs database

# Logs Elasticsearch
docker compose logs elasticsearch
```

### Accéder au shell

```bash
# Shell PHP
make shell

# Shell Root
make shell-root
```

## 🎉 C'est Parti !

Vous êtes maintenant prêt à développer avec Joy Pharma Backend !

**Prochaines étapes** :
1. ✅ Créer un utilisateur admin : `make admin-create`
2. ✅ Tester l'API : https://localhost/docs
3. ✅ Configurer votre IDE
4. ✅ Commencer à coder ! 🚀

---

**Fait avec ❤️ pour Joy Pharma**

