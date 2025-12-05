# Guide de Déploiement - back-preprod.joy-pharma.com

Ce guide explique comment configurer le déploiement du projet pour pointer vers `back-preprod.joy-pharma.com`.

## Configuration du domaine

### Variables d'environnement requises

Pour le déploiement en production, vous devez définir les variables d'environnement suivantes :

```bash
# Domaine principal
SERVER_NAME=back-preprod.joy-pharma.com

# URLs Mercure
MERCURE_PUBLIC_URL=https://back-preprod.joy-pharma.com/.well-known/mercure

# Configuration n8n
N8N_HOST=back-preprod.joy-pharma.com
N8N_PROTOCOL=https

# Autres variables de production
APP_ENV=prod
APP_SECRET=<votre-secret-app>
CADDY_MERCURE_JWT_SECRET=<votre-secret-mercure>
DATABASE_URL=postgresql://user:password@host:5432/dbname
```

## Configuration via dotenv-vault

1. Ajoutez ces variables dans votre fichier `.env` local
2. Poussez-les vers dotenv-vault :
   ```bash
   dotenv-vault push
   dotenv-vault open production
   ```
3. Configurez les valeurs pour l'environnement `production`
4. Générez le vault :
   ```bash
   dotenv-vault build
   ```

## Déploiement avec Docker Compose

### Option 1: Utiliser compose.prod.yaml

```bash
# Créez un fichier .env.production avec vos variables
cp .env.example .env.production

# Définissez SERVER_NAME dans .env.production
echo "SERVER_NAME=back-preprod.joy-pharma.com" >> .env.production

# Lancez avec le fichier de production
docker compose -f compose.yaml -f compose.prod.yaml --env-file .env.production up -d
```

### Option 2: Variables d'environnement directes

```bash
SERVER_NAME=back-preprod.joy-pharma.com \
MERCURE_PUBLIC_URL=https://back-preprod.joy-pharma.com/.well-known/mercure \
docker compose -f compose.yaml -f compose.prod.yaml up -d
```

## Configuration DNS

Assurez-vous que votre DNS pointe vers votre serveur :

```
A     back-preprod.joy-pharma.com    ->  <votre-ip-serveur>
```

## Certificat SSL/TLS

Caddy (via FrankenPHP) génère automatiquement des certificats SSL via Let's Encrypt pour `back-preprod.joy-pharma.com` si :
- Le port 80 et 443 sont accessibles depuis l'extérieur
- Le domaine pointe correctement vers votre serveur
- Caddy peut valider le domaine

### Vérification du certificat

Après le démarrage, vérifiez que le certificat est généré :
```bash
docker compose exec php curl -I https://back-preprod.joy-pharma.com
```

## Configuration CORS

La configuration CORS est déjà mise à jour dans `config/packages/nelmio_cors.yaml` pour autoriser :
- `https://back-preprod.joy-pharma.com`
- `https://www.joy-pharma.com`
- `https://joy-pharma.com`
- Tous les sous-domaines de `joy-pharma.com`

## Déploiement via GitHub Actions

Le workflow de déploiement (`.github/workflows/deploy.yml`) construit, pousse l'image Docker vers Docker Hub et **déploie automatiquement** sur le serveur.

### Configuration des secrets GitHub

Pour que le déploiement automatique fonctionne, vous devez configurer les secrets suivants dans GitHub (Settings > Secrets and variables > Actions) :

#### Secrets requis pour le build et push :

- `DOCKERHUB_USERNAME` : Votre nom d'utilisateur Docker Hub
- `DOCKERHUB_TOKEN` : Votre token d'accès Docker Hub (généré dans Docker Hub > Account Settings > Security)
- `DOTENV_KEY` : La clé de déchiffrement dotenv-vault pour l'environnement de production (obtenue avec `dotenv-vault keys production`)

#### Secrets requis pour le déploiement SSH :

- `SSH_HOST` : L'adresse IP ou le nom d'hôte de votre serveur de production
- `SSH_USER` : Le nom d'utilisateur SSH pour se connecter au serveur
- `SSH_PRIVATE_KEY` : La clé privée SSH pour l'authentification (sans mot de passe recommandé)
- `SSH_PORT` : (Optionnel) Le port SSH, par défaut 22
- `SSH_DEPLOY_PATH` : (Optionnel) Le chemin du répertoire de déploiement sur le serveur, par défaut `~/joy-pharma-back`

### Génération de la clé SSH

Pour générer une paire de clés SSH sans mot de passe :

```bash
# Générer une nouvelle paire de clés
ssh-keygen -t ed25519 -C "github-actions-deploy" -f ~/.ssh/github_actions_deploy -N ""

# Afficher la clé privée (à copier dans le secret SSH_PRIVATE_KEY)
cat ~/.ssh/github_actions_deploy

# Afficher la clé publique (à ajouter sur le serveur)
cat ~/.ssh/github_actions_deploy.pub
```

Sur le serveur, ajoutez la clé publique au fichier `~/.ssh/authorized_keys` :

```bash
# Sur le serveur
echo "VOTRE_CLE_PUBLIQUE" >> ~/.ssh/authorized_keys
chmod 600 ~/.ssh/authorized_keys
```

### Préparation du serveur

1. **Installer Docker et Docker Compose** sur le serveur :
   ```bash
   # Installation Docker (exemple pour Ubuntu/Debian)
   curl -fsSL https://get.docker.com -o get-docker.sh
   sudo sh get-docker.sh
   sudo usermod -aG docker $USER
   ```

2. **Créer le répertoire de déploiement** :
   ```bash
   mkdir -p ~/joy-pharma-back
   cd ~/joy-pharma-back
   ```

3. **Copier les fichiers de configuration** :
   ```bash
   # Copier compose.yaml et compose.prod.yaml depuis le repository
   # Vous pouvez cloner le repo ou copier manuellement ces fichiers
   ```

4. **Créer le fichier `.env` sur le serveur** avec toutes les variables d'environnement nécessaires (voir section "Variables d'environnement requises" ci-dessus)

### Étapes de déploiement automatique

1. **Push vers la branche `preprod`** déclenche automatiquement :
   - Le build de l'image Docker
   - Le push vers Docker Hub avec le tag `preprod`
   - La connexion SSH au serveur
   - Le pull de la nouvelle image
   - Le redémarrage des services avec `docker compose`

2. Le workflow se connecte automatiquement au serveur et exécute :
   ```bash
   docker login
   docker pull <username>/joy-pharma-back:preprod
   docker compose -f compose.yaml -f compose.prod.yaml pull php
   docker compose -f compose.yaml -f compose.prod.yaml up -d --no-deps php
   ```

### Déploiement manuel (si nécessaire)

Si vous devez déployer manuellement sur le serveur :

```bash
# Se connecter au serveur
ssh user@server

# Aller dans le répertoire de déploiement
cd ~/joy-pharma-back

# Se connecter à Docker Hub
docker login

# Récupérer la dernière image
docker pull <votre-username>/joy-pharma-back:preprod

# Mettre à jour les services
export IMAGES_PREFIX="<votre-username>/"
export IMAGE_TAG="preprod"
docker compose -f compose.yaml -f compose.prod.yaml pull php
docker compose -f compose.yaml -f compose.prod.yaml up -d --no-deps php
```

## Vérification du déploiement

1. **Vérifier que le service répond** :
   ```bash
   curl https://back-preprod.joy-pharma.com
   ```

2. **Vérifier l'API** :
   ```bash
   curl https://back-preprod.joy-pharma.com/api
   ```

3. **Vérifier Mercure** :
   ```bash
   curl https://back-preprod.joy-pharma.com/.well-known/mercure
   ```

## Troubleshooting

### Le certificat SSL ne se génère pas

- Vérifiez que les ports 80 et 443 sont ouverts dans votre firewall
- Vérifiez que le DNS pointe correctement
- Consultez les logs Caddy : `docker compose logs php`

### Erreurs CORS

- Vérifiez que votre frontend utilise bien `https://back-preprod.joy-pharma.com`
- Vérifiez la configuration dans `config/packages/nelmio_cors.yaml`

### Variables d'environnement non chargées

- Vérifiez que `DOTENV_KEY` est défini correctement
- Vérifiez que le fichier `.env.vault` est présent dans l'image Docker
- Consultez les logs : `docker compose logs php`

## Support

Pour plus d'informations, consultez :
- [Documentation Caddy](https://caddyserver.com/docs/)
- [Documentation FrankenPHP](https://frankenphp.dev/)
- [Documentation Symfony](https://symfony.com/doc/current/index.html)

