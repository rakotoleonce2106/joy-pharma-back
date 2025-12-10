# 🔐 Configuration Infisical pour Joy Pharma Backend

Ce guide explique comment configurer Infisical pour gérer les secrets de votre application de manière sécurisée en production.

## 📋 Table des Matières

- [Qu'est-ce qu'Infisical ?](#quest-ce-quinfisical-)
- [Configuration Initiale](#configuration-initiale)
- [Variables d'Environnement Requises](#variables-denvironnement-requises)
- [Configuration GitHub Actions](#configuration-github-actions)
- [Déploiement avec Infisical](#déploiement-avec-infisical)
- [Bonnes Pratiques](#bonnes-pratiques)
- [Troubleshooting](#troubleshooting)

## Qu'est-ce qu'Infisical ?

[Infisical](https://infisical.com/) est un gestionnaire de secrets open-source qui permet de :
- ✅ Centraliser tous vos secrets
- ✅ Gérer différents environnements (dev, staging, prod)
- ✅ Synchroniser automatiquement les secrets
- ✅ Auditer les accès aux secrets
- ✅ Intégrer facilement avec CI/CD

## Configuration Initiale

### 1. Créer un Compte Infisical

1. Allez sur [Infisical Cloud](https://app.infisical.com/) ou hébergez votre propre instance
2. Créez un compte
3. Créez une nouvelle organisation

### 2. Créer un Projet

```bash
# Dans le dashboard Infisical
1. Cliquez sur "New Project"
2. Nom du projet : "Joy Pharma Backend"
3. Créez le projet
```

### 3. Configurer les Environnements

Infisical supporte plusieurs environnements par défaut :
- `dev` - Développement
- `staging` - Staging/Preprod
- `prod` - Production

### 4. Créer une Machine Identity (Service Account)

Pour permettre à GitHub Actions d'accéder aux secrets :

```bash
# Dans Infisical Dashboard
1. Allez dans "Project Settings" → "Machine Identities"
2. Cliquez sur "Create Identity"
3. Nom : "GitHub Actions Deploy"
4. Sélectionnez "Universal Auth"
5. Notez le CLIENT_ID et CLIENT_SECRET
```

**⚠️ Important** : Sauvegardez immédiatement le `CLIENT_SECRET`, il ne sera plus visible après.

## Variables d'Environnement Requises

### Variables Docker/Infrastructure

Ajoutez ces variables dans Infisical (environnement `prod`) :

```bash
# Application Symfony
APP_ENV=prod
APP_SECRET=votre_secret_unique_32_caracteres
APP_DEBUG=0

# Database
DATABASE_URL=postgresql://user:password@database:5432/app?serverVersion=16&charset=utf8
POSTGRES_VERSION=16
POSTGRES_DB=app
POSTGRES_USER=app
POSTGRES_PASSWORD=mot_de_passe_securise

# JWT Authentication
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=votre_passphrase_jwt
JWT_TTL=3600

# Caddy/FrankenPHP
SERVER_NAME=api.votre-domaine.com
HTTP_PORT=80
HTTPS_PORT=443
HTTP3_PORT=443

# Mercure
CADDY_MERCURE_JWT_SECRET=secret_mercure_unique
CADDY_MERCURE_URL=http://php/.well-known/mercure
CADDY_MERCURE_PUBLIC_URL=https://api.votre-domaine.com/.well-known/mercure

# Elasticsearch
ELASTICSEARCH_VERSION=8.11.1
ELASTICSEARCH_URL=http://elasticsearch:9200

# CORS
CORS_ALLOW_ORIGIN='^https?://(www\.)?votre-domaine\.com$'

# Mailer
MAILER_DSN=smtp://user:pass@smtp.example.com:587

# FrankenPHP Worker
FRANKENPHP_WORKER_COUNT=auto
```

### Configuration dans Infisical

1. **Via l'interface web** :
   ```bash
   1. Sélectionnez l'environnement "prod"
   2. Cliquez sur "Add Secret"
   3. Key : APP_ENV
   4. Value : prod
   5. Répétez pour chaque variable
   ```

2. **Via Infisical CLI** :
   ```bash
   # Installer le CLI
   brew install infisical/get-cli/infisical
   
   # Se connecter
   infisical login
   
   # Ajouter des secrets
   infisical secrets set APP_ENV prod --env=prod
   infisical secrets set APP_SECRET "votre_secret_unique" --env=prod
   ```

## Configuration GitHub Actions

### Secrets GitHub à Configurer

Dans GitHub : `Settings` → `Secrets and variables` → `Actions` → `New repository secret`

```bash
# Infisical
INFISICAL_CLIENT_ID=your_client_id
INFISICAL_CLIENT_SECRET=your_client_secret
INFISICAL_PROJECTID=your_project_id
INFISICAL_DOMAIN=https://app.infisical.com  # ou votre instance

# SSH pour déploiement
SSH_HOST=votre-serveur.com
SSH_USER=deploy
SSH_PRIVATE_KEY=your_private_key
SSH_PORT=22

# Docker Hub
DOCKERHUB_USERNAME=your_username
DOCKERHUB_TOKEN=your_token

# Configuration
SERVER_NAME=api.votre-domaine.com
```

### Workflow de Déploiement

Les workflows sont déjà configurés dans `.github/workflows/` :

- **`deploy-build.yml`** : Build et push de l'image Docker
- **`deploy-server.yml`** : Déploiement sur le serveur avec Infisical
- **`deploy-env.yml`** : Copie des fichiers de configuration
- **`deploy.yml`** : Workflow principal orchestrant le déploiement

## Déploiement avec Infisical

### Processus de Déploiement

Le déploiement automatique se déclenche :
- Sur push vers la branche `preprod`
- Ou manuellement via GitHub Actions

### Étapes du Déploiement

1. **Build** : Construction de l'image Docker
   ```yaml
   - Build de l'image avec le target `frankenphp_prod`
   - Push vers Docker Hub
   - Tag avec le nom de la branche
   ```

2. **Préparation** :
   ```bash
   - Copie de compose.yaml et compose.prod.yaml
   - Copie du dossier frankenphp/
   - Installation d'Infisical CLI sur le serveur
   ```

3. **Déploiement** :
   ```bash
   - Connexion à Infisical
   - Export des secrets vers .env
   - Pull de la nouvelle image
   - Démarrage des conteneurs
   - Exécution des migrations
   - Vérification de la santé
   ```

### Commandes Manuelles

Pour déployer manuellement :

```bash
# Via GitHub Actions
1. Allez dans "Actions"
2. Sélectionnez "Deploy to Production"
3. Cliquez sur "Run workflow"
4. Sélectionnez la branche
5. Cliquez sur "Run workflow"

# Via SSH sur le serveur
cd joypharma

# Installer Infisical CLI (si nécessaire)
curl -1sLf 'https://dl.cloudsmith.io/public/infisical/infisical-cli/setup.deb.sh' | sudo -E bash
sudo apt-get update && sudo apt-get install -y infisical

# Se connecter à Infisical
infisical login --method=universal-auth \
  --client-id="YOUR_CLIENT_ID" \
  --client-secret="YOUR_CLIENT_SECRET" \
  --domain="https://app.infisical.com"

# Exporter les secrets
infisical export --env=prod --format=dotenv > .env

# Ajouter les variables Docker
echo "IMAGES_PREFIX=youruser/" >> .env
echo "TRAEFIK_NETWORK=traefik_default" >> .env

# Déployer
docker compose -f compose.yaml -f compose.prod.yaml pull
docker compose -f compose.yaml -f compose.prod.yaml up -d

# Vérifier
docker compose -f compose.yaml -f compose.prod.yaml ps
```

## Bonnes Pratiques

### Sécurité

1. **Ne jamais commiter de secrets**
   ```bash
   # .gitignore contient déjà
   .env
   .env.*
   !.env.example
   ```

2. **Rotation des secrets**
   ```bash
   - Changez les secrets régulièrement
   - Utilisez des mots de passe forts
   - Activez l'audit dans Infisical
   ```

3. **Accès limité**
   ```bash
   - Donnez l'accès minimal nécessaire
   - Utilisez des Machine Identities séparées par environnement
   - Révoquez les accès inutilisés
   ```

### Organisation

1. **Nommage cohérent**
   ```bash
   # Préfixer par le service
   DATABASE_URL
   DATABASE_PASSWORD
   
   # Grouper par fonctionnalité
   JWT_SECRET_KEY
   JWT_PUBLIC_KEY
   JWT_PASSPHRASE
   ```

2. **Documentation**
   ```bash
   # Ajouter des descriptions dans Infisical
   APP_SECRET: "Secret key for Symfony framework"
   DATABASE_URL: "PostgreSQL connection string"
   ```

3. **Environnements**
   ```bash
   # Utilisez des valeurs différentes par environnement
   dev    : APP_DEBUG=1
   staging: APP_DEBUG=0
   prod   : APP_DEBUG=0
   ```

### Backup

```bash
# Exporter tous les secrets pour backup
infisical export --env=prod --format=dotenv > backup-secrets-$(date +%Y%m%d).env

# Stocker le backup de manière sécurisée
# NE PAS le commiter dans Git !
gpg -c backup-secrets-$(date +%Y%m%d).env
```

## Troubleshooting

### Erreur : "Failed to generate Infisical token"

**Cause** : Mauvaises credentials ou problème de connexion

**Solution** :
```bash
# Vérifier les credentials
echo $INFISICAL_CLIENT_ID
echo $INFISICAL_CLIENT_SECRET

# Tester la connexion
curl -X POST https://app.infisical.com/api/v1/auth/universal-auth/login \
  -H "Content-Type: application/json" \
  -d "{\"clientId\":\"$INFISICAL_CLIENT_ID\",\"clientSecret\":\"$INFISICAL_CLIENT_SECRET\"}"
```

### Erreur : ".env file is empty"

**Cause** : Aucun secret défini pour l'environnement

**Solution** :
```bash
# Vérifier les secrets dans Infisical
infisical secrets list --env=prod

# Ajouter des secrets manquants
infisical secrets set APP_ENV prod --env=prod
```

### Erreur : "Permission denied"

**Cause** : Machine Identity n'a pas les permissions

**Solution** :
```bash
# Dans Infisical Dashboard
1. Allez dans "Project Settings" → "Machine Identities"
2. Sélectionnez votre identity
3. Vérifiez les permissions (Read/Write pour prod)
4. Sauvegardez
```

### Les secrets ne se mettent pas à jour

**Cause** : Cache ou .env non regénéré

**Solution** :
```bash
# Sur le serveur
cd joypharma

# Supprimer l'ancien .env
rm .env

# Regénérer
infisical export --env=prod --format=dotenv > .env

# Redémarrer les conteneurs
docker compose -f compose.yaml -f compose.prod.yaml restart
```

### Déploiement échoue : "Database connection failed"

**Cause** : DATABASE_URL mal configuré

**Solution** :
```bash
# Vérifier DATABASE_URL dans Infisical
# Format correct :
DATABASE_URL=postgresql://user:password@database:5432/dbname?serverVersion=16&charset=utf8

# Note: "database" est le nom du service dans compose.yaml
```

## Migration depuis .env

Si vous migrez depuis des fichiers `.env` classiques :

```bash
# 1. Lister vos variables actuelles
cat .env | grep -v '^#' | grep -v '^$'

# 2. Les importer dans Infisical
while IFS='=' read -r key value; do
  if [ ! -z "$key" ]; then
    infisical secrets set "$key" "$value" --env=prod
  fi
done < .env

# 3. Vérifier l'import
infisical secrets list --env=prod

# 4. Supprimer le .env local (après backup !)
mv .env .env.backup
```

## Ressources

### Documentation Officielle
- [Infisical Docs](https://infisical.com/docs)
- [Infisical CLI](https://infisical.com/docs/cli/overview)
- [Universal Auth](https://infisical.com/docs/documentation/platform/identities/universal-auth)

### Liens Utiles
- [Infisical GitHub](https://github.com/Infisical/infisical)
- [Infisical Discord](https://infisical.com/discord)

## Exemple Complet

### 1. Configuration Initiale

```bash
# Sur votre machine locale

# Installer Infisical CLI
brew install infisical/get-cli/infisical  # macOS
# ou
curl -1sLf 'https://dl.cloudsmith.io/public/infisical/infisical-cli/setup.deb.sh' | sudo -E bash
sudo apt-get update && sudo apt-get install -y infisical  # Linux

# Se connecter
infisical login

# Créer le projet et ajouter les secrets
infisical secrets set APP_ENV prod --env=prod
infisical secrets set APP_SECRET "$(openssl rand -base64 32)" --env=prod
infisical secrets set DATABASE_PASSWORD "$(openssl rand -base64 32)" --env=prod
# ... etc pour tous les secrets
```

### 2. Configuration GitHub

```bash
# Créer une Machine Identity dans Infisical
# puis ajouter dans GitHub Secrets :

INFISICAL_CLIENT_ID=xxxxx
INFISICAL_CLIENT_SECRET=xxxxx
INFISICAL_PROJECTID=xxxxx
INFISICAL_DOMAIN=https://app.infisical.com
```

### 3. Déploiement

```bash
# Push vers preprod déclenche le déploiement automatique
git push origin preprod

# Ou déploiement manuel via GitHub Actions
```

---

**🔐 Gardez vos secrets en sécurité avec Infisical ! 🔐**

