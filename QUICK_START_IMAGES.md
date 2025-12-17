# 🚀 Quick Start : Images sur le Serveur avec Traefik

## ⚡ En 5 minutes

### 1️⃣ Créer les dossiers sur le serveur

```bash
ssh user@your-server << 'EOF'
sudo mkdir -p /joy-pharma-data/images/products
sudo mkdir -p /joy-pharma-data/images/profile  
sudo mkdir -p /joy-pharma-data/media
sudo mkdir -p /joy-pharma-data/uploads
sudo chown -R 82:82 /joy-pharma-data/
sudo chmod -R 755 /joy-pharma-data/
echo "✅ Dossiers créés !"
EOF
```

### 2️⃣ Copier vos images (depuis votre Mac)

```bash
cd /Users/mac2016/Documents/GitHub/joy-pharma-back

rsync -avz --progress \
  public/images/ \
  user@your-server:/tmp/images-upload/

echo "✅ Images uploadées dans /tmp !"
```

### 3️⃣ Installer les images

```bash
ssh user@your-server << 'EOF'
sudo cp -r /tmp/images-upload/* /joy-pharma-data/images/
sudo chown -R 82:82 /joy-pharma-data/
sudo chmod -R 755 /joy-pharma-data/
rm -rf /tmp/images-upload/
echo "✅ Images installées dans /joy-pharma-data/images/ !"
echo "📊 Nombre de fichiers :"
sudo find /joy-pharma-data/images -type f | wc -l
EOF
```

### 4️⃣ Redéployer l'application

```bash
ssh user@your-server "cd ~/joy-pharma-back && ./deploy.sh"
```

### 5️⃣ Tester

```bash
# Remplacer par un vrai nom de fichier
curl -I https://api.joypharma.com/images/products/placeholder.png
```

Si vous voyez `HTTP/2 200`, c'est bon ! 🎉

---

## 🗺️ Architecture

```
┌─────────────────────────────────────────────────┐
│  Internet                                        │
└─────────────────┬───────────────────────────────┘
                  │
                  ↓
┌─────────────────────────────────────────────────┐
│  Traefik (Port 443)                             │
│  https://api.joypharma.com                      │
└─────────────────┬───────────────────────────────┘
                  │
                  ↓
┌─────────────────────────────────────────────────┐
│  Container PHP (FrankenPHP)                     │
│  /app/public/images/ ────┐                      │
│  /app/public/media/  ────┤  Volumes montés      │
│  /app/public/uploads/────┘                      │
└─────────────────┬───────────────────────────────┘
                  │
                  ↓
┌─────────────────────────────────────────────────┐
│  Serveur - Stockage persistant                  │
│  /joy-pharma-data/images/    ← VOS IMAGES       │
│  /joy-pharma-data/media/                        │
│  /joy-pharma-data/uploads/                      │
└─────────────────────────────────────────────────┘
```

---

## 📂 Structure des fichiers

### Serveur : `/joy-pharma-data/`

```
/joy-pharma-data/
├── images/
│   ├── products/          ← Vos 185 MB d'images produits
│   │   ├── image1.jpg
│   │   ├── image2.png
│   │   └── ...
│   └── profile/           ← Images de profil
├── media/                 ← Uploads VichUploader (MediaObject)
└── uploads/               ← Autres uploads
```

### URLs accessibles

| Fichier serveur | URL publique |
|----------------|--------------|
| `/joy-pharma-data/images/products/doliprane.jpg` | `https://api.joypharma.com/images/products/doliprane.jpg` |
| `/joy-pharma-data/images/profile/user-123.jpg` | `https://api.joypharma.com/images/profile/user-123.jpg` |
| `/joy-pharma-data/media/abc123.jpg` | `https://api.joypharma.com/media/abc123.jpg` |

---

## ✅ Vérifications

### 1. Vérifier les fichiers dans le container

```bash
ssh user@your-server
cd ~/joy-pharma-back
docker compose -f compose.yaml -f compose.prod.yaml exec php ls -lh /app/public/images/products/ | head
```

### 2. Vérifier les volumes

```bash
docker compose -f compose.yaml -f compose.prod.yaml exec php df -h | grep images
```

Devrait afficher :
```
/dev/sda1    50G   10G   40G   20%   /app/public/images
```

### 3. Tester HTTP

```bash
curl -I https://api.joypharma.com/images/products/placeholder.png
```

Réponse attendue :
```
HTTP/2 200
content-type: image/png
content-length: 12345
```

---

## 🔧 Configuration (déjà faite ✅)

Votre `docker-compose.prod.example.yml` contient déjà :

```yaml
services:
  php:
    volumes:
      - /joy-pharma-data/images:/app/public/images:rw
      - /joy-pharma-data/media:/app/public/media:rw
      - /joy-pharma-data/uploads:/app/public/uploads:rw
```

**Aucune modification nécessaire !**

---

## 🐛 Problèmes ?

### Images 404

```bash
# Vérifier les permissions
ssh user@your-server "sudo ls -la /joy-pharma-data/images/"

# Corriger si nécessaire
ssh user@your-server "sudo chown -R 82:82 /joy-pharma-data/ && sudo chmod -R 755 /joy-pharma-data/"
```

### Volume vide

```bash
# Vérifier la configuration
ssh user@your-server "cat ~/joy-pharma-back/compose.prod.yaml | grep -A 5 volumes"

# Redémarrer
ssh user@your-server "cd ~/joy-pharma-back && docker compose -f compose.yaml -f compose.prod.yaml restart"
```

### Logs

```bash
# Voir les logs du container
ssh user@your-server "cd ~/joy-pharma-back && docker compose -f compose.yaml -f compose.prod.yaml logs -f php"
```

---

## 📞 Script complet (copier-coller)

**Remplacez `user@your-server` par vos vraies infos !**

```bash
#!/bin/bash
SERVER="user@your-server"

echo "🚀 Installation des images sur le serveur..."

# 1. Créer les dossiers
echo "1/5 Création des dossiers..."
ssh $SERVER << 'EOF'
sudo mkdir -p /joy-pharma-data/{images/products,images/profile,media,uploads}
sudo chown -R 82:82 /joy-pharma-data/
sudo chmod -R 755 /joy-pharma-data/
EOF

# 2. Upload
echo "2/5 Upload des images..."
rsync -avz --progress \
  public/images/ \
  $SERVER:/tmp/images-upload/

# 3. Installation
echo "3/5 Installation des images..."
ssh $SERVER << 'EOF'
sudo cp -r /tmp/images-upload/* /joy-pharma-data/images/
sudo chown -R 82:82 /joy-pharma-data/
sudo chmod -R 755 /joy-pharma-data/
rm -rf /tmp/images-upload/
echo "Fichiers installés: $(sudo find /joy-pharma-data/images -type f | wc -l)"
EOF

# 4. Redéploiement
echo "4/5 Redéploiement de l'application..."
ssh $SERVER "cd ~/joy-pharma-back && ./deploy.sh"

# 5. Test
echo "5/5 Test de l'accès..."
sleep 5
curl -I https://api.joypharma.com/images/products/placeholder.png

echo ""
echo "🎉 Terminé ! Vos images sont accessibles à :"
echo "https://api.joypharma.com/images/products/..."
```

Enregistrez ce script dans `install-images.sh` et exécutez :

```bash
chmod +x install-images.sh
./install-images.sh
```

---

## 📚 Documentation complète

Pour plus de détails, voir : **[docs/setup-images-with-traefik.md](docs/setup-images-with-traefik.md)**

---

## ⏱️ Temps estimé

- Création des dossiers : 10 secondes
- Upload des images (185 MB) : 2-5 minutes (selon connexion)
- Installation : 30 secondes
- Redéploiement : 1-2 minutes

**Total : ~5-10 minutes** ⚡

