# 🚀 Guide de Déploiement - Joy Pharma Backend

Guide complet pour déployer Joy Pharma Backend en production avec Docker, FrankenPHP et Infisical.

## 📋 Vue d'Ensemble

Le déploiement utilise :
- 🐳 **Docker** avec **FrankenPHP** pour l'application
- 🔐 **Infisical** pour la gestion des secrets
- 🤖 **GitHub Actions** pour le CI/CD automatique
- 🌐 **Traefik** (optionnel) pour le reverse proxy

## 🎯 Prérequis

### Sur le Serveur

- **OS** : Linux (Ubuntu 20.04+ / Debian 11+ recommandé)
- **RAM** : 4 GB minimum (8 GB recommandé)
- **CPU** : 2 cores minimum (4 cores recommandé)
- **Disque** : 20 GB minimum
- **Docker** : 20.10+
- **Docker Compose** : V2.10+
- **Infisical CLI** : Latest

### Sur GitHub

- **Repository** : Accès avec droits d'écriture
- **Secrets configurés** : Voir section [Configuration GitHub](#configuration-github)

### Sur Infisical

- **Compte créé** : [app.infisical.com](https://app.infisical.com)
- **Projet configuré** : Joy Pharma Backend
- **Machine Identity** : Pour GitHub Actions

## 🔐 Configuration Infisical

### 1. Créer le Projet

```bash
1. Connectez-vous à Infisical
2. Créez un nouveau projet : "Joy Pharma Backend"
3. Notez le PROJECT_ID
```

### 2. Configurer les Secrets

Ajoutez ces variables dans l'environnement `prod` :

```bash
# Application
APP_ENV=prod
APP_SECRET=votre_secret_unique_32_caracteres
APP_DEBUG=0

# Database
DATABASE_URL=postgresql://app:password@database:5432/app?serverVersion=16&charset=utf8
POSTGRES_PASSWORD=mot_de_passe_securise

# JWT
JWT_PASSPHRASE=votre_passphrase_jwt

# Caddy
SERVER_NAME=api.votre-domaine.com
CADDY_MERCURE_JWT_SECRET=secret_mercure_unique

# CORS
CORS_ALLOW_ORIGIN='^https?://(www\.)?votre-domaine\.com$'
```

**Documentation complète** : [docs/infisical.md](docs/infisical.md)

### 3. Créer une Machine Identity

```bash
1. Project Settings → Machine Identities
2. Create Identity : "GitHub Actions Deploy"
3. Type : Universal Auth
4. Permissions : Read/Write sur environment "prod"
5. Sauvegarder CLIENT_ID et CLIENT_SECRET
```

## ⚙️ Configuration GitHub

### Secrets à Configurer

Allez dans : `Settings` → `Secrets and variables` → `Actions`

```bash
# Infisical
INFISICAL_CLIENT_ID=xxxxx
INFISICAL_CLIENT_SECRET=xxxxx
INFISICAL_PROJECTID=xxxxx
INFISICAL_DOMAIN=https://app.infisical.com

# SSH Serveur
SSH_HOST=votre-serveur.com
SSH_USER=deploy
SSH_PRIVATE_KEY=votre_cle_privee_ssh
SSH_PORT=22

# Docker Hub
DOCKERHUB_USERNAME=your_username
DOCKERHUB_TOKEN=your_access_token

# Configuration
SERVER_NAME=api.votre-domaine.com
```

### Génération de la Clé SSH

```bash
# Sur votre machine locale
ssh-keygen -t ed25519 -C "github-actions-deploy" -f github-deploy

# Copier la clé publique sur le serveur
ssh-copy-id -i github-deploy.pub deploy@votre-serveur.com

# Copier la clé privée dans GitHub Secrets
cat github-deploy  # → SSH_PRIVATE_KEY
```

## 🖥️ Préparation du Serveur

### 1. Installer Docker

```bash
# Se connecter au serveur
ssh deploy@votre-serveur.com

# Installer Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh

# Ajouter l'utilisateur au groupe docker
sudo usermod -aG docker $USER
newgrp docker

# Vérifier
docker --version
docker compose version
```

### 2. Créer le Répertoire de Déploiement

```bash
# Créer le dossier
mkdir -p ~/joypharma
cd ~/joypharma

# Vérifier les permissions
ls -la
```

### 3. Configurer le Firewall (optionnel)

```bash
# Installer UFW
sudo apt install ufw

# Règles de base
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow 22/tcp   # SSH
sudo ufw allow 80/tcp   # HTTP
sudo ufw allow 443/tcp  # HTTPS
sudo ufw allow 443/udp  # HTTP/3

# Activer
sudo ufw enable
```

## 🚀 Déploiement

### Déploiement Automatique

Le déploiement est automatique sur push vers `preprod` :

```bash
# Sur votre machine locale
git checkout preprod
git merge develop
git push origin preprod

# GitHub Actions se charge du reste !
```

### Déploiement Manuel via GitHub

```bash
1. Allez sur GitHub → Actions
2. Sélectionnez "Deploy to Production"
3. Click "Run workflow"
4. Sélectionnez la branche : preprod
5. Click "Run workflow"
```

### Déploiement Manuel via SSH

```bash
# Se connecter au serveur
ssh deploy@votre-serveur.com
cd ~/joypharma

# 1. Installer Infisical CLI (si nécessaire)
if ! command -v infisical &> /dev/null; then
    curl -1sLf 'https://dl.cloudsmith.io/public/infisical/infisical-cli/setup.deb.sh' | sudo -E bash
    sudo apt-get update && sudo apt-get install -y infisical
fi

# 2. Se connecter à Infisical
infisical login --method=universal-auth \
  --client-id="YOUR_CLIENT_ID" \
  --client-secret="YOUR_CLIENT_SECRET" \
  --domain="https://app.infisical.com"

# 3. Exporter les secrets
infisical export --env=prod --format=dotenv > .env

# 4. Ajouter les variables Docker
echo "IMAGES_PREFIX=votreuser/" >> .env
echo "SERVER_NAME=api.votre-domaine.com" >> .env

# 5. Pull et déployer
docker compose -f compose.yaml -f compose.prod.yaml pull
docker compose -f compose.yaml -f compose.prod.yaml up -d

# 6. Exécuter les migrations
docker compose -f compose.yaml -f compose.prod.yaml exec php \
  bin/console doctrine:migrations:migrate --no-interaction

# 7. Vérifier
docker compose -f compose.yaml -f compose.prod.yaml ps
```

## 📊 Workflows GitHub Actions

### Fichiers de Workflow

```
.github/workflows/
├── deploy.yml            # Workflow principal
├── deploy-build.yml      # Build et push Docker
├── deploy-server.yml     # Déploiement serveur
├── deploy-env.yml        # Préparation environnement
└── docker.yml            # CI Docker (tests)
```

### Processus de Déploiement

```mermaid
graph LR
    A[Push to preprod] --> B[Build Image]
    B --> C[Push to Docker Hub]
    C --> D[Copy Files to Server]
    D --> E[Install Infisical CLI]
    E --> F[Export Secrets to .env]
    F --> G[Pull New Image]
    G --> H[Start Containers]
    H --> I[Run Migrations]
    I --> J[Health Checks]
    J --> K[Deployment Success]
```

### Étapes Détaillées

1. **Build** (deploy-build.yml)
   - Checkout du code
   - Setup Docker Buildx
   - Login Docker Hub
   - Build image avec target `frankenphp_prod`
   - Push vers Docker Hub avec tags

2. **Deploy** (deploy-server.yml)
   - Copie des fichiers de configuration
   - Installation d'Infisical CLI
   - Connexion à Infisical
   - Export des secrets vers `.env`
   - Pull de la nouvelle image
   - Backup du conteneur actuel
   - Démarrage des nouveaux conteneurs
   - Vérification de la santé
   - Exécution des migrations
   - Nettoyage des anciennes images

## ✅ Vérification du Déploiement

### 1. Vérifier les Services

```bash
# État des conteneurs
docker compose -f compose.yaml -f compose.prod.yaml ps

# Résultat attendu :
# NAME       STATUS         PORTS
# php        Up (healthy)   0.0.0.0:80->80/tcp, 0.0.0.0:443->443/tcp
# database   Up (healthy)   5432/tcp
# elasticsearch Up (healthy) 9200/tcp
```

### 2. Vérifier les Logs

```bash
# Logs de tous les services
docker compose -f compose.yaml -f compose.prod.yaml logs

# Logs PHP uniquement
docker compose -f compose.yaml -f compose.prod.yaml logs php

# Suivre les logs en temps réel
docker compose -f compose.yaml -f compose.prod.yaml logs -f
```

### 3. Tester l'Application

```bash
# Test HTTPS
curl https://api.votre-domaine.com

# Test API
curl https://api.votre-domaine.com/api

# Test Documentation
curl https://api.votre-domaine.com/docs
```

### 4. Vérifier le Certificat SSL

```bash
# Vérifier l'émission Let's Encrypt
openssl s_client -connect api.votre-domaine.com:443 \
  -servername api.votre-domaine.com < /dev/null \
  | openssl x509 -noout -dates

# Vérifier dans le navigateur
# Le cadenas doit être vert
```

## 🔄 Mises à Jour

### Mise à Jour Automatique

```bash
# Sur votre machine locale
git checkout preprod
git merge develop  # ou main
git push origin preprod

# GitHub Actions déploie automatiquement
```

### Mise à Jour Manuelle

```bash
# Sur le serveur
cd ~/joypharma

# Pull nouvelle image
docker compose -f compose.yaml -f compose.prod.yaml pull

# Redémarrer
docker compose -f compose.yaml -f compose.prod.yaml up -d

# Migrations
docker compose -f compose.yaml -f compose.prod.yaml exec php \
  bin/console doctrine:migrations:migrate --no-interaction
```

## 🔙 Rollback

### Rollback Automatique

Le workflow GitHub Actions effectue un rollback automatique si :
- Le conteneur ne démarre pas
- Le conteneur redémarre en boucle
- Les health checks échouent

### Rollback Manuel

```bash
# Sur le serveur
cd ~/joypharma

# Arrêter les conteneurs actuels
docker compose -f compose.yaml -f compose.prod.yaml down

# Lister les images disponibles
docker images | grep joy-pharma-back

# Modifier .env pour utiliser l'ancienne image
echo "IMAGES_PREFIX=youruser/" > .env.backup
echo "IMAGE_TAG=previous-tag" >> .env.backup

# Redémarrer avec l'ancienne version
docker compose -f compose.yaml -f compose.prod.yaml --env-file .env.backup up -d
```

## 🐛 Troubleshooting

### Erreur : "Failed to generate Infisical token"

```bash
# Vérifier les credentials
infisical login --method=universal-auth \
  --client-id="YOUR_CLIENT_ID" \
  --client-secret="YOUR_CLIENT_SECRET"
```

### Erreur : "Container keeps restarting"

```bash
# Voir les logs du conteneur
docker compose -f compose.yaml -f compose.prod.yaml logs php

# Vérifier le .env
cat .env | grep -v "PASSWORD\|SECRET"

# Vérifier la configuration
docker compose -f compose.yaml -f compose.prod.yaml config
```

### Erreur : "Database connection failed"

```bash
# Vérifier que PostgreSQL est démarré
docker compose -f compose.yaml -f compose.prod.yaml ps database

# Tester la connexion
docker compose -f compose.yaml -f compose.prod.yaml exec database \
  pg_isready -U app

# Vérifier DATABASE_URL
docker compose -f compose.yaml -f compose.prod.yaml exec php \
  bin/console debug:container --env-var=DATABASE_URL
```

### Erreur : "SSL Certificate not valid"

```bash
# Redémarrer Caddy pour regénérer le certificat
docker compose -f compose.yaml -f compose.prod.yaml restart php

# Vérifier les logs Caddy
docker compose -f compose.yaml -f compose.prod.yaml logs php | grep -i caddy
```

## 📊 Monitoring

### Logs en Production

```bash
# Logs en temps réel
docker compose -f compose.yaml -f compose.prod.yaml logs -f --tail=100

# Logs d'erreur uniquement
docker compose -f compose.yaml -f compose.prod.yaml logs | grep -i error

# Logs Symfony
docker compose -f compose.yaml -f compose.prod.yaml exec php \
  tail -f var/log/prod.log
```

### Métriques de Performance

```bash
# Utilisation CPU/RAM
docker stats

# Espace disque
df -h

# Logs d'accès Caddy (dans le conteneur)
docker compose -f compose.yaml -f compose.prod.yaml exec php \
  cat /var/log/caddy/access.log
```

## 🔐 Sécurité

### Bonnes Pratiques

1. **Secrets** : Toujours via Infisical, jamais en clair
2. **SSH** : Clés uniquement, pas de mot de passe
3. **Firewall** : UFW configuré et actif
4. **Updates** : Système et Docker régulièrement mis à jour
5. **Backups** : Automatiques et testés
6. **Monitoring** : Logs et alertes configurés

### Audit de Sécurité

```bash
# Vérifier les ports ouverts
sudo netstat -tulpn | grep LISTEN

# Vérifier les règles firewall
sudo ufw status verbose

# Vérifier les utilisateurs Docker
getent group docker
```

## 📚 Documentation Complémentaire

- 📥 [INSTALLATION.md](INSTALLATION.md) - Installation locale
- 🐳 [DOCKER.md](DOCKER.md) - Documentation Docker
- 🚢 [docs/production.md](docs/production.md) - Guide production détaillé
- 🔐 [docs/infisical.md](docs/infisical.md) - Configuration Infisical

## 🆘 Support

### Besoin d'Aide ?

1. Consultez la documentation complète
2. Vérifiez les logs : `docker compose logs`
3. Ouvrez une issue sur GitHub
4. Contactez l'équipe DevOps

---

**Bon déploiement ! 🚀**

