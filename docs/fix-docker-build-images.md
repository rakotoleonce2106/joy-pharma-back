# Fix : Erreur Docker Build avec Images (185 MB)

## 🔴 Problème

Après avoir ajouté toutes les images dans `/public/images/products` (185 MB), le build Docker échoue avec :

```
ERROR: Error response from daemon: Head "https://registry-1.docker.io/v2/moby/buildkit/manifests/buildx-stable-1": 
received unexpected HTTP status: 500 Internal Server Error
```

## 🎯 Cause

1. **Images incluses dans le build Docker** : Le dossier `public/images/` n'était pas dans `.dockerignore`
2. **Image Docker trop volumineuse** : 185 MB d'images ralentissent le build et causent des timeouts
3. **Erreur Docker Hub** : Le registry Docker Hub retourne une erreur 500 à cause du timeout

## ✅ Solutions

### Solution 1 : Exclure les images du build Docker (RECOMMANDÉ)

Les images utilisateur ne doivent **PAS** être incluses dans l'image Docker. Elles doivent être stockées :
- Sur un volume Docker persistant
- Sur un service de stockage cloud (S3, DigitalOcean Spaces, etc.)
- Sur un CDN

#### Étape 1 : Mettre à jour `.dockerignore`

Le fichier `.dockerignore` a été mis à jour pour exclure :

```dockerignore
# Fichiers uploadés par les utilisateurs (ne doivent pas être dans l'image Docker)
public/images/
public/media/
public/uploads/
```

#### Étape 2 : Utiliser un volume Docker

Dans votre `compose.yaml` ou déploiement, montez un volume :

```yaml
services:
  php:
    volumes:
      - app_uploads:/app/public/images
      - app_media:/app/public/media
      - app_uploads_general:/app/public/uploads

volumes:
  app_uploads:
  app_media:
  app_uploads_general:
```

#### Étape 3 : Rebuild sans les images

```bash
# Nettoyer le cache Docker
docker builder prune -a -f

# Rebuild l'image (beaucoup plus légère maintenant)
docker build -t joy-pharma-backend .

# Vérifier la taille de l'image
docker images joy-pharma-backend
```

### Solution 2 : Utiliser un stockage cloud (Production)

Pour la production, utilisez un service de stockage externe :

#### Option A : AWS S3 / DigitalOcean Spaces

```bash
# Installer AWS CLI ou s3cmd
composer require league/flysystem-aws-s3-v3

# Configuration dans .env
AWS_S3_BUCKET=joy-pharma-uploads
AWS_S3_REGION=fra1
AWS_S3_ENDPOINT=https://fra1.digitaloceanspaces.com
AWS_ACCESS_KEY_ID=your_key
AWS_SECRET_ACCESS_KEY=your_secret
```

#### Option B : CDN

Uploadez les images sur un CDN et référencez-les par URL :

```bash
# Synchroniser les images vers le CDN
rsync -avz public/images/ user@cdn-server:/var/www/cdn/images/
```

### Solution 3 : Workaround temporaire (si vraiment nécessaire)

Si vous devez absolument inclure les images dans le build Docker (non recommandé) :

#### Option 1 : Utiliser Docker BuildKit avec cache

```bash
# Activer BuildKit
export DOCKER_BUILDKIT=1

# Build avec cache
docker build --build-arg BUILDKIT_INLINE_CACHE=1 -t joy-pharma-backend .
```

#### Option 2 : Compresser les images

```bash
# Installer imagemagick
brew install imagemagick  # macOS
apt-get install imagemagick  # Linux

# Compresser toutes les images
find public/images/products -type f \( -name "*.jpg" -o -name "*.jpeg" \) -exec mogrify -quality 75 {} \;
find public/images/products -type f -name "*.png" -exec optipng -o3 {} \;

# Vérifier la nouvelle taille
du -sh public/images/products
```

#### Option 3 : Retry le build

Parfois, c'est juste un problème temporaire du Docker Hub :

```bash
# Attendre quelques minutes et retry
sleep 300

# Retry le build
docker build -t joy-pharma-backend . --no-cache
```

## 🚀 Workflow recommandé pour les images

### Architecture idéale

```
┌─────────────────────────────────────┐
│   Application Backend (Docker)      │
│   - Code PHP/Symfony                │
│   - Pas d'images utilisateur        │
│   - Taille : ~500 MB                │
└─────────────────────────────────────┘
              ↓
┌─────────────────────────────────────┐
│   Volume Docker Persistant           │
│   - Images produits                 │
│   - Media uploads                   │
│   - Taille : illimitée              │
└─────────────────────────────────────┘
              ↓ (Optionnel)
┌─────────────────────────────────────┐
│   CDN / Cloud Storage               │
│   - Distribution globale            │
│   - Backup automatique              │
└─────────────────────────────────────┘
```

### Workflow de déploiement

1. **Build** : Image Docker sans les uploads (légère, rapide)
2. **Deploy** : Déployer l'image sur le serveur
3. **Sync** : Synchroniser les images séparément

```bash
# 1. Build l'image (maintenant rapide)
docker build -t joy-pharma-backend .

# 2. Push l'image
docker push registry/joy-pharma-backend

# 3. Sync les images séparément (une seule fois)
rsync -avz public/images/ server:/var/lib/docker/volumes/app_uploads/_data/
```

## 🔍 Vérification

### Vérifier que les images sont exclues

```bash
# Créer un build de test
docker build -t test-build .

# Créer un container temporaire
docker create --name test-container test-build

# Vérifier que public/images est vide
docker export test-container | tar -t | grep "public/images"

# Nettoyer
docker rm test-container
docker rmi test-build
```

### Vérifier la taille de l'image

```bash
# Avant (avec images) : ~700-800 MB
# Après (sans images) : ~500 MB

docker images joy-pharma-backend
```

## ⚡ Actions immédiates

### 1. Mettre à jour `.dockerignore` (FAIT ✅)

Le fichier a été mis à jour pour exclure `public/images/`, `public/media/`, `public/uploads/`

### 2. Nettoyer et rebuild

```bash
# Nettoyer le cache Docker
docker builder prune -a -f

# Rebuild
docker build -t joy-pharma-backend .
```

### 3. Push

```bash
# Push l'image (maintenant beaucoup plus légère)
docker push your-registry/joy-pharma-backend:latest
```

### 4. Configurer le volume sur le serveur

```bash
# Sur le serveur de production
mkdir -p /var/lib/docker/volumes/app_uploads/_data

# Copier les images une seule fois
scp -r public/images/* server:/var/lib/docker/volumes/app_uploads/_data/
```

## 📊 Comparaison

| Méthode | Taille image | Temps build | Temps push | Recommandé |
|---------|--------------|-------------|------------|------------|
| **Avec images** | ~800 MB | 5-10 min | 10-20 min | ❌ Non |
| **Sans images** | ~500 MB | 2-3 min | 3-5 min | ✅ Oui |
| **+ Compression** | ~600 MB | 3-4 min | 5-8 min | 🟡 OK |
| **+ CDN** | ~500 MB | 2-3 min | 3-5 min | ✅✅ Idéal |

## 🔄 Migration depuis l'ancien système

Si vous avez déjà des images en production :

```bash
# 1. Sauvegarder les images actuelles
ssh server "tar -czf /tmp/images-backup.tar.gz /path/to/public/images"

# 2. Télécharger le backup
scp server:/tmp/images-backup.tar.gz ./

# 3. Déployer la nouvelle version (sans images dans Docker)
# ... (votre processus de déploiement)

# 4. Restaurer les images dans le volume
scp images-backup.tar.gz server:/tmp/
ssh server "tar -xzf /tmp/images-backup.tar.gz -C /var/lib/docker/volumes/app_uploads/_data/"
```

## 🆘 En cas d'erreur persistante

Si l'erreur Docker Hub 500 persiste même après avoir exclu les images :

### 1. Vérifier le status de Docker Hub

```bash
# Vérifier https://status.docker.com/
curl -s https://status.docker.com/ | grep -i "operational"
```

### 2. Utiliser un registry alternatif temporairement

```bash
# GitHub Container Registry
docker tag joy-pharma-backend ghcr.io/username/joy-pharma-backend
docker push ghcr.io/username/joy-pharma-backend

# DigitalOcean Container Registry
docker tag joy-pharma-backend registry.digitalocean.com/your-registry/joy-pharma-backend
docker push registry.digitalocean.com/your-registry/joy-pharma-backend
```

### 3. Retry avec délai

```bash
# Script avec retry automatique
for i in {1..3}; do
  echo "Tentative $i..."
  docker push your-registry/joy-pharma-backend && break
  echo "Échec, attente 60s..."
  sleep 60
done
```

## 📝 Checklist

- [x] Mettre à jour `.dockerignore` pour exclure `public/images/`
- [ ] Nettoyer le cache Docker : `docker builder prune -a -f`
- [ ] Rebuild l'image : `docker build -t joy-pharma-backend .`
- [ ] Vérifier la taille : `docker images joy-pharma-backend`
- [ ] Configurer les volumes dans `compose.yaml`
- [ ] Push l'image : `docker push ...`
- [ ] Copier les images sur le serveur dans le volume persistant
- [ ] Tester que les images sont accessibles après déploiement

## 🎉 Résultat attendu

Après ces modifications :
- ✅ Build Docker **3-5x plus rapide**
- ✅ Image Docker **200-300 MB plus légère**
- ✅ Push vers le registry **beaucoup plus rapide**
- ✅ Plus d'erreur 500 de Docker Hub
- ✅ Architecture propre et scalable

Les images seront gérées via des volumes Docker persistants, ce qui est la bonne pratique pour les données utilisateur.

