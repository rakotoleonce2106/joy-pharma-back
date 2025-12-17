# 🖼️ Configuration des Images avec Traefik

## 📐 Architecture actuelle

```
Serveur
├── /traefik/                          # Reverse proxy + HTTPS
│   └── Traefik gère: api.joypharma.com
│
├── /infrastructure/                   # PostgreSQL + pgAdmin
│
├── /joy-pharma-back/                  # Application backend
│   ├── compose.yaml
│   ├── compose.prod.yaml
│   └── .env
│
└── /joy-pharma-data/                  # ⭐ DONNÉES PERSISTANTES (à créer)
    ├── images/                        # 👈 VOS IMAGES ICI
    │   ├── products/
    │   └── profile/
    ├── media/                         # Uploads VichUploader
    └── uploads/                       # Autres uploads
```

## 🎯 Solution : Stockage dans `/joy-pharma-data/`

### Étape 1 : Créer les dossiers sur le serveur

```bash
# SSH vers votre serveur
ssh user@your-server

# Créer la structure
sudo mkdir -p /joy-pharma-data/images/products
sudo mkdir -p /joy-pharma-data/images/profile
sudo mkdir -p /joy-pharma-data/media
sudo mkdir -p /joy-pharma-data/uploads

# Permissions (www-data est l'utilisateur du conteneur PHP)
sudo chown -R 82:82 /joy-pharma-data/
# OU si www-data existe sur l'hôte :
# sudo chown -R www-data:www-data /joy-pharma-data/

sudo chmod -R 755 /joy-pharma-data/
```

> **Note** : UID 82 = www-data dans les conteneurs PHP FrankenPHP/Alpine

### Étape 2 : Copier vos images (185 MB) depuis votre Mac

```bash
# Sur votre Mac
cd /Users/mac2016/Documents/GitHub/joy-pharma-back

# Option A : SCP (simple)
scp -r public/images/* user@your-server:/tmp/images-upload/

# Option B : Rsync (recommandé - plus rapide avec beaucoup de fichiers)
rsync -avz --progress \
  public/images/ \
  user@your-server:/tmp/images-upload/
```

### Étape 3 : Déplacer vers `/joy-pharma-data/`

```bash
# Sur le serveur
ssh user@your-server

# Copier depuis /tmp vers /joy-pharma-data
sudo cp -r /tmp/images-upload/* /joy-pharma-data/images/

# Vérifier
sudo ls -lh /joy-pharma-data/images/products/ | head -10

# Ajuster les permissions
sudo chown -R 82:82 /joy-pharma-data/
sudo chmod -R 755 /joy-pharma-data/

# Nettoyer /tmp
rm -rf /tmp/images-upload/
```

### Étape 4 : Vérifier le `compose.prod.yaml`

Le fichier `docker-compose.prod.example.yml` contient déjà la bonne configuration :

```yaml
services:
  php:
    volumes:
      - /joy-pharma-data/images:/app/public/images:rw
      - /joy-pharma-data/media:/app/public/media:rw
      - /joy-pharma-data/uploads:/app/public/uploads:rw
```

✅ **Aucune modification nécessaire** si vous utilisez ce fichier !

### Étape 5 : Redéployer

```bash
# Sur le serveur
cd ~/joy-pharma-back
./deploy.sh
```

---

## 🌐 Comment les URLs fonctionnent

### Flow complet

```
1. Client demande: https://api.joypharma.com/images/products/doliprane.jpg
                                    ↓
2. Traefik reçoit la requête (port 443)
                                    ↓
3. Traefik route vers le container PHP (FrankenPHP)
                                    ↓
4. FrankenPHP sert le fichier depuis /app/public/images/products/doliprane.jpg
                                    ↓
5. Ce chemin est monté vers /joy-pharma-data/images/products/doliprane.jpg
                                    ↓
6. Client reçoit l'image ✅
```

### MediaObject et les URLs

Votre `MediaObject.php` retourne déjà les bonnes URLs :

```php
public function getContentUrl(): ?string
{
    if ($this->filePath) {
        // Retourne : /media/xxxxx.jpg
        return '/media/' . $this->filePath;
    }
    return null;
}
```

**Configuration VichUploader** (`config/packages/vich_uploader.yaml`) :

```yaml
vich_uploader:
    db_driver: orm
    mappings:
        media_object:
            uri_prefix: /media                              # 👈 Préfixe URL
            upload_destination: '%kernel.project_dir%/public/media'  # 👈 Dossier
            namer: Vich\UploaderBundle\Naming\SmartUniqueNamer
```

**Résultat** :
- Fichier stocké dans : `/app/public/media/xxxxx.jpg` (conteneur)
- Ce qui correspond à : `/joy-pharma-data/media/xxxxx.jpg` (serveur)
- URL accessible : `https://api.joypharma.com/media/xxxxx.jpg`

---

## 🔍 Vérification

### 1. Vérifier les volumes Docker

```bash
# Sur le serveur
cd ~/joy-pharma-back

# Lister les fichiers dans le container
docker compose -f compose.yaml -f compose.prod.yaml exec php ls -lh /app/public/images/products/ | head -10

# Vérifier le montage
docker compose -f compose.yaml -f compose.prod.yaml exec php df -h | grep images
```

Vous devriez voir :
```
/dev/sda1    50G   10G   40G   20%   /app/public/images
```

### 2. Tester l'accès HTTP

```bash
# Remplacer par un vrai nom de fichier
curl -I https://api.joypharma.com/images/products/test-image.jpg

# Ou avec IP locale
curl -I http://localhost/images/products/test-image.jpg
```

Réponse attendue :
```
HTTP/2 200
content-type: image/jpeg
content-length: 123456
...
```

### 3. Tester depuis votre application

```bash
# Test d'un MediaObject
curl https://api.joypharma.com/api/media_objects/1 | jq '.contentUrl'

# Résultat : "/media/xxxxx.jpg"

# Tester l'image
curl -I https://api.joypharma.com/media/xxxxx.jpg
```

---

## 📁 Structure complète des dossiers

### Sur le serveur : `/joy-pharma-data/`

```
/joy-pharma-data/
├── images/                           # Images statiques (produits, profils)
│   ├── products/                     # Images produits (185 MB)
│   │   ├── image1.jpg
│   │   ├── image2.png
│   │   └── ...
│   ├── profile/                      # Images de profil utilisateur
│   │   └── ...
│   └── placeholder.png              # Image par défaut
│
├── media/                            # Uploads VichUploader (MediaObject)
│   ├── 6789abcd-uuid.jpg
│   └── ...
│
└── uploads/                          # Autres uploads
    └── ...
```

### Dans le container : `/app/public/`

```
/app/public/
├── images/        → monté vers /joy-pharma-data/images/
├── media/         → monté vers /joy-pharma-data/media/
├── uploads/       → monté vers /joy-pharma-data/uploads/
├── index.php      # Point d'entrée Symfony
└── bundles/       # Assets Symfony
```

---

## 🚨 Problèmes courants et solutions

### Problème 1 : Images non accessibles (404)

```bash
# Vérifier que le dossier existe dans le container
docker compose exec php ls -la /app/public/images/

# Vérifier les permissions
docker compose exec php stat /app/public/images/

# Vérifier que FrankenPHP sert les fichiers statiques
docker compose exec php php -r "echo file_exists('/app/public/images/products/test.jpg') ? 'OK' : 'NON';"
```

**Solution** : Vérifier les permissions (UID 82)
```bash
sudo chown -R 82:82 /joy-pharma-data/
```

### Problème 2 : Volume vide après redémarrage

```bash
# Vérifier la configuration du volume dans compose.prod.yaml
cat compose.prod.yaml | grep -A 5 "volumes:"

# Devrait montrer :
#   - /joy-pharma-data/images:/app/public/images:rw
```

**Solution** : Le volume doit pointer vers un **chemin absolu** sur l'hôte

### Problème 3 : CORS bloque les images

```bash
# Vérifier les headers CORS
curl -I -H "Origin: https://admin.joypharma.com" \
  https://api.joypharma.com/images/products/test.jpg
```

**Solution** : Les labels Traefik dans `compose.prod.yaml` gèrent déjà CORS :

```yaml
labels:
  - "traefik.http.middlewares.joy-pharma-backend-cors.headers.accesscontrolalloworigin=*"
```

### Problème 4 : Permissions refusées

```bash
# Erreur : Permission denied
docker compose logs php | grep -i permission
```

**Solution** :

```bash
# Sur le serveur
sudo chown -R 82:82 /joy-pharma-data/
sudo chmod -R 755 /joy-pharma-data/

# Si vous utilisez SELinux (CentOS/RHEL)
sudo chcon -Rt container_file_t /joy-pharma-data/
```

---

## 📝 Script de synchronisation automatique

Créez ce script sur votre Mac pour synchroniser facilement les images :

```bash
#!/bin/bash
# sync-images-to-server.sh

SERVER="user@your-server"
LOCAL_DIR="public/images/"
REMOTE_TMP="/tmp/images-sync"
REMOTE_FINAL="/joy-pharma-data/images"

echo "🚀 Synchronisation des images vers le serveur..."

# 1. Synchroniser vers /tmp (pas besoin de sudo)
echo "📤 Upload en cours..."
rsync -avz --progress \
  --exclude='.DS_Store' \
  --exclude='*.tmp' \
  $LOCAL_DIR \
  $SERVER:$REMOTE_TMP/

# 2. Déplacer vers le dossier final avec sudo
echo "📦 Installation sur le serveur..."
ssh $SERVER << EOF
  # Backup (au cas où)
  sudo cp -rp $REMOTE_FINAL $REMOTE_FINAL.backup-\$(date +%Y%m%d-%H%M%S) 2>/dev/null || true
  
  # Synchroniser
  sudo rsync -av --delete $REMOTE_TMP/ $REMOTE_FINAL/
  
  # Permissions
  sudo chown -R 82:82 $REMOTE_FINAL/
  sudo chmod -R 755 $REMOTE_FINAL/
  
  # Nettoyer
  rm -rf $REMOTE_TMP/
  
  # Statistiques
  echo ""
  echo "✅ Synchronisation terminée !"
  echo "📊 Fichiers dans $REMOTE_FINAL :"
  sudo find $REMOTE_FINAL -type f | wc -l
  echo "💾 Taille totale :"
  sudo du -sh $REMOTE_FINAL
EOF

echo "🎉 Terminé !"
```

**Utilisation** :

```bash
chmod +x sync-images-to-server.sh
./sync-images-to-server.sh
```

---

## ✅ Checklist complète

### Préparation (une seule fois)

- [ ] Créer `/joy-pharma-data/images/` sur le serveur
- [ ] Créer `/joy-pharma-data/media/` sur le serveur
- [ ] Créer `/joy-pharma-data/uploads/` sur le serveur
- [ ] Définir les permissions (UID 82 ou www-data)
- [ ] Vérifier que `compose.prod.yaml` contient les volumes

### Copie des images (une seule fois)

- [ ] Copier les images vers `/tmp/` sur le serveur
- [ ] Déplacer vers `/joy-pharma-data/images/`
- [ ] Vérifier les permissions
- [ ] Vérifier le nombre de fichiers

### Déploiement

- [ ] Exécuter `./deploy.sh`
- [ ] Vérifier que le container démarre
- [ ] Vérifier les volumes montés
- [ ] Tester l'accès HTTP aux images

### Tests

- [ ] `curl -I https://api.joypharma.com/images/products/test.jpg`
- [ ] Accéder via le navigateur
- [ ] Tester un MediaObject API
- [ ] Vérifier les logs : `docker compose logs php`

---

## 🎯 Commandes rapides pour commencer MAINTENANT

```bash
# 1. Sur le serveur - Créer les dossiers
ssh user@your-server "sudo mkdir -p /joy-pharma-data/{images/products,images/profile,media,uploads} && sudo chown -R 82:82 /joy-pharma-data && sudo chmod -R 755 /joy-pharma-data"

# 2. Sur votre Mac - Copier les images
rsync -avz --progress public/images/ user@your-server:/tmp/images-upload/

# 3. Sur le serveur - Installer les images
ssh user@your-server "sudo cp -r /tmp/images-upload/* /joy-pharma-data/images/ && sudo chown -R 82:82 /joy-pharma-data/ && rm -rf /tmp/images-upload"

# 4. Redéployer
ssh user@your-server "cd ~/joy-pharma-back && ./deploy.sh"

# 5. Tester
curl -I https://api.joypharma.com/images/products/placeholder.png
```

---

## 📊 Résumé

| Élément | Valeur |
|---------|--------|
| **Stockage serveur** | `/joy-pharma-data/images/` |
| **Montage container** | `/app/public/images/` |
| **URL publique** | `https://api.joypharma.com/images/...` |
| **MediaObject URL** | `https://api.joypharma.com/media/...` |
| **Permissions** | UID 82 (www-data) |
| **Propriétaire** | `82:82` ou `www-data:www-data` |
| **Mode** | `755` (dossiers), `644` (fichiers) |

🎉 **Vos images seront accessibles via Traefik à l'URL** : `https://api.joypharma.com/images/products/nom-image.jpg`

