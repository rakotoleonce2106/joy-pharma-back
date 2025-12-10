# 🚢 Guide de Déploiement en Production

Ce guide décrit comment déployer Joy Pharma Backend en production avec Docker et FrankenPHP.

## 📋 Prérequis

### Infrastructure
- Serveur Linux (Ubuntu 20.04+ / Debian 11+ recommandé)
- **4 GB RAM minimum** (8 GB recommandé)
- **2 CPU cores minimum** (4 cores recommandé)
- **20 GB espace disque minimum**
- Docker Engine 20.10+
- Docker Compose V2.10+

### Domaine
- Nom de domaine configuré (ex: api.joypharma.com)
- DNS pointant vers votre serveur (record A)
- Port 80 et 443 ouverts

## 🔐 Gestion des Secrets avec Infisical

Ce projet utilise **Infisical** pour gérer les secrets de manière sécurisée en production.

Pour la configuration complète d'Infisical, consultez : **[docs/infisical.md](infisical.md)**

### Avantages d'Infisical

- ✅ Centralisation des secrets
- ✅ Synchronisation automatique
- ✅ Audit des accès
- ✅ Rotation facilitée
- ✅ Intégration CI/CD native

## 🔒 Préparation

### 1. Configuration du Serveur

```bash
# Mettre à jour le système
sudo apt update && sudo apt upgrade -y

# Installer Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sh get-docker.sh

# Ajouter l'utilisateur au groupe docker
sudo usermod -aG docker $USER
newgrp docker

# Vérifier l'installation
docker --version
docker compose version
```

### 2. Cloner le Projet

```bash
# Se connecter au serveur
ssh user@votre-serveur.com

# Cloner le repository
git clone https://github.com/votre-org/joy-pharma-back.git
cd joy-pharma-back

# Checkout de la branche de production
git checkout main
```

### 3. Configuration des Variables d'Environnement

Créez un fichier `.env.prod` :

```bash
cp .env.example .env.prod
```

Éditez `.env.prod` avec les valeurs de production :

```env
###> symfony/framework-bundle ###
APP_ENV=prod
APP_DEBUG=0
APP_SECRET=VOTRE_SECRET_VRAIMENT_LONG_ET_SECURISE_32_CARACTERES_MINIMUM
###< symfony/framework-bundle ###

###> doctrine/doctrine-bundle ###
DATABASE_URL=postgresql://app:MOT_DE_PASSE_SECURISE@database:5432/app?serverVersion=16&charset=utf8
###< doctrine/doctrine-bundle ###

###> lexik/jwt-authentication-bundle ###
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=VOTRE_PASSPHRASE_JWT_SECURISEE
JWT_TTL=3600
###< lexik/jwt-authentication-bundle ###

###> symfony/mailer ###
MAILER_DSN=smtp://user:pass@smtp.mailtrap.io:2525
###< symfony/mailer ###

###> nelmio/cors-bundle ###
CORS_ALLOW_ORIGIN='^https?://(www\.)?votre-domaine\.com$'
###< nelmio/cors-bundle ###

###> Docker configuration ###
POSTGRES_VERSION=16
POSTGRES_DB=app
POSTGRES_USER=app
POSTGRES_PASSWORD=MOT_DE_PASSE_POSTGRES_SECURISE
POSTGRES_PORT=5432

# Caddy configuration
SERVER_NAME=api.votre-domaine.com
HTTP_PORT=80
HTTPS_PORT=443
HTTP3_PORT=443

# Mercure configuration
CADDY_MERCURE_JWT_SECRET=VOTRE_SECRET_MERCURE_UNIQUE_ET_LONG
CADDY_MERCURE_URL=http://php/.well-known/mercure
CADDY_MERCURE_PUBLIC_URL=https://api.votre-domaine.com/.well-known/mercure

# XDebug (désactivé en production)
XDEBUG_MODE=off

# Elasticsearch
ELASTICSEARCH_VERSION=8.11.1
ELASTICSEARCH_URL=http://elasticsearch:9200
###< Docker configuration ###

###> FrankenPHP Worker ###
FRANKENPHP_WORKER_COUNT=auto
###< FrankenPHP Worker ###
```

**⚠️ IMPORTANT** : 
- Changez TOUS les mots de passe et secrets
- Utilisez des valeurs longues et aléatoires
- Ne commitez JAMAIS ce fichier dans Git

### 4. Sécuriser les Fichiers

```bash
# Restreindre les permissions du fichier .env.prod
chmod 600 .env.prod

# S'assurer que seul l'utilisateur peut lire le fichier
ls -la .env.prod
# Devrait afficher: -rw------- 1 user user
```

## 🏗️ Build et Déploiement

### 1. Build des Images

```bash
# Build avec le fichier de configuration production
docker compose -f compose.yaml -f compose.prod.yaml build --no-cache --pull
```

### 2. Générer les Clés JWT

```bash
# Créer le répertoire pour les clés
mkdir -p config/jwt

# Générer les clés
docker compose -f compose.yaml -f compose.prod.yaml run --rm php \
  bin/console lexik:jwt:generate-keypair --overwrite

# Sécuriser les permissions
chmod 600 config/jwt/*.pem
```

### 3. Démarrer les Services

```bash
# Charger les variables d'environnement
export $(cat .env.prod | grep -v '^#' | xargs)

# Démarrer les conteneurs
docker compose -f compose.yaml -f compose.prod.yaml up -d
```

### 4. Initialiser la Base de Données

```bash
# Attendre que PostgreSQL soit prêt
docker compose -f compose.yaml -f compose.prod.yaml exec database \
  pg_isready -U app

# Créer la base de données
docker compose -f compose.yaml -f compose.prod.yaml exec php \
  bin/console doctrine:database:create --if-not-exists

# Exécuter les migrations
docker compose -f compose.yaml -f compose.prod.yaml exec php \
  bin/console doctrine:migrations:migrate --no-interaction
```

### 5. Indexer Elasticsearch

```bash
# Réindexer les produits
docker compose -f compose.yaml -f compose.prod.yaml exec php \
  bin/console app:reindex-products
```

### 6. Créer un Utilisateur Admin

```bash
docker compose -f compose.yaml -f compose.prod.yaml exec php \
  bin/console app:create-admin-user
```

## ✅ Vérification

### 1. Vérifier les Services

```bash
# État des conteneurs
docker compose -f compose.yaml -f compose.prod.yaml ps

# Tous les services doivent être "Up" et "healthy"
```

### 2. Vérifier les Logs

```bash
# Logs de tous les services
docker compose -f compose.yaml -f compose.prod.yaml logs

# Logs PHP uniquement
docker compose -f compose.yaml -f compose.prod.yaml logs php

# Vérifier qu'il n'y a pas d'erreurs
```

### 3. Tests de Santé

```bash
# Test HTTPS
curl https://api.votre-domaine.com

# Test API
curl https://api.votre-domaine.com/api

# Test Documentation
curl https://api.votre-domaine.com/docs

# Test Mercure
curl https://api.votre-domaine.com/.well-known/mercure
```

### 4. Vérifier les Certificats SSL

```bash
# Vérifier le certificat Let's Encrypt
openssl s_client -connect api.votre-domaine.com:443 -servername api.votre-domaine.com < /dev/null | openssl x509 -noout -dates

# Vérifier l'expiration
curl -vI https://api.votre-domaine.com 2>&1 | grep -i expire
```

## 🔧 Configuration Avancée

### Performance FrankenPHP

Le fichier `compose.prod.yaml` active automatiquement le **Worker Mode** :

```yaml
environment:
  FRANKENPHP_CONFIG: "import worker.Caddyfile"
  FRANKENPHP_WORKER_COUNT: auto  # ou un nombre spécifique
```

**Recommandations** :
- `auto` : Détection automatique (recommandé)
- `2` : 2 workers (serveur 2 CPU)
- `4` : 4 workers (serveur 4 CPU)

### Optimisation PostgreSQL

Créez un fichier `docker/postgres/postgresql.conf` :

```ini
# Connexions
max_connections = 100

# Mémoire
shared_buffers = 256MB
effective_cache_size = 1GB
maintenance_work_mem = 64MB
work_mem = 16MB

# Checkpoints
checkpoint_completion_target = 0.9
wal_buffers = 16MB

# Query planning
random_page_cost = 1.1
effective_io_concurrency = 200
```

Montez-le dans `compose.prod.yaml` :

```yaml
database:
  volumes:
    - ./docker/postgres/postgresql.conf:/etc/postgresql/postgresql.conf:ro
  command: postgres -c config_file=/etc/postgresql/postgresql.conf
```

### Optimisation Elasticsearch

Dans `compose.prod.yaml` :

```yaml
elasticsearch:
  environment:
    - "ES_JAVA_OPTS=-Xms1g -Xmx1g"  # Ajuster selon RAM disponible
    - cluster.routing.allocation.disk.threshold_enabled=true
    - cluster.routing.allocation.disk.watermark.low=85%
    - cluster.routing.allocation.disk.watermark.high=90%
```

## 🔐 Sécurité

### 1. Firewall

```bash
# Installer UFW
sudo apt install ufw

# Règles de base
sudo ufw default deny incoming
sudo ufw default allow outgoing

# Autoriser SSH
sudo ufw allow 22/tcp

# Autoriser HTTP/HTTPS
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw allow 443/udp  # HTTP/3

# Activer le firewall
sudo ufw enable

# Vérifier le statut
sudo ufw status
```

### 2. Fail2Ban

```bash
# Installer Fail2Ban
sudo apt install fail2ban

# Créer une configuration pour Docker
sudo nano /etc/fail2ban/jail.local
```

Contenu de `jail.local` :

```ini
[DEFAULT]
bantime = 3600
findtime = 600
maxretry = 5

[sshd]
enabled = true

[nginx-http-auth]
enabled = true
```

### 3. Sauvegardes Automatiques

Créez un script de backup :

```bash
#!/bin/bash
# /home/user/backup.sh

BACKUP_DIR="/home/user/backups"
DATE=$(date +%Y%m%d_%H%M%S)
PROJECT_DIR="/home/user/joy-pharma-back"

cd $PROJECT_DIR

# Backup PostgreSQL
docker compose -f compose.yaml -f compose.prod.yaml exec -T database \
  pg_dump -U app app | gzip > "$BACKUP_DIR/db_$DATE.sql.gz"

# Backup des fichiers uploadés
tar -czf "$BACKUP_DIR/uploads_$DATE.tar.gz" public/images

# Nettoyer les backups de plus de 7 jours
find $BACKUP_DIR -name "*.gz" -mtime +7 -delete

echo "Backup completed: $DATE"
```

Ajoutez-le au crontab :

```bash
chmod +x /home/user/backup.sh

# Editer crontab
crontab -e

# Ajouter cette ligne (backup tous les jours à 2h du matin)
0 2 * * * /home/user/backup.sh >> /home/user/backup.log 2>&1
```

## 📊 Monitoring

### 1. Logs avec Journalisation

```bash
# Configurer les logs Docker
sudo nano /etc/docker/daemon.json
```

```json
{
  "log-driver": "json-file",
  "log-opts": {
    "max-size": "10m",
    "max-file": "3"
  }
}
```

```bash
sudo systemctl restart docker
```

### 2. Monitoring des Ressources

```bash
# Utilisation Docker
docker stats

# Espace disque
df -h

# Mémoire
free -h

# Processus
htop
```

### 3. Health Checks

Créez un script de monitoring :

```bash
#!/bin/bash
# /home/user/health-check.sh

# Vérifier l'API
if ! curl -sf https://api.votre-domaine.com/api > /dev/null; then
    echo "API DOWN!" | mail -s "API Health Alert" admin@votre-domaine.com
fi

# Vérifier PostgreSQL
if ! docker compose -f compose.yaml -f compose.prod.yaml exec -T database pg_isready -U app > /dev/null; then
    echo "PostgreSQL DOWN!" | mail -s "DB Health Alert" admin@votre-domaine.com
fi
```

Ajoutez au crontab (toutes les 5 minutes) :

```bash
*/5 * * * * /home/user/health-check.sh
```

## 🔄 Mise à Jour

### Déploiement d'une Nouvelle Version

```bash
# Se connecter au serveur
ssh user@votre-serveur.com
cd joy-pharma-back

# Récupérer les derniers changements
git pull origin main

# Rebuild les images
docker compose -f compose.yaml -f compose.prod.yaml build --no-cache

# Arrêter les anciens conteneurs
docker compose -f compose.yaml -f compose.prod.yaml down

# Démarrer les nouveaux
docker compose -f compose.yaml -f compose.prod.yaml up -d

# Exécuter les migrations
docker compose -f compose.yaml -f compose.prod.yaml exec php \
  bin/console doctrine:migrations:migrate --no-interaction

# Vider le cache
docker compose -f compose.yaml -f compose.prod.yaml exec php \
  bin/console cache:clear
```

### Rollback

```bash
# Revenir à la version précédente
git log --oneline  # Trouver le commit précédent
git checkout COMMIT_HASH

# Rebuild et redémarrer
docker compose -f compose.yaml -f compose.prod.yaml build --no-cache
docker compose -f compose.yaml -f compose.prod.yaml up -d

# Rollback des migrations si nécessaire
docker compose -f compose.yaml -f compose.prod.yaml exec php \
  bin/console doctrine:migrations:migrate prev --no-interaction
```

## 🆘 Troubleshooting Production

### Logs Détaillés

```bash
# Logs PHP en temps réel
docker compose -f compose.yaml -f compose.prod.yaml logs -f php

# Logs Caddy
docker compose -f compose.yaml -f compose.prod.yaml exec php cat /var/log/caddy/access.log
docker compose -f compose.yaml -f compose.prod.yaml exec php cat /var/log/caddy/error.log

# Logs Symfony
docker compose -f compose.yaml -f compose.prod.yaml exec php tail -f var/log/prod.log
```

### Redémarrer un Service

```bash
# Redémarrer PHP
docker compose -f compose.yaml -f compose.prod.yaml restart php

# Redémarrer PostgreSQL
docker compose -f compose.yaml -f compose.prod.yaml restart database

# Redémarrer tout
docker compose -f compose.yaml -f compose.prod.yaml restart
```

### Espace Disque Plein

```bash
# Nettoyer les images Docker inutilisées
docker system prune -a

# Nettoyer les volumes non utilisés
docker volume prune

# Nettoyer les logs Symfony
docker compose -f compose.yaml -f compose.prod.yaml exec php \
  rm -rf var/log/*.log
```

## 📱 CI/CD avec GitHub Actions

Voir le fichier `.github/workflows/deploy.yml` pour automatiser le déploiement.

## ✅ Checklist de Production

Avant de mettre en production, vérifiez :

- [ ] Variables d'environnement configurées (`.env.prod`)
- [ ] Secrets et mots de passe changés
- [ ] Clés JWT générées
- [ ] Certificats SSL valides
- [ ] Base de données migrée
- [ ] Elasticsearch indexé
- [ ] Firewall configuré
- [ ] Backups automatiques configurés
- [ ] Monitoring en place
- [ ] Health checks configurés
- [ ] CORS configuré pour votre domaine
- [ ] HTTPS fonctionnel
- [ ] API accessible
- [ ] Tests de charge effectués

## 🔗 Ressources

- [Docker Security Best Practices](https://docs.docker.com/engine/security/)
- [Symfony Production Best Practices](https://symfony.com/doc/current/deployment.html)
- [FrankenPHP Production](https://frankenphp.dev/docs/production/)
- [Let's Encrypt](https://letsencrypt.org/)

---

**Bon déploiement ! 🚀**

