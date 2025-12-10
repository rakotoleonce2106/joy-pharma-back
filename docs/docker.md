# Documentation Docker - Joy Pharma Backend

Cette documentation explique comment utiliser Docker pour développer et déployer l'application Joy Pharma Backend.

## Prérequis

- Docker Engine 20.10+
- Docker Compose V2.10+

## Architecture

L'application utilise **FrankenPHP**, un serveur d'application moderne pour PHP qui combine :
- Un serveur web Caddy avec HTTPS automatique
- Un support natif pour HTTP/2 et HTTP/3
- Le mode Worker pour des performances optimales
- Un hub Mercure intégré pour le temps réel
- Support de Vulcain pour l'optimisation des requêtes

## Installation et Démarrage

### Première Installation

1. **Construire les images Docker** :
```bash
docker compose build --pull --no-cache
```

2. **Démarrer les conteneurs** :
```bash
docker compose up --wait
```

3. **Accéder à l'application** :
   - Ouvrez https://localhost dans votre navigateur
   - Acceptez le certificat TLS auto-généré (normal en développement)

4. **Arrêter les conteneurs** :
```bash
docker compose down --remove-orphans
```

### Utilisation Quotidienne

```bash
# Démarrer en mode détaché
docker compose up -d

# Voir les logs
docker compose logs -f php

# Arrêter
docker compose down
```

## Structure des Fichiers Docker

### Fichiers Principaux

- **`Dockerfile`** : Définit l'image de l'application avec FrankenPHP
- **`compose.yaml`** : Configuration de base (services, volumes, réseau)
- **`compose.override.yaml`** : Surcharges pour le développement
- **`compose.prod.yaml`** : Surcharges pour la production
- **`.dockerignore`** : Fichiers exclus lors du build

### Configuration FrankenPHP

- **`frankenphp/Caddyfile`** : Configuration du serveur Caddy
- **`frankenphp/worker.Caddyfile`** : Configuration du mode Worker
- **`frankenphp/worker.php`** : Point d'entrée du Worker
- **`frankenphp/conf.d/`** : Configuration PHP personnalisée
  - `10-app.ini` : Configuration commune
  - `20-app.dev.ini` : Configuration développement
  - `20-app.prod.ini` : Configuration production

## Services Docker

### Service `php`

Le service principal exécutant l'application Symfony avec FrankenPHP.

**Ports exposés** :
- `80` (HTTP)
- `443` (HTTPS)
- `443/udp` (HTTP/3)

**Variables d'environnement importantes** :
- `APP_ENV` : Environnement (dev/prod)
- `DATABASE_URL` : Connexion à la base de données
- `MERCURE_URL` : URL interne du hub Mercure
- `XDEBUG_MODE` : Configuration XDebug (dev uniquement)

### Service `database`

PostgreSQL 16 pour la persistance des données.

**Port exposé** (dev uniquement) :
- `5432`

**Variables d'environnement** :
- `POSTGRES_DB` : Nom de la base
- `POSTGRES_USER` : Utilisateur
- `POSTGRES_PASSWORD` : Mot de passe

## Commandes Utiles

### Exécuter des Commandes Symfony

```bash
# Console Symfony
docker compose exec php bin/console

# Migrations
docker compose exec php bin/console doctrine:migrations:migrate

# Cache
docker compose exec php bin/console cache:clear

# Créer un utilisateur admin
docker compose exec php bin/console app:create-admin-user
```

### Accéder au Conteneur

```bash
# Shell interactif
docker compose exec php sh

# En tant que root
docker compose exec -u root php sh
```

### Gestion de la Base de Données

```bash
# Créer la base
docker compose exec php bin/console doctrine:database:create

# Exécuter les migrations
docker compose exec php bin/console doctrine:migrations:migrate --no-interaction

# Charger des fixtures (si disponibles)
docker compose exec php bin/console doctrine:fixtures:load --no-interaction
```

### Logs

```bash
# Tous les services
docker compose logs -f

# Service spécifique
docker compose logs -f php
docker compose logs -f database
```

## Développement

### Mode Développement

Par défaut, `compose.override.yaml` est utilisé automatiquement et active :
- Volume monté pour le code source (hot-reload)
- XDebug disponible
- PHP configuré pour le développement
- Port PostgreSQL exposé pour accès direct

### Déboguer avec XDebug

1. **Activer XDebug** :
```bash
XDEBUG_MODE=debug docker compose up -d
```

2. **Configuration IDE** :
   - Host : `localhost`
   - Port : `9003`
   - IDE Key : `PHPSTORM`
   - Path mapping : `/app` → chemin local du projet

3. **Variables d'environnement XDebug** :
   - `XDEBUG_MODE=off` : Désactivé (défaut)
   - `XDEBUG_MODE=debug` : Mode débogage
   - `XDEBUG_MODE=coverage` : Couverture de code
   - `XDEBUG_MODE=profile` : Profilage

### Installer de Nouvelles Dépendances

```bash
# Composer
docker compose exec php composer require vendor/package

# NPM (si nécessaire)
docker compose exec php npm install package-name
```

## Production

### Déploiement en Production

1. **Utiliser le fichier de composition production** :
```bash
docker compose -f compose.yaml -f compose.prod.yaml build
docker compose -f compose.yaml -f compose.prod.yaml up -d
```

2. **Variables d'environnement à configurer** :
   - `APP_SECRET` : Secret unique pour l'application
   - `DATABASE_URL` : URL de la base de production
   - `MERCURE_JWT_SECRET` : Secret Mercure
   - `SERVER_NAME` : Nom de domaine de production

3. **Mode Worker FrankenPHP** :
   
   En production, FrankenPHP utilise le mode Worker pour des performances optimales :
   - Pré-chargement de l'application Symfony
   - Pas de redémarrage à chaque requête
   - Performances jusqu'à 15x supérieures

### Optimisations Production

Le build de production inclut :
- OPcache activé et optimisé
- Autoload optimisé de Composer
- Validation des timestamps désactivée
- Preloading PHP activé
- Assertions désactivées

## Performance

### Mode Worker

Le mode Worker de FrankenPHP garde l'application Symfony en mémoire :
- **Avantages** :
  - Performances exceptionnelles
  - Réduction de la latence
  - Économie de ressources
  
- **Configuration** :
  - Activé automatiquement en production
  - Nombre de workers : `FRANKENPHP_WORKER_COUNT` (défaut : auto)

### HTTP/3 et Early Hints

FrankenPHP supporte nativement :
- **HTTP/3** : Protocole QUIC pour des connexions plus rapides
- **Early Hints** : Envoi précoce des ressources critiques
- **Compression** : Zstandard, Brotli et Gzip

## Certificats TLS

### Développement

Caddy génère automatiquement des certificats TLS auto-signés pour `localhost`.

### Production

Caddy obtient automatiquement des certificats Let's Encrypt gratuits :
- Renouvellement automatique
- Support ACME
- Configuration via `SERVER_NAME`

## Mercure (Temps Réel)

Un hub Mercure est intégré pour les notifications en temps réel :

```yaml
MERCURE_URL: http://php/.well-known/mercure
MERCURE_PUBLIC_URL: https://localhost/.well-known/mercure
MERCURE_JWT_SECRET: !ChangeThisMercureHubJWTSecretKey!
```

## Volumes Docker

- **`caddy_data`** : Données persistantes de Caddy (certificats TLS)
- **`caddy_config`** : Configuration de Caddy
- **`database_data`** : Données PostgreSQL

### Sauvegarder les Données

```bash
# Backup PostgreSQL
docker compose exec database pg_dump -U app app > backup.sql

# Restaurer
docker compose exec -T database psql -U app app < backup.sql
```

## Résolution de Problèmes

### Le port 80/443 est déjà utilisé

Modifiez les ports dans `.env` :
```env
HTTP_PORT=8080
HTTPS_PORT=8443
```

### Erreur de permission

```bash
# Reconstruire avec les bonnes permissions
docker compose down -v
docker compose build --no-cache
docker compose up -d
```

### Reset complet

```bash
# Attention : supprime toutes les données
docker compose down -v
docker compose build --no-cache
docker compose up -d
```

### Logs de débogage

```bash
# Logs détaillés
docker compose logs -f --tail=100 php

# Logs Caddy
docker compose exec php cat /var/log/caddy/error.log
```

## Mise à Jour

### Mettre à Jour les Images

```bash
# Récupérer les dernières images
docker compose pull

# Reconstruire
docker compose build --pull

# Redémarrer
docker compose up -d
```

## Références

- [Documentation Symfony Docker](https://github.com/dunglas/symfony-docker)
- [FrankenPHP](https://frankenphp.dev/)
- [Caddy Server](https://caddyserver.com/)
- [Docker Compose](https://docs.docker.com/compose/)

## Support

Pour plus d'informations sur la configuration Docker Symfony, consultez :
- https://dunglas.dev/2021/12/symfonys-new-native-docker-support-symfony-world/
- https://github.com/dunglas/symfony-docker

