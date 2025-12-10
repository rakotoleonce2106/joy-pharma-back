# 📥 Guide d'Installation - Joy Pharma Backend

Guide complet pour installer et configurer Joy Pharma Backend avec Docker.

## 🎯 Méthode Rapide (Recommandée)

### Pour les Débutants

```bash
# 1. Cloner le projet
git clone https://github.com/votre-org/joy-pharma-back.git
cd joy-pharma-back

# 2. Lancer le script d'installation
./start.sh

# 3. Ouvrir https://localhost dans votre navigateur
```

C'est tout ! ✨

## 📋 Prérequis

### Logiciels Requis

| Logiciel | Version Minimum | Lien de Téléchargement |
|----------|----------------|------------------------|
| Docker Desktop | 20.10+ | [Télécharger](https://docs.docker.com/get-docker/) |
| Git | 2.x | [Télécharger](https://git-scm.com/downloads) |

### Configuration Minimale

- **RAM** : 4 GB minimum (8 GB recommandé)
- **Espace Disque** : 10 GB minimum
- **Processeur** : 2 cores minimum
- **OS** : macOS 10.15+, Windows 10+, Linux

## 🚀 Installation Détaillée

### Étape 1 : Installer Docker

#### macOS

```bash
# Télécharger Docker Desktop
open https://docs.docker.com/desktop/install/mac-install/

# Ou avec Homebrew
brew install --cask docker
```

#### Windows

```bash
# Télécharger Docker Desktop
# https://docs.docker.com/desktop/install/windows-install/

# Installer WSL2 si nécessaire
wsl --install
```

#### Linux (Ubuntu/Debian)

```bash
# Installer Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh

# Ajouter votre utilisateur au groupe docker
sudo usermod -aG docker $USER
newgrp docker

# Vérifier l'installation
docker --version
docker compose version
```

### Étape 2 : Cloner le Projet

```bash
# Via HTTPS
git clone https://github.com/votre-org/joy-pharma-back.git

# Ou via SSH (si configuré)
git clone git@github.com:votre-org/joy-pharma-back.git

# Accéder au répertoire
cd joy-pharma-back
```

### Étape 3 : Configuration

#### Option A : Configuration Automatique (Recommandé)

```bash
# Le script start.sh copie automatiquement .env.example vers .env
./start.sh
```

#### Option B : Configuration Manuelle

```bash
# Copier le fichier d'exemple
cp .env.example .env

# Éditer le fichier .env
nano .env  # ou vim, code, etc.
```

Modifiez au minimum ces valeurs dans `.env` :

```env
# Secret de l'application (générez-en un unique)
APP_SECRET=ChangezCeciParUnSecretUnique32Caracteres

# Mot de passe PostgreSQL
POSTGRES_PASSWORD=ChangezCeMotDePasse

# Passphrase JWT (générez-en une unique)
JWT_PASSPHRASE=votre_passphrase_securisee

# Secret Mercure
CADDY_MERCURE_JWT_SECRET=ChangezCeSecretMercure
```

**💡 Astuce** : Pour générer des secrets sécurisés :

```bash
# Sur Linux/macOS
openssl rand -base64 32

# Ou avec PHP
php -r "echo bin2hex(random_bytes(32));"
```

### Étape 4 : Démarrer l'Application

#### Méthode 1 : Script Interactif (Plus Simple)

```bash
./start.sh
```

Le script vous guidera et :
- ✅ Vérifiera que Docker est installé
- ✅ Construira les images Docker
- ✅ Démarrera les conteneurs
- ✅ Créera la base de données
- ✅ Exécutera les migrations
- ✅ Générera les clés JWT

#### Méthode 2 : Makefile (Pour les Développeurs)

```bash
# Installation complète
make start

# Afficher toutes les commandes disponibles
make help
```

#### Méthode 3 : Docker Compose (Contrôle Total)

```bash
# 1. Construire les images
docker compose build --pull --no-cache

# 2. Démarrer les conteneurs
docker compose up -d

# 3. Attendre que PostgreSQL soit prêt
sleep 10

# 4. Créer la base de données
docker compose exec php bin/console doctrine:database:create --if-not-exists

# 5. Exécuter les migrations
docker compose exec php bin/console doctrine:migrations:migrate --no-interaction

# 6. Générer les clés JWT
docker compose exec php bin/console lexik:jwt:generate-keypair --overwrite
```

### Étape 5 : Vérification

#### Vérifier que les Services Fonctionnent

```bash
# Afficher l'état des conteneurs
docker compose ps

# Résultat attendu :
# NAME                STATUS              PORTS
# php                 Up (healthy)        0.0.0.0:80->80/tcp, 0.0.0.0:443->443/tcp
# database            Up (healthy)        0.0.0.0:5432->5432/tcp
# elasticsearch       Up (healthy)        0.0.0.0:9200->9200/tcp
```

#### Vérifier les Logs

```bash
# Logs de tous les services
docker compose logs

# Logs PHP uniquement
docker compose logs php

# Suivre les logs en temps réel
docker compose logs -f
```

#### Tester l'API

```bash
# Test HTTPS (acceptez le certificat auto-signé)
curl -k https://localhost

# Test API
curl -k https://localhost/api

# Résultat attendu : JSON avec la liste des endpoints
```

#### Ouvrir dans le Navigateur

1. Ouvrez **https://localhost** dans votre navigateur
2. Votre navigateur affichera un avertissement de sécurité (normal pour un certificat auto-signé)
3. **Cliquez sur "Avancé" puis "Accepter le risque et continuer"**
4. Vous devriez voir la page d'accueil de l'API

**Documentation API** : https://localhost/docs

### Étape 6 : Créer un Utilisateur Admin

```bash
# Avec Makefile
make admin-create

# Avec Docker Compose
docker compose exec php bin/console app:create-admin-user
```

Suivez les instructions pour créer votre compte administrateur.

## 🎨 Configuration IDE

### PHPStorm

1. **Configurer l'interpréteur PHP** :
   - Preferences → PHP
   - CLI Interpreter : Ajouter "From Docker, Vagrant, VM..."
   - Docker Compose
   - Service : `php`
   - Configuration files : `compose.yaml`

2. **Configurer XDebug** :
   - Run → Edit Configurations
   - Add New Configuration → PHP Remote Debug
   - Server Name : `localhost`
   - IDE key : `PHPSTORM`
   - Path mappings : `/app` → votre chemin local

3. **Configurer la base de données** :
   - Database → Data Source → PostgreSQL
   - Host : `localhost`
   - Port : `5432`
   - Database : `app`
   - User : `app`
   - Password : (celui de .env)

### Visual Studio Code

1. **Installer les extensions** :
   - PHP Intelephense
   - Docker
   - PHP Debug

2. **Configurer XDebug** (`.vscode/launch.json`) :
```json
{
    "version": "0.2.0",
    "configurations": [
        {
            "name": "Listen for Xdebug",
            "type": "php",
            "request": "launch",
            "port": 9003,
            "pathMappings": {
                "/app": "${workspaceFolder}"
            }
        }
    ]
}
```

3. **Configurer les tâches** (`.vscode/tasks.json`) :
```json
{
    "version": "2.0.0",
    "tasks": [
        {
            "label": "Start Docker",
            "type": "shell",
            "command": "docker compose up -d"
        },
        {
            "label": "Stop Docker",
            "type": "shell",
            "command": "docker compose down"
        }
    ]
}
```

## 🔧 Configuration Avancée

### Changer les Ports

Si les ports 80/443 sont déjà utilisés, modifiez `.env` :

```env
HTTP_PORT=8080
HTTPS_PORT=8443
```

Puis redémarrez :

```bash
docker compose down
docker compose up -d
```

L'application sera accessible sur **https://localhost:8443**

### Activer XDebug

```bash
# Arrêter les conteneurs
docker compose down

# Démarrer avec XDebug
XDEBUG_MODE=debug docker compose up -d

# Ou définir dans .env
echo "XDEBUG_MODE=debug" >> .env
docker compose up -d
```

### Ajouter des Données de Test

Si vous avez des fixtures :

```bash
docker compose exec php bin/console doctrine:fixtures:load --no-interaction
```

## ❓ Problèmes Courants

### Docker n'est pas installé

**Erreur** : `docker: command not found`

**Solution** : Installez Docker Desktop depuis https://docs.docker.com/get-docker/

### Port déjà utilisé

**Erreur** : `Bind for 0.0.0.0:80 failed: port is already allocated`

**Solution** : Changez les ports dans `.env` (voir section "Changer les Ports")

### Impossible de se connecter à la base de données

**Erreur** : `Connection refused` ou `Could not connect to database`

**Solution** :
```bash
# Vérifier que PostgreSQL est démarré
docker compose ps database

# Redémarrer la base de données
docker compose restart database

# Attendre 10 secondes et réessayer
```

### Erreur de certificat SSL

**Erreur** : `SSL certificate problem: self signed certificate`

**Solution** : 
- Dans le navigateur : Acceptez le certificat manuellement
- Avec curl : Utilisez l'option `-k` : `curl -k https://localhost`

### Erreur "Permission denied"

**Erreur** : `Permission denied` lors du démarrage

**Solution** :
```bash
# Rendre les scripts exécutables
chmod +x start.sh
chmod +x frankenphp/healthcheck.sh

# Sur Linux, vérifier les permissions Docker
sudo usermod -aG docker $USER
newgrp docker
```

### Les modifications ne sont pas prises en compte

**Solution** :
```bash
# Vider le cache Symfony
docker compose exec php bin/console cache:clear

# Reconstruire les images
docker compose build --no-cache
docker compose up -d
```

## 🧹 Désinstallation

### Arrêter l'Application

```bash
# Arrêter les conteneurs
docker compose down

# Arrêter et supprimer les volumes (⚠️ perte de données)
docker compose down -v
```

### Suppression Complète

```bash
# Supprimer les conteneurs, volumes et réseaux
docker compose down -v

# Supprimer les images
docker rmi $(docker images 'joy-pharma-backend*' -q)

# Supprimer le projet
cd ..
rm -rf joy-pharma-back
```

## 📚 Prochaines Étapes

Une fois l'installation terminée :

1. ✅ [Guide de Démarrage Rapide](docs/quickstart.md)
2. ✅ [Documentation API](https://localhost/docs)
3. ✅ [Guide de Contribution](CONTRIBUTING.md)
4. ✅ [Documentation Docker Complète](docs/docker.md)

## 🆘 Besoin d'Aide ?

- 📖 Consultez la [documentation complète](docs/)
- 🐛 Signalez un bug sur [GitHub Issues](https://github.com/votre-org/joy-pharma-back/issues)
- 💬 Posez une question dans [Discussions](https://github.com/votre-org/joy-pharma-back/discussions)

---

**Développé avec ❤️ pour Joy Pharma**

