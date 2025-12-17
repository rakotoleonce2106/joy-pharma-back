# 📁 Gestion des Images sur le Serveur

## 🎯 Où mettre les images sur le serveur ?

Il existe **3 options** pour stocker les images sur votre serveur. Voici un guide complet pour chaque option.

---

## ✅ Option 1 : Volume Docker (RECOMMANDÉ pour débuter)

### Architecture

```
Serveur
├── /var/lib/docker/volumes/
│   ├── joy_pharma_images/_data/        ← Images produits ici
│   ├── joy_pharma_media/_data/         ← Media uploads ici
│   └── joy_pharma_uploads/_data/       ← Autres uploads ici
│
└── Docker Container
    └── /app/public/images/ → monté vers le volume
```

### Configuration

#### 1. Créer le fichier `compose.prod.yaml`

```yaml
# compose.prod.yaml
version: '3.8'

services:
  php:
    image: ${DOCKER_IMAGE:-your-registry/joy-pharma-backend:latest}
    volumes:
      # Monter les volumes pour les images
      - joy_pharma_images:/app/public/images:rw
      - joy_pharma_media:/app/public/media:rw
      - joy_pharma_uploads:/app/public/uploads:rw
    environment:
      APP_ENV: prod
      DATABASE_URL: ${DATABASE_URL}
    restart: unless-stopped

  nginx:
    image: nginx:alpine
    volumes:
      # IMPORTANT : Nginx doit aussi avoir accès aux images
      - joy_pharma_images:/var/www/html/public/images:ro
      - joy_pharma_media:/var/www/html/public/media:ro
      - joy_pharma_uploads:/var/www/html/public/uploads:ro
    ports:
      - "80:80"
      - "443:443"
    restart: unless-stopped

volumes:
  joy_pharma_images:
    driver: local
  joy_pharma_media:
    driver: local
  joy_pharma_uploads:
    driver: local
```

#### 2. Démarrer avec les volumes

```bash
# Sur le serveur
cd /path/to/your/app

# Démarrer avec les volumes
docker compose -f compose.prod.yaml up -d
```

#### 3. Copier les images dans le volume

**Méthode A : Depuis votre machine locale**

```bash
# Créer une archive des images
cd /Users/mac2016/Documents/GitHub/joy-pharma-back
tar -czf images.tar.gz public/images/

# Copier sur le serveur
scp images.tar.gz user@your-server:/tmp/

# Sur le serveur, extraire dans le volume
ssh user@your-server
cd /tmp
tar -xzf images.tar.gz

# Trouver le chemin du volume
VOLUME_PATH=$(docker volume inspect joy_pharma_images --format '{{.Mountpoint}}')
echo "Volume path: $VOLUME_PATH"

# Copier les images dans le volume
sudo cp -r public/images/* $VOLUME_PATH/

# Vérifier
sudo ls -lh $VOLUME_PATH/
```

**Méthode B : Upload direct dans le volume**

```bash
# Sur le serveur
# Obtenir le chemin du volume
VOLUME_PATH=$(docker volume inspect joy_pharma_images --format '{{.Mountpoint}}')

# Créer l'arborescence
sudo mkdir -p $VOLUME_PATH/products
sudo mkdir -p $VOLUME_PATH/profile

# Copier vos images
sudo cp /path/to/your/images/* $VOLUME_PATH/products/

# Ajuster les permissions
sudo chown -R www-data:www-data $VOLUME_PATH/
sudo chmod -R 755 $VOLUME_PATH/
```

**Méthode C : Rsync (le plus efficace pour beaucoup de fichiers)**

```bash
# Depuis votre machine locale
rsync -avz --progress \
  public/images/ \
  user@your-server:/tmp/images-upload/

# Sur le serveur
ssh user@your-server
VOLUME_PATH=$(docker volume inspect joy_pharma_images --format '{{.Mountpoint}}')
sudo rsync -av /tmp/images-upload/ $VOLUME_PATH/
sudo chown -R www-data:www-data $VOLUME_PATH/
```

#### 4. Vérifier que ça fonctionne

```bash
# Sur le serveur
# Lister les fichiers dans le volume
VOLUME_PATH=$(docker volume inspect joy_pharma_images --format '{{.Mountpoint}}')
sudo ls -lh $VOLUME_PATH/products/ | head -20

# Vérifier depuis le container
docker compose exec php ls -lh /app/public/images/products/ | head -20

# Tester l'accès HTTP
curl -I http://your-domain.com/images/products/nom-image.jpg
```

---

## 🚀 Option 2 : Dossier partagé sur l'hôte (Simple mais moins flexible)

### Architecture

```
Serveur
├── /srv/joy-pharma/
│   ├── images/
│   │   ├── products/     ← Vos images ici
│   │   └── profile/
│   ├── media/
│   └── uploads/
│
└── Docker Container
    └── /app/public/images/ → monté vers /srv/joy-pharma/images/
```

### Configuration

#### 1. Créer les dossiers sur le serveur

```bash
# Sur le serveur
sudo mkdir -p /srv/joy-pharma/images/products
sudo mkdir -p /srv/joy-pharma/images/profile
sudo mkdir -p /srv/joy-pharma/media
sudo mkdir -p /srv/joy-pharma/uploads

# Définir les permissions
sudo chown -R www-data:www-data /srv/joy-pharma/
sudo chmod -R 755 /srv/joy-pharma/
```

#### 2. Modifier `compose.prod.yaml`

```yaml
# compose.prod.yaml
version: '3.8'

services:
  php:
    image: ${DOCKER_IMAGE}
    volumes:
      # Montage direct vers le dossier de l'hôte
      - /srv/joy-pharma/images:/app/public/images:rw
      - /srv/joy-pharma/media:/app/public/media:rw
      - /srv/joy-pharma/uploads:/app/public/uploads:rw
    restart: unless-stopped

  nginx:
    image: nginx:alpine
    volumes:
      # Nginx accède aux mêmes dossiers
      - /srv/joy-pharma/images:/var/www/html/public/images:ro
      - /srv/joy-pharma/media:/var/www/html/public/media:ro
    ports:
      - "80:80"
      - "443:443"
    restart: unless-stopped
```

#### 3. Copier les images

```bash
# Méthode 1 : SCP depuis votre machine
scp -r public/images/* user@your-server:/srv/joy-pharma/images/

# Méthode 2 : Rsync (recommandé pour beaucoup de fichiers)
rsync -avz --progress \
  public/images/ \
  user@your-server:/srv/joy-pharma/images/

# Sur le serveur, ajuster les permissions
ssh user@your-server
sudo chown -R www-data:www-data /srv/joy-pharma/images/
sudo chmod -R 755 /srv/joy-pharma/images/
```

#### 4. Vérifier

```bash
# Lister les fichiers
ls -lh /srv/joy-pharma/images/products/

# Vérifier depuis le container
docker compose exec php ls -lh /app/public/images/products/

# Tester l'accès HTTP
curl -I http://your-domain.com/images/products/test-image.jpg
```

---

## ☁️ Option 3 : Stockage Cloud / CDN (RECOMMANDÉ pour production)

### Architecture

```
                    ┌─────────────────────────┐
                    │   CDN / Cloud Storage   │
                    │   (S3, Spaces, etc.)    │
                    │   - Images produits     │
                    │   - Distribution globale│
                    └─────────────────────────┘
                              ↑
                              │ Upload
                              │
                    ┌─────────────────────────┐
                    │   Application Backend   │
                    │   - Code PHP            │
                    │   - Pas d'images        │
                    └─────────────────────────┘
```

### Avantages

- ✅ Performance : CDN distribué globalement
- ✅ Scalabilité : Pas de limite de stockage
- ✅ Backup automatique
- ✅ Coût optimisé (~$5/mois pour 250GB)
- ✅ Images séparées de l'application

### Providers recommandés

#### A. DigitalOcean Spaces (Simple, pas cher)

**Coût** : $5/mois pour 250GB + 1TB de transfert

```bash
# Installation
composer require league/flysystem-aws-s3-v3

# Configuration .env
DO_SPACES_ENDPOINT=https://fra1.digitaloceanspaces.com
DO_SPACES_KEY=your_key
DO_SPACES_SECRET=your_secret
DO_SPACES_BUCKET=joy-pharma-images
DO_SPACES_REGION=fra1
DO_SPACES_CDN_URL=https://joy-pharma-images.fra1.cdn.digitaloceanspaces.com
```

**Upload des images**

```bash
# Installer s3cmd
pip3 install s3cmd

# Configurer
s3cmd --configure

# Upload toutes les images
s3cmd sync public/images/ s3://joy-pharma-images/images/ \
  --acl-public \
  --add-header="Cache-Control:max-age=31536000"

# Vérifier
s3cmd ls s3://joy-pharma-images/images/products/
```

#### B. AWS S3 (Puissant, flexible)

**Configuration .env**

```bash
AWS_ACCESS_KEY_ID=your_key
AWS_SECRET_ACCESS_KEY=your_secret
AWS_S3_BUCKET=joy-pharma-images
AWS_S3_REGION=eu-west-3
AWS_CLOUDFRONT_URL=https://d1234567890.cloudfront.net
```

**Upload avec AWS CLI**

```bash
# Installation
brew install awscli  # macOS
apt-get install awscli  # Linux

# Configuration
aws configure

# Upload
aws s3 sync public/images/ s3://joy-pharma-images/images/ \
  --acl public-read \
  --cache-control max-age=31536000

# Avec CloudFront (CDN)
aws cloudfront create-invalidation \
  --distribution-id YOUR_DIST_ID \
  --paths "/images/*"
```

#### C. Cloudinary (Optimisation automatique d'images)

**Avantages** : Redimensionnement automatique, optimisation, transformations

```bash
composer require cloudinary/cloudinary_php

# Configuration .env
CLOUDINARY_URL=cloudinary://key:secret@cloud_name
```

---

## 📋 Quelle option choisir ?

| Critère | Volume Docker | Dossier Hôte | Cloud/CDN |
|---------|--------------|--------------|-----------|
| **Setup** | Moyen | Facile | Complexe |
| **Coût** | Gratuit | Gratuit | ~$5-20/mois |
| **Performance** | Bonne | Bonne | Excellente |
| **Scalabilité** | Limitée | Limitée | Illimitée |
| **Backup** | Manuel | Manuel | Automatique |
| **Distribution** | Local | Local | Globale |
| **Maintenance** | Moyenne | Facile | Faible |
| **Recommandé pour** | Dev/Staging | Dev/Small prod | Production |

### Ma recommandation

1. **Phase 1 (maintenant)** : Utilisez **Volume Docker** ou **Dossier Hôte**
2. **Phase 2 (quand traffic augmente)** : Migrez vers **DigitalOcean Spaces + CDN**

---

## 🔄 Script de synchronisation automatique

### Pour Volume Docker

```bash
#!/bin/bash
# sync-images-to-server.sh

SERVER="user@your-server.com"
LOCAL_IMAGES="public/images/"
VOLUME_NAME="joy_pharma_images"

echo "🚀 Synchronisation des images vers le serveur..."

# 1. Créer une archive
echo "📦 Création de l'archive..."
tar -czf /tmp/images.tar.gz -C public images/

# 2. Copier sur le serveur
echo "📤 Upload vers le serveur..."
scp /tmp/images.tar.gz $SERVER:/tmp/

# 3. Extraire dans le volume
echo "📥 Extraction dans le volume Docker..."
ssh $SERVER << 'EOF'
  # Obtenir le chemin du volume
  VOLUME_PATH=$(docker volume inspect joy_pharma_images --format '{{.Mountpoint}}')
  
  # Extraire
  cd /tmp
  tar -xzf images.tar.gz
  
  # Copier dans le volume
  sudo rsync -av --delete images/ $VOLUME_PATH/
  
  # Permissions
  sudo chown -R www-data:www-data $VOLUME_PATH/
  sudo chmod -R 755 $VOLUME_PATH/
  
  # Nettoyer
  rm -rf images/ images.tar.gz
  
  echo "✅ Synchronisation terminée !"
  echo "📊 Nombre de fichiers :"
  sudo find $VOLUME_PATH -type f | wc -l
EOF

# 4. Nettoyer local
rm /tmp/images.tar.gz

echo "🎉 Terminé !"
```

**Utilisation**

```bash
chmod +x sync-images-to-server.sh
./sync-images-to-server.sh
```

### Pour Dossier Hôte

```bash
#!/bin/bash
# sync-images-simple.sh

rsync -avz --progress --delete \
  public/images/ \
  user@your-server:/srv/joy-pharma/images/

ssh user@your-server << 'EOF'
  sudo chown -R www-data:www-data /srv/joy-pharma/images/
  sudo chmod -R 755 /srv/joy-pharma/images/
  echo "✅ Synchronisation terminée !"
  echo "📊 $(find /srv/joy-pharma/images -type f | wc -l) fichiers synchronisés"
EOF
```

---

## ✅ Checklist de déploiement

### Avant le déploiement

- [ ] Choisir l'option de stockage (Volume Docker / Dossier Hôte / Cloud)
- [ ] Créer les dossiers/volumes sur le serveur
- [ ] Configurer `compose.prod.yaml` avec les volumes
- [ ] Tester le montage des volumes

### Déploiement initial

- [ ] Déployer l'application (sans images)
- [ ] Copier les images dans le volume/dossier
- [ ] Vérifier les permissions (www-data:www-data, 755)
- [ ] Tester l'accès HTTP aux images

### Après le déploiement

- [ ] Vérifier que les images s'affichent
- [ ] Tester l'upload de nouvelles images
- [ ] Configurer les backups
- [ ] (Optionnel) Configurer le CDN

---

## 🔍 Dépannage

### Les images ne s'affichent pas

```bash
# 1. Vérifier que le volume est monté
docker compose exec php df -h

# 2. Vérifier les fichiers
docker compose exec php ls -lh /app/public/images/products/

# 3. Vérifier les permissions
docker compose exec php ls -la /app/public/images/

# 4. Vérifier la configuration Nginx
docker compose exec nginx nginx -T | grep -A 10 "location.*images"

# 5. Tester l'accès direct
curl -I http://your-domain.com/images/products/test.jpg
```

### Permissions incorrectes

```bash
# Sur le serveur
VOLUME_PATH=$(docker volume inspect joy_pharma_images --format '{{.Mountpoint}}')

# Corriger les permissions
sudo chown -R www-data:www-data $VOLUME_PATH/
sudo chmod -R 755 $VOLUME_PATH/

# Redémarrer les containers
docker compose restart
```

### Volume vide après redémarrage

```bash
# Vérifier que le volume est bien déclaré dans compose.yaml
docker compose config | grep -A 5 "volumes:"

# Vérifier que le volume existe
docker volume ls | grep joy_pharma

# Inspecter le volume
docker volume inspect joy_pharma_images
```

---

## 📞 Exemple complet pour commencer maintenant

### Option recommandée : Dossier Hôte (le plus simple)

```bash
# 1. Sur le serveur, créer les dossiers
ssh user@your-server
sudo mkdir -p /srv/joy-pharma/images/products
sudo mkdir -p /srv/joy-pharma/images/profile
sudo chown -R www-data:www-data /srv/joy-pharma/
sudo chmod -R 755 /srv/joy-pharma/

# 2. Sur votre machine locale, copier les images
cd /Users/mac2016/Documents/GitHub/joy-pharma-back
rsync -avz --progress \
  public/images/ \
  user@your-server:/srv/joy-pharma/images/

# 3. Ajuster les permissions
ssh user@your-server "sudo chown -R www-data:www-data /srv/joy-pharma/images/"

# 4. Mettre à jour compose.prod.yaml sur le serveur
# Ajouter les volumes (voir exemple ci-dessus)

# 5. Redémarrer l'application
ssh user@your-server "cd /path/to/app && docker compose -f compose.prod.yaml up -d"

# 6. Vérifier
curl -I http://your-domain.com/images/products/test-image.jpg
```

---

**Résumé** : Pour commencer, utilisez un **dossier sur l'hôte** (`/srv/joy-pharma/images/`) et synchronisez avec `rsync`. Plus tard, migrez vers un CDN pour de meilleures performances ! 🚀

