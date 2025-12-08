# Guide de Déploiement - back-preprod.joy-pharma.com

Ce guide explique comment configurer le déploiement du projet pour pointer vers `back-preprod.joy-pharma.com`.

## Architecture : Traefik et Caddy

Ce projet utilise **deux serveurs web** qui travaillent ensemble :

### Traefik (Reverse Proxy Externe)

**Rôle** : Reverse proxy externe qui gère le routage et les certificats SSL/TLS

- **Déjà installé sur le serveur** : Traefik est un service séparé qui écoute sur les ports 80 et 443
- **Gestion SSL/TLS** : Traefik obtient et renouvelle automatiquement les certificats Let's Encrypt
- **Routage** : Route le trafic HTTPS vers les conteneurs Docker appropriés
- **Découverte automatique** : Découvre automatiquement les conteneurs via les labels Docker
- **Point d'entrée** : C'est le premier point de contact pour les requêtes HTTP/HTTPS

### Caddy (Serveur Web Interne)

**Rôle** : Serveur web intégré dans le conteneur qui exécute l'application PHP

- **Intégré dans FrankenPHP** : Caddy est inclus dans l'image Docker `frankenphp`
- **Application PHP** : Exécute l'application Symfony/PHP
- **Mercure Hub** : Gère le hub Mercure pour les WebSockets et Server-Sent Events
- **Port interne** : Écoute sur le port 80 **à l'intérieur du conteneur** (pas exposé publiquement)
- **Configuration** : Configuré via `frankenphp/Caddyfile`

### Architecture en Production

```
Internet
   ↓
[Traefik] (Ports 80/443 publics)
   ↓ HTTPS avec certificat Let's Encrypt
   ↓ Routage basé sur Host: back-preprod.joy-pharma.com
[Conteneur joy-pharma-back]
   ↓ Port 80 interne (réseau Docker)
[Caddy/FrankenPHP] (Port 80 interne)
   ↓
[Application Symfony/PHP]
```

### Pourquoi cette architecture ?

1. **Séparation des responsabilités** :
   - Traefik gère le SSL/TLS et le routage multi-domaines
   - Caddy gère l'application PHP et Mercure

2. **Flexibilité** :
   - Traefik peut router vers plusieurs services (n8n, autres applications)
   - Caddy reste optimisé pour PHP/Symfony

3. **Sécurité** :
   - Le conteneur n'expose pas de ports publiquement
   - Seul Traefik est exposé sur les ports 80/443

4. **Développement local** :
   - En local (sans Traefik), Caddy peut être utilisé directement avec les ports exposés
   - En production, Traefik gère le routage

### Configuration dans ce projet

**En développement local** (`compose.yaml`) :
- Caddy expose les ports 80/443 directement
- Pas besoin de Traefik

**En production** (`.github/workflows/deploy.yml`) :
- Le conteneur n'expose **pas** de ports (pas de `-p 80:80`)
- Traefik route le trafic vers le conteneur via le réseau Docker `traefik`
- Traefik gère les certificats SSL/TLS
- Caddy écoute sur le port 80 interne et sert l'application

### Résumé

| Composant | Rôle | Port | Où |
|-----------|------|------|-----|
| **Traefik** | Reverse proxy, SSL/TLS | 80/443 publics | Serveur (déjà installé) |
| **Caddy** | Serveur web PHP, Mercure | 80 interne | Conteneur Docker |

**Important** : Traefik doit être configuré avec le certresolver `letsencrypt` pour que les certificats SSL fonctionnent. Voir la section "Erreur Traefik Router uses a nonexistent certificate resolver" ci-dessous.

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

⚠️ **IMPORTANT** : Avant de déployer, vous devez configurer l'enregistrement DNS pour que Let's Encrypt puisse valider le domaine et générer le certificat SSL.

### Étapes de configuration DNS

1. **Obtenez l'adresse IP de votre serveur** :
   ```bash
   # Sur votre serveur
   curl ifconfig.me
   # ou
   hostname -I
   ```

2. **Connectez-vous à votre fournisseur DNS** (où le domaine `joy-pharma.com` est géré) :
   - Cloudflare, AWS Route 53, OVH, etc.

3. **Ajoutez un enregistrement A** :
   ```
   Type: A
   Nom/Host: back-preprod
   Valeur/Points to: <IP_DE_VOTRE_SERVEUR>
   TTL: 300 (ou valeur par défaut)
   Proxy: Désactivé (important pour Let's Encrypt)
   ```

4. **Vérifiez la propagation DNS** (peut prendre de quelques minutes à 48h) :
   ```bash
   # Vérification depuis votre machine locale
   dig back-preprod.joy-pharma.com
   # ou
   nslookup back-preprod.joy-pharma.com
   # ou
   host back-preprod.joy-pharma.com
   ```

5. **Vérifiez que le domaine pointe vers votre serveur** :
   ```bash
   curl -I http://back-preprod.joy-pharma.com
   ```

**Note** : Si le DNS n'est pas configuré, vous verrez l'erreur `NXDOMAIN` dans les logs et le certificat SSL ne pourra pas être généré automatiquement.

## Certificat SSL/TLS

Traefik (utilisé comme reverse proxy) génère automatiquement des certificats SSL via Let's Encrypt pour `back-preprod.joy-pharma.com` si :
- Le port 80 et 443 sont accessibles depuis l'extérieur
- Le domaine pointe correctement vers votre serveur
- Traefik peut valider le domaine via le challenge HTTP-01

### Vérification du certificat

Après le démarrage, vérifiez que le certificat est généré et valide :

**Script de diagnostic automatique :**

Un script de diagnostic est disponible pour vérifier rapidement la configuration SSL :

```bash
./check_ssl.sh
```

Ce script vérifie :
- La résolution DNS
- La connexion HTTP/HTTPS
- Le certificat SSL et son émetteur
- La validité du certificat

**Vérification manuelle :**

**1. Vérification basique :**
```bash
# Test HTTP (doit rediriger vers HTTPS)
curl -I http://back-preprod.joy-pharma.com

# Test HTTPS
curl -I https://back-preprod.joy-pharma.com
```

**Si curl échoue avec "unable to get local issuer certificate"**, utilisez `-k` pour le diagnostic :
```bash
curl -k -I https://back-preprod.joy-pharma.com
```

**2. Vérification détaillée du certificat :**
```bash
# Vérifier l'émetteur, le sujet et les dates d'expiration
echo | openssl s_client -connect back-preprod.joy-pharma.com:443 -servername back-preprod.joy-pharma.com 2>&1 | openssl x509 -noout -issuer -subject -dates

# Vérifier si c'est un certificat Let's Encrypt valide
echo | openssl s_client -connect back-preprod.joy-pharma.com:443 -servername back-preprod.joy-pharma.com 2>&1 | openssl x509 -noout -issuer

# Vérifier la chaîne de certificats complète
echo | openssl s_client -connect back-preprod.joy-pharma.com:443 -servername back-preprod.joy-pharma.com 2>&1 | grep -A 10 "Certificate chain"
```

**Résultat attendu pour un certificat Let's Encrypt production :**
```
issuer=C = US, O = Let's Encrypt, CN = R3
```

**Si vous voyez "Fake LE Intermediate" ou "Let's Encrypt (STAGING)"**, c'est que le certificat staging est utilisé (voir section Troubleshooting).

**Si vous voyez "self-signed" ou un émetteur inconnu**, le certificat n'est pas valide et doit être régénéré.

**3. Vérification depuis le navigateur :**
- Ouvrez `https://back-preprod.joy-pharma.com` dans votre navigateur
- Cliquez sur l'icône de cadenas dans la barre d'adresse
- Vérifiez que le certificat est émis par "Let's Encrypt" (pas "Let's Encrypt (STAGING)")

**4. Vérification des logs Traefik :**
```bash
# Trouvez le conteneur Traefik
docker ps | grep traefik

# Consultez les logs pour voir la génération du certificat
docker logs <container-traefik> | grep -i certificate
docker logs <container-traefik> | grep -i letsencrypt
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

### Erreur DNS NXDOMAIN lors de l'obtention du certificat

Si vous voyez cette erreur dans les logs :
```
DNS problem: NXDOMAIN looking up A for back-preprod.joy-pharma.com
DNS problem: NXDOMAIN looking up AAAA for back-preprod.joy-pharma.com
```

**Cause** : Le domaine `back-preprod.joy-pharma.com` n'a pas d'enregistrement DNS configuré.

**Solution 1 : Configurer le DNS (recommandé)**

1. Connectez-vous à votre fournisseur DNS (où le domaine `joy-pharma.com` est géré)
2. Ajoutez un enregistrement A pointant vers l'IP de votre serveur :
   ```
   Type: A
   Nom: back-preprod
   Valeur: <IP_DE_VOTRE_SERVEUR>
   TTL: 300 (ou la valeur par défaut)
   ```
3. Attendez la propagation DNS (peut prendre quelques minutes à quelques heures)
4. Vérifiez la résolution DNS :
   ```bash
   dig back-preprod.joy-pharma.com
   # ou
   nslookup back-preprod.joy-pharma.com
   ```
5. Redémarrez le conteneur pour relancer l'obtention du certificat

**Solution 2 : Utiliser Let's Encrypt Staging (pour tests)**

Si vous voulez tester sans configurer le DNS immédiatement, vous pouvez utiliser l'environnement de staging de Let's Encrypt. Modifiez le workflow `.github/workflows/deploy.yml` pour ajouter :

```yaml
-l "traefik.http.routers.joy-pharma-back.tls.certresolver=letsencrypt-staging" \
```

Note : Les certificats staging ne sont pas reconnus comme valides par les navigateurs, mais permettent de tester la configuration.

**Solution 3 : Désactiver temporairement TLS**

Pour le développement uniquement, vous pouvez désactiver TLS en modifiant les labels Traefik dans le workflow :

```yaml
-l "traefik.http.routers.joy-pharma-back.entrypoints=web" \
# Retirez la ligne tls.certresolver
```

⚠️ **Attention** : Cette solution ne doit être utilisée qu'en développement local, jamais en production.

### Le certificat SSL ne se génère pas

- Vérifiez que les ports 80 et 443 sont ouverts dans votre firewall
- Vérifiez que le DNS pointe correctement (voir section ci-dessus)
- Vérifiez que le domaine résout correctement : `dig back-preprod.joy-pharma.com`
- Consultez les logs Traefik : `docker logs <container-traefik>` ou les logs du conteneur : `docker logs joy-pharma-back`

### Erreur Traefik "Router uses a nonexistent certificate resolver"

Si vous voyez cette erreur dans les logs Traefik :
```
ERR Router uses a nonexistent certificate resolver certificateResolver=letsencrypt routerName=joy-pharma-back@docker
```

**Cause** : Traefik n'a pas de certresolver `letsencrypt` configuré dans sa configuration globale.

**Solution : Configurer le certresolver dans Traefik**

Traefik doit avoir une configuration qui définit le certresolver `letsencrypt`. Voici comment le configurer :

**1. Vérifier la configuration actuelle de Traefik :**

```bash
# Trouvez le conteneur Traefik
docker ps | grep traefik

# Vérifiez la configuration
docker exec <container-traefik> cat /etc/traefik/traefik.yml
# ou si la config est dans un volume
docker inspect <container-traefik> | grep -A 10 "Mounts"
```

**2. Configuration Traefik avec certresolver Let's Encrypt :**

Si Traefik utilise un fichier de configuration, ajoutez ceci dans `traefik.yml` :

```yaml
certificatesResolvers:
  letsencrypt:
    acme:
      email: votre-email@example.com  # Remplacez par votre email
      storage: /data/acme.json
      httpChallenge:
        entryPoint: web
```

**3. Si Traefik est lancé avec docker-compose :**

Ajoutez la configuration dans votre `docker-compose.yml` pour Traefik :

```yaml
services:
  traefik:
    image: traefik:v2.10
    command:
      - "--certificatesresolvers.letsencrypt.acme.email=votre-email@example.com"
      - "--certificatesresolvers.letsencrypt.acme.storage=/data/acme.json"
      - "--certificatesresolvers.letsencrypt.acme.httpchallenge.entrypoint=web"
      - "--entrypoints.web.address=:80"
      - "--entrypoints.websecure.address=:443"
    volumes:
      - traefik_data:/data
    ports:
      - "80:80"
      - "443:443"
```

**4. Si Traefik est lancé directement avec docker run :**

Vous devez ajouter les arguments de ligne de commande :

```bash
docker run -d \
  --name traefik \
  -p 80:80 -p 443:443 \
  -v /var/run/docker.sock:/var/run/docker.sock:ro \
  -v traefik_data:/data \
  traefik:v2.10 \
  --certificatesresolvers.letsencrypt.acme.email=votre-email@example.com \
  --certificatesresolvers.letsencrypt.acme.storage=/data/acme.json \
  --certificatesresolvers.letsencrypt.acme.httpchallenge.entrypoint=web \
  --entrypoints.web.address=:80 \
  --entrypoints.websecure.address=:443
```

**5. Après avoir configuré le certresolver :**

```bash
# Redémarrez Traefik
docker restart <container-traefik>

# Vérifiez les logs pour confirmer
docker logs <container-traefik> | grep -i certresolver

# Redémarrez le conteneur joy-pharma-back
docker restart joy-pharma-back

# Vérifiez que l'erreur a disparu
docker logs <container-traefik> | tail -20
```

**6. Vérification :**

Après configuration, les logs Traefik ne devraient plus afficher l'erreur. Vous devriez voir des messages comme :
```
time="..." level=info msg="Configuration loaded from flags."
time="..." level=info msg="Starting provider *acme.Provider"
```

**Note importante** : 
- Remplacez `votre-email@example.com` par une adresse email valide (utilisée par Let's Encrypt pour les notifications)
- Assurez-vous que le volume `/data` est monté pour stocker `acme.json`
- Le port 80 doit être accessible pour le challenge HTTP-01 de Let's Encrypt

**Fichiers d'exemple :**

Des fichiers d'exemple de configuration Traefik sont disponibles dans `docs/` :
- `traefik.example.yml` : Configuration YAML complète
- `traefik.docker-compose.example.yml` : Configuration docker-compose

### Erreur Caddy "tls: unrecognized name" ou tentative d'obtention de certificat

Si vous voyez cette erreur dans les logs Caddy :
```
error="tls: unrecognized name"
msg="validating authorization"
identifier="back-preprod.joy-pharma.com"
```

**Cause** : Caddy essaie d'obtenir automatiquement un certificat Let's Encrypt alors que Traefik gère déjà les certificats SSL/TLS.

**Problème** : En production, Caddy ne doit **pas** obtenir de certificats car :
1. Traefik gère déjà les certificats SSL/TLS
2. Caddy écoute uniquement sur le port 80 interne (pas accessible publiquement)
3. Les certificats obtenus par Caddy ne seraient pas utilisés par Traefik

**Solution : Désactiver la gestion automatique des certificats dans Caddy**

La variable d'environnement `CADDY_TLS_CONFIG` doit être définie pour désactiver la gestion automatique des certificats.

**1. Dans le workflow de déploiement (`.github/workflows/deploy.yml`) :**

La variable est déjà ajoutée :
```yaml
-e CADDY_TLS_CONFIG="tls internal" \
```

**2. Dans `compose.prod.yaml` :**

La variable est déjà configurée :
```yaml
CADDY_TLS_CONFIG: ${CADDY_TLS_CONFIG:-tls internal}
```

**3. Vérifier que la variable est définie :**

```bash
# Sur le serveur, vérifiez les variables d'environnement du conteneur
docker inspect joy-pharma-back | grep -A 5 "CADDY_TLS_CONFIG"
```

Doit afficher :
```
"CADDY_TLS_CONFIG=tls internal"
```

**4. Si la variable n'est pas définie, redéployer :**

```bash
# Redémarrer le conteneur avec la variable
docker stop joy-pharma-back
docker rm joy-pharma-back

# Redéployer via GitHub Actions ou manuellement avec la variable
docker run -d \
  --name joy-pharma-back \
  ...
  -e CADDY_TLS_CONFIG="tls internal" \
  ...
```

**5. Vérifier les logs après correction :**

```bash
docker logs joy-pharma-back | grep -i "certificate\|tls\|acme"
```

Vous ne devriez plus voir d'erreurs liées à l'obtention de certificats Let's Encrypt par Caddy.

**Explication technique :**

- `tls internal` : Caddy utilise un certificat auto-signé interne (non utilisé par Traefik)
- Traefik gère le SSL/TLS externe avec Let's Encrypt
- Caddy sert uniquement l'application PHP sur le port 80 interne
- Traefik termine le SSL/TLS et transmet le trafic HTTP en clair à Caddy

### Erreur `ERR_CERT_AUTHORITY_INVALID` dans le navigateur

Si votre navigateur affiche l'erreur `net::ERR_CERT_AUTHORITY_INVALID`, cela signifie que le certificat SSL n'est pas reconnu comme valide par le navigateur.

**Causes possibles :**

1. **Certificat Let's Encrypt Staging utilisé** : Si Traefik est configuré pour utiliser `letsencrypt-staging`, les certificats ne sont pas reconnus par les navigateurs (ils sont destinés aux tests uniquement).

2. **Certificat auto-signé** : Un certificat auto-signé est utilisé au lieu d'un certificat Let's Encrypt valide.

3. **Certificat non généré correctement** : Le certificat Let's Encrypt n'a pas été généré ou a échoué.

4. **Configuration Traefik incorrecte** : Le certresolver n'est pas correctement configuré dans Traefik.

**Solutions :**

**Solution 1 : Vérifier la configuration Traefik**

Vérifiez que Traefik utilise bien le certresolver `letsencrypt` (production) et non `letsencrypt-staging` :

```bash
# Sur le serveur, vérifiez les labels du conteneur
docker inspect joy-pharma-back | grep -i certresolver
```

Le label doit être :
```
traefik.http.routers.joy-pharma-back.tls.certresolver=letsencrypt
```

**Solution 2 : Vérifier les certificats générés par Traefik**

```bash
# Vérifiez les certificats dans Traefik
docker exec <container-traefik> ls -la /data/acme.json
# ou selon votre configuration Traefik
docker exec <container-traefik> cat /data/acme.json
```

**Solution 3 : Vérifier le certificat via la ligne de commande**

```bash
# Vérifiez le certificat depuis votre machine
openssl s_client -connect back-preprod.joy-pharma.com:443 -servername back-preprod.joy-pharma.com < /dev/null 2>/dev/null | openssl x509 -noout -issuer -subject -dates
```

Si vous voyez "Fake LE Intermediate" ou "Let's Encrypt (STAGING)", c'est que le staging est utilisé.

**Si curl échoue avec "unable to get local issuer certificate" :**

```bash
# Diagnostic : contourner la vérification pour voir le certificat
curl -k -I https://back-preprod.joy-pharma.com

# Vérifier le certificat en détail
openssl s_client -connect back-preprod.joy-pharma.com:443 -servername back-preprod.joy-pharma.com < /dev/null 2>&1 | grep -A 5 "Certificate chain"

# Vérifier l'émetteur complet
openssl s_client -connect back-preprod.joy-pharma.com:443 -servername back-preprod.joy-pharma.com < /dev/null 2>&1 | openssl x509 -noout -text | grep -A 3 "Issuer:"
```

L'erreur `unable to get local issuer certificate` avec curl indique généralement :
- Un certificat auto-signé
- Un certificat Let's Encrypt staging
- Une chaîne de certificats incomplète
- Un certificat expiré ou invalide

**Solution 4 : Forcer la régénération du certificat**

1. Supprimez les certificats existants dans Traefik (si accessible)
2. Redémarrez le conteneur :
   ```bash
   docker restart joy-pharma-back
   ```
3. Vérifiez les logs Traefik pour voir la génération du certificat :
   ```bash
   docker logs <container-traefik> -f
   ```

**Solution 5 : Vérifier la configuration Traefik globale**

Assurez-vous que Traefik a bien un certresolver `letsencrypt` configuré (pas seulement `letsencrypt-staging`). Vérifiez la configuration Traefik :

```bash
# Trouvez le conteneur Traefik
docker ps | grep traefik

# Vérifiez sa configuration
docker exec <container-traefik> cat /etc/traefik/traefik.yml
# ou
docker inspect <container-traefik> | grep -A 20 -i certresolver
```

**Solution 6 : Utiliser Let's Encrypt Production explicitement**

Si vous utilisez Traefik, assurez-vous que le certresolver est bien configuré pour la production. Dans le workflow `.github/workflows/deploy.yml`, la ligne doit être :

```yaml
-l "traefik.http.routers.joy-pharma-back.tls.certresolver=letsencrypt" \
```

**Note importante** : Si vous avez utilisé `letsencrypt-staging` pour tester, vous devez :
1. Changer pour `letsencrypt` dans le workflow
2. Supprimer les certificats staging dans Traefik
3. Redéployer pour générer un nouveau certificat production

### Erreur "Impossible de se connecter en HTTPS"

Si le script de diagnostic ou `curl -k` échoue avec "Impossible de se connecter en HTTPS", cela signifie que la connexion HTTPS ne fonctionne pas du tout, même en ignorant le certificat.

**Causes possibles :**

1. **Traefik n'est pas démarré** : Le reverse proxy Traefik n'est pas en cours d'exécution
2. **Port 443 fermé** : Le port 443 est fermé par le firewall ou n'est pas exposé
3. **Conteneur non démarré** : Le conteneur `joy-pharma-back` n'est pas démarré
4. **Configuration réseau incorrecte** : Le conteneur n'est pas sur le réseau Traefik
5. **Traefik n'écoute pas sur le port 443** : Configuration Traefik incorrecte

**Diagnostic sur le serveur :**

```bash
# 1. Vérifier que Traefik est démarré
docker ps | grep traefik

# 2. Vérifier que le conteneur joy-pharma-back est démarré
docker ps | grep joy-pharma-back

# 3. Vérifier que le conteneur est sur le réseau traefik
docker inspect joy-pharma-back | grep -A 10 "Networks"

# 4. Vérifier les logs Traefik
docker logs <container-traefik> | tail -50

# 5. Vérifier les logs du conteneur
docker logs joy-pharma-back | tail -50

# 6. Vérifier que le port 443 est ouvert (sur le serveur)
sudo netstat -tlnp | grep 443
# ou
sudo ss -tlnp | grep 443

# 7. Vérifier les règles de firewall
sudo ufw status
# ou
sudo iptables -L -n | grep 443
```

**Solutions :**

**Solution 1 : Démarrer Traefik**

Si Traefik n'est pas démarré, démarrez-le selon votre configuration. Traefik doit être configuré pour :
- Écouter sur les ports 80 et 443
- Avoir un certresolver `letsencrypt` configuré
- Être sur le réseau Docker `traefik`

**Solution 2 : Vérifier la configuration du conteneur**

Vérifiez que le conteneur a les bons labels Traefik :

```bash
docker inspect joy-pharma-back | grep -A 20 "Labels"
```

Doit contenir :
- `traefik.enable=true`
- `traefik.http.routers.joy-pharma-back.rule=Host(\`back-preprod.joy-pharma.com\`)`
- `traefik.http.routers.joy-pharma-back.entrypoints=websecure`
- `traefik.http.routers.joy-pharma-back.tls.certresolver=letsencrypt`
- `traefik.docker.network=traefik`

**Solution 3 : Vérifier le réseau Docker**

```bash
# Vérifier que le réseau traefik existe
docker network ls | grep traefik

# Si le réseau n'existe pas, le créer
docker network create traefik

# Vérifier que le conteneur est sur le bon réseau
docker inspect joy-pharma-back | grep -A 5 "Networks"
```

**Solution 4 : Ouvrir les ports dans le firewall**

```bash
# Ubuntu/Debian avec ufw
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw reload

# Ou avec iptables
sudo iptables -A INPUT -p tcp --dport 80 -j ACCEPT
sudo iptables -A INPUT -p tcp --dport 443 -j ACCEPT
```

**Solution 5 : Redémarrer les services**

```bash
# Redémarrer le conteneur
docker restart joy-pharma-back

# Vérifier les logs après redémarrage
docker logs joy-pharma-back -f
```

### Erreur curl "unable to get local issuer certificate"

Si `curl` échoue avec cette erreur :
```
curl: (60) SSL certificate problem: unable to get local issuer certificate
```

**Diagnostic rapide :**

```bash
# 1. Vérifier que HTTP fonctionne (redirection vers HTTPS)
curl -I http://back-preprod.joy-pharma.com
# Doit retourner : HTTP/1.1 308 Permanent Redirect vers HTTPS

# 2. Contourner la vérification SSL pour tester la connexion
curl -k -I https://back-preprod.joy-pharma.com
# Si cela fonctionne, le problème vient du certificat, pas de la connexion

# 3. Vérifier le certificat en détail
echo | openssl s_client -connect back-preprod.joy-pharma.com:443 -servername back-preprod.joy-pharma.com 2>&1 | openssl x509 -noout -issuer -subject -dates

# 4. Vérifier la chaîne de certificats complète
echo | openssl s_client -connect back-preprod.joy-pharma.com:443 -servername back-preprod.joy-pharma.com 2>&1 | grep -A 10 "Certificate chain"
```

**Solutions :**

1. **Si le certificat est en staging** : Vérifiez que Traefik utilise `letsencrypt` (production) et non `letsencrypt-staging`
2. **Si le certificat est auto-signé** : Traefik n'a pas réussi à obtenir un certificat Let's Encrypt. Vérifiez les logs Traefik
3. **Si la chaîne est incomplète** : Traefik doit servir la chaîne complète. Vérifiez la configuration Traefik

**Pour forcer curl à accepter le certificat (temporaire, pour tests uniquement) :**

```bash
# Option 1 : Désactiver la vérification (non sécurisé, pour tests uniquement)
curl -k https://back-preprod.joy-pharma.com

# Option 2 : Utiliser les certificats système
curl --cacert /etc/ssl/certs/ca-certificates.crt https://back-preprod.joy-pharma.com
```

⚠️ **Important** : L'option `-k` désactive la vérification SSL et ne doit être utilisée que pour le diagnostic. En production, le certificat doit être valide.

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

