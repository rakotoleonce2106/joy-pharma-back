# 🚀 Setup Images - Guide pour votre serveur

## 📊 Votre architecture actuelle

```
/
├── traefik/                    # Reverse proxy
├── infrastructure/             # PostgreSQL partagée
├── joy-pharma-back/           # ← Vous êtes ici (auto-déployé)
└── joy-pharma-admin/          # Frontend
```

## 🎯 Objectif

Les images doivent être **HORS** du dossier `joy-pharma-back/` pour ne pas être supprimées lors des déploiements automatiques.

## ✅ Solution : Dossier partagé `/joy-pharma-data/`

### Architecture finale

```
/
├── traefik/
├── infrastructure/
│
├── joy-pharma-data/           # ← NOUVEAU - Données persistantes
│   ├── images/
│   │   ├── products/          # ← Vos 185 MB d'images ici
│   │   └── profile/
│   ├── media/                 # ← Uploads VichUploader
│   └── uploads/               # ← Autres uploads
│
├── joy-pharma-back/           # Code auto-déployé (peut être supprimé)
│   └── compose.yaml           # Monte les volumes vers /joy-pharma-data/
│
└── joy-pharma-admin/
```

---

## 🔧 Étapes de configuration

### Étape 1 : Créer les dossiers sur le serveur

```bash
# 1. Connectez-vous au serveur
ssh user@your-server

# 2. Créez la structure (À LA RACINE, pas dans joy-pharma-back/)
sudo mkdir -p /joy-pharma-data/images/products
sudo mkdir -p /joy-pharma-data/images/profile
sudo mkdir -p /joy-pharma-data/media
sudo mkdir -p /joy-pharma-data/uploads

# 3. Permissions (UID 82 = www-data dans FrankenPHP)
sudo chown -R 82:82 /joy-pharma-data/
sudo chmod -R 755 /joy-pharma-data/

# 4. Vérifier
ls -la /joy-pharma-data/
```

### Étape 2 : Copier vos images sur le serveur

**Depuis votre Mac** :

```bash
# Retour sur votre Mac
cd /Users/mac2016/Documents/GitHub/joy-pharma-back

# Créer une archive
tar -czf images.tar.gz public/images/

# Copier sur le serveur (dans /tmp temporairement)
scp images.tar.gz user@your-server:/tmp/

# Sur le serveur, extraire dans le bon dossier
ssh user@your-server
cd /tmp
tar -xzf images.tar.gz
sudo rsync -av public/images/ /joy-pharma-data/images/
sudo chown -R 82:82 /joy-pharma-data/images/
rm -rf public/ images.tar.gz

# Vérifier
sudo ls -lh /joy-pharma-data/images/products/ | head
```

### Étape 3 : Mettre à jour compose.yaml (FAIT ✅)

Le fichier `compose.yaml` a été mis à jour avec les volumes :

```yaml
volumes:
  - /joy-pharma-data/images:/app/public/images:rw
  - /joy-pharma-data/media:/app/public/media:rw
  - /joy-pharma-data/uploads:/app/public/uploads:rw
```

### Étape 4 : Commit et push

```bash
# Sur votre Mac
cd /Users/mac2016/Documents/GitHub/joy-pharma-back

git add compose.yaml
git commit -m "feat: ajouter volumes pour images persistantes"
git push
```

### Étape 5 : Redéployer sur le serveur

Le GitHub Actions va automatiquement :
1. Pull le nouveau `compose.yaml`
2. Redémarrer le container avec les volumes montés

**Ou manuellement** :

```bash
ssh user@your-server
cd ~/joy-pharma-back
git pull
docker compose down
docker compose up -d
```

---

## 🔍 Comment les URLs fonctionnent ?

### 1. MediaObject retourne une URL relative

**Code : `src/Entity/MediaObject.php`**

```php
public function getContentUrl(): ?string
{
    if ($this->filePath) {
        // Retourne : "/media/abc123.jpg"
        return '/media/' . $this->filePath;
    }
    return null;
}
```

**Réponse API** :

```json
{
  "id": 123,
  "name": "DOLIPRANE 1000MG",
  "image": {
    "contentUrl": "/media/abc123-uuid.jpg"
  }
}
```

### 2. Client construit l'URL complète

**Frontend** :

```javascript
// L'API retourne : "/media/abc123.jpg"
const imageUrl = product.image.contentUrl;

// Le client construit l'URL complète avec le domaine de l'API
const fullUrl = `https://preprod.joy-pharma.com${imageUrl}`;
// Résultat : "https://preprod.joy-pharma.com/media/abc123.jpg"

// Dans React/Vue
<img src={`${API_BASE_URL}${imageUrl}`} />
```

### 3. Traefik route vers le container PHP

```
Client demande:
https://preprod.joy-pharma.com/media/abc123.jpg
              ↓
Traefik vérifie Host: preprod.joy-pharma.com
              ↓
Route vers container joy-pharma-back-php (port 80)
              ↓
FrankenPHP cherche le fichier: /app/public/media/abc123.jpg
              ↓
Volume Docker: /app/public/media → /joy-pharma-data/media
              ↓
Fichier trouvé: /joy-pharma-data/media/abc123.jpg
              ↓
Image servie ! ✅
```

### 4. Magie des volumes Docker

```yaml
volumes:
  - /joy-pharma-data/media:/app/public/media:rw
```

**Cette ligne fait** :

| Sur le serveur | Dans le container | URL accessible |
|----------------|-------------------|----------------|
| `/joy-pharma-data/media/abc.jpg` | `/app/public/media/abc.jpg` | `https://preprod.joy-pharma.com/media/abc.jpg` |

---

## ✅ Vérification

### Test 1 : Dossier créé sur le serveur

```bash
ssh user@your-server
ls -la /joy-pharma-data/
# Devrait montrer: images/, media/, uploads/
```

### Test 2 : Images copiées

```bash
sudo ls -lh /joy-pharma-data/images/products/ | head
# Devrait montrer vos images
```

### Test 3 : Volume monté dans le container

```bash
cd ~/joy-pharma-back
docker compose exec php ls -lh /app/public/images/products/ | head
# Devrait montrer les MÊMES images
```

### Test 4 : API retourne les URLs

```bash
curl https://preprod.joy-pharma.com/api/products/1 | jq '.image.contentUrl'
# Devrait retourner: "/media/something.jpg"
```

### Test 5 : Image accessible via URL

```bash
curl -I https://preprod.joy-pharma.com/media/abc123.jpg
# Devrait retourner: HTTP/2 200 avec content-type: image/jpeg
```

---

## 🐛 Dépannage

### ❌ Problème : Images 404

**Cause** : Le volume n'est pas monté ou le fichier n'existe pas

```bash
# Vérifier que le volume est monté
docker compose exec php df -h | grep media

# Vérifier que les fichiers existent
docker compose exec php ls -la /app/public/media/

# Vérifier sur l'hôte
sudo ls -la /joy-pharma-data/media/
```

### ❌ Problème : Permission denied

**Cause** : Mauvaises permissions

```bash
# Corriger
sudo chown -R 82:82 /joy-pharma-data/
sudo chmod -R 755 /joy-pharma-data/

# Redémarrer le container
cd ~/joy-pharma-back
docker compose restart
```

### ❌ Problème : Volume vide après redémarrage

**Cause** : Le dossier n'existe pas sur l'hôte avant le montage

```bash
# Vérifier que le dossier existe AVANT de démarrer Docker
ls -la /joy-pharma-data/

# Si vide, recréer et recopier les images
sudo mkdir -p /joy-pharma-data/images/products
# ... recopier les images
```

---

## 📝 Checklist complète

- [ ] **Étape 1** : Créer `/joy-pharma-data/` sur le serveur
- [ ] **Étape 2** : Copier les 185 MB d'images dans `/joy-pharma-data/images/`
- [ ] **Étape 3** : Définir permissions : `sudo chown -R 82:82 /joy-pharma-data/`
- [ ] **Étape 4** : Mettre à jour `compose.yaml` avec les volumes (FAIT ✅)
- [ ] **Étape 5** : Commit et push le `compose.yaml`
- [ ] **Étape 6** : Redéployer sur le serveur
- [ ] **Test 1** : Vérifier que les volumes sont montés
- [ ] **Test 2** : Vérifier que les images sont accessibles via API
- [ ] **Test 3** : Tester l'accès HTTP direct aux images

---

## 🎉 Résultat final

### Avant

```
Client → API: GET /api/products/123
Response: { "image": { "contentUrl": "/media/abc.jpg" } }
Client → API: GET /media/abc.jpg
Response: 404 ❌ (image non trouvée)
```

### Après

```
Client → API: GET /api/products/123
Response: { "image": { "contentUrl": "/media/abc.jpg" } }

Client → API: GET /media/abc.jpg
Traefik → Container PHP
FrankenPHP → /app/public/media/abc.jpg
Volume → /joy-pharma-data/media/abc.jpg
Response: 200 ✅ (image servie !)
```

---

## 🚀 Script tout-en-un

**Sur votre Mac, créez ce script** :

```bash
#!/bin/bash
# setup-images-production.sh

SERVER="user@your-server"
SERVER_PATH="/joy-pharma-data"

echo "🚀 Configuration des images pour le serveur de production"

# 1. Créer la structure sur le serveur
echo "📁 Création de la structure sur le serveur..."
ssh $SERVER << 'EOF'
sudo mkdir -p /joy-pharma-data/images/products
sudo mkdir -p /joy-pharma-data/images/profile
sudo mkdir -p /joy-pharma-data/media
sudo mkdir -p /joy-pharma-data/uploads
sudo chown -R 82:82 /joy-pharma-data/
sudo chmod -R 755 /joy-pharma-data/
EOF

# 2. Créer l'archive des images
echo "📦 Création de l'archive..."
tar -czf /tmp/images.tar.gz -C public images/

# 3. Copier sur le serveur
echo "📤 Upload vers le serveur (185 MB, peut prendre quelques minutes)..."
scp /tmp/images.tar.gz $SERVER:/tmp/

# 4. Extraire dans le bon dossier
echo "📥 Extraction et mise en place..."
ssh $SERVER << 'EOF'
cd /tmp
tar -xzf images.tar.gz
sudo rsync -av --delete public/images/ /joy-pharma-data/images/
sudo chown -R 82:82 /joy-pharma-data/images/
rm -rf public/ images.tar.gz
echo "✅ $(sudo find /joy-pharma-data/images -type f | wc -l) fichiers copiés"
EOF

# 5. Nettoyer local
rm /tmp/images.tar.gz

echo "🎉 Configuration terminée !"
echo ""
echo "Prochaines étapes :"
echo "1. git add compose.yaml"
echo "2. git commit -m 'feat: volumes pour images persistantes'"
echo "3. git push"
echo "4. Le déploiement GitHub Actions va redémarrer avec les volumes"
```

**Utilisation** :

```bash
chmod +x setup-images-production.sh
./setup-images-production.sh
```

---

## 💡 Points importants

1. **`/joy-pharma-data/` est À LA RACINE**, pas dans `joy-pharma-back/`
2. **Les volumes survivent aux redéploiements** - les images ne sont jamais supprimées
3. **FrankenPHP sert les fichiers statiques** - pas besoin de Nginx séparé
4. **Traefik route tout vers le même container** - API et images
5. **UID 82 = www-data dans FrankenPHP** - permissions importantes

---

**Vous êtes prêt !** Suivez les étapes et vos images seront accessibles via l'API. 🚀

