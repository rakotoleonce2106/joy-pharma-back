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

Le workflow de déploiement (`.github/workflows/deploy.yml`) construit et pousse l'image Docker vers Docker Hub.

### Étapes de déploiement

1. **Push vers la branche `preprod`** déclenche automatiquement le build
2. L'image est poussée vers Docker Hub avec les tags appropriés
3. Sur votre serveur de production, récupérez et lancez l'image :

```bash
# Récupérer l'image
docker pull <votre-username>/joy-pharma-back:latest

# Lancer avec les variables d'environnement
docker run -d \
  --name joy-pharma-back \
  -p 80:80 -p 443:443 \
  -e SERVER_NAME=back-preprod.joy-pharma.com \
  -e DOTENV_KEY=$DOTENV_KEY \
  -e APP_SECRET=$APP_SECRET \
  -e DATABASE_URL=$DATABASE_URL \
  <votre-username>/joy-pharma-back:latest
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

