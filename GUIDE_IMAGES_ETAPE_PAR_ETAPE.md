# 🔧 Guide étape par étape - Images sur le serveur

## ❌ Problème : "Rien n'a été créé"

Voici comment résoudre ça **étape par étape**.

---

## ✅ Solution : Commandes une par une

### Étape 1 : Se connecter au serveur

```bash
# Remplacez par vos vraies informations
ssh user@your-server
```

### Étape 2 : Vérifier que vous avez les droits sudo

```bash
sudo whoami
# Devrait afficher : root
```

Si ça demande un mot de passe, entrez-le. Si ça dit "permission denied", contactez l'administrateur du serveur.

### Étape 3 : Créer le dossier principal

```bash
# Créer le dossier principal
sudo mkdir -p /joy-pharma-data

# Vérifier qu'il existe
ls -la / | grep joy-pharma-data
# Devrait afficher une ligne avec joy-pharma-data
```

### Étape 4 : Créer les sous-dossiers

```bash
# Créer tous les sous-dossiers
sudo mkdir -p /joy-pharma-data/images/products
sudo mkdir -p /joy-pharma-data/images/profile
sudo mkdir -p /joy-pharma-data/media
sudo mkdir -p /joy-pharma-data/uploads

# Vérifier
ls -la /joy-pharma-data/
# Devrait afficher: images, media, uploads
```

### Étape 5 : Définir les permissions

```bash
# UID 82 = www-data dans FrankenPHP
sudo chown -R 82:82 /joy-pharma-data/
sudo chmod -R 755 /joy-pharma-data/

# Vérifier les permissions
ls -ld /joy-pharma-data/
# Devrait afficher: drwxr-xr-x ... 82 82 ... /joy-pharma-data/
```

### Étape 6 : Vérification finale

```bash
# Afficher la structure complète
sudo tree /joy-pharma-data/ -L 2
# Ou si tree n'est pas installé :
find /joy-pharma-data/ -type d

# Devrait afficher :
# /joy-pharma-data/
# /joy-pharma-data/images
# /joy-pharma-data/images/products
# /joy-pharma-data/images/profile
# /joy-pharma-data/media
# /joy-pharma-data/uploads
```

✅ **Si vous voyez cette structure, c'est bon !**

---

## 📤 Copier les images

### Méthode 1 : Avec le script automatique (recommandé)

**Sur votre Mac** :

```bash
cd /Users/mac2016/Documents/GitHub/joy-pharma-back

# Rendre le script exécutable
chmod +x setup-images-server.sh

# Lancer le script (remplacez par votre serveur)
./setup-images-server.sh user@your-server
```

Le script va :
- ✅ Créer la structure
- ✅ Créer l'archive des images
- ✅ L'uploader sur le serveur
- ✅ L'extraire au bon endroit
- ✅ Définir les permissions

### Méthode 2 : Manuellement

**Sur votre Mac** :

```bash
cd /Users/mac2016/Documents/GitHub/joy-pharma-back

# 1. Créer l'archive
tar -czf ~/images.tar.gz -C public images/

# 2. Vérifier la taille
du -h ~/images.tar.gz
# Devrait afficher environ 50-80 MB (compressé)

# 3. Copier sur le serveur
scp ~/images.tar.gz user@your-server:/tmp/

# Devrait afficher une barre de progression
```

**Sur le serveur** :

```bash
ssh user@your-server

# 1. Aller dans /tmp
cd /tmp

# 2. Vérifier que l'archive est là
ls -lh images.tar.gz

# 3. Extraire
tar -xzf images.tar.gz

# 4. Vérifier l'extraction
ls -la public/images/

# 5. Copier dans le bon dossier
sudo rsync -av public/images/ /joy-pharma-data/images/

# 6. Permissions
sudo chown -R 82:82 /joy-pharma-data/images/
sudo chmod -R 755 /joy-pharma-data/images/

# 7. Vérifier
sudo ls -lh /joy-pharma-data/images/products/ | head

# 8. Compter les fichiers
sudo find /joy-pharma-data/images -type f | wc -l

# 9. Nettoyer
rm -rf public/ images.tar.gz
```

---

## 🔍 Vérifications importantes

### 1. Vérifier que le dossier existe

```bash
ssh user@your-server
ls -la /joy-pharma-data/
```

**Devrait afficher** :

```
drwxr-xr-x  5 82 82 4096 Dec 17 16:00 .
drwxr-xr-x 20 root root 4096 Dec 17 15:55 ..
drwxr-xr-x  4 82 82 4096 Dec 17 16:00 images
drwxr-xr-x  2 82 82 4096 Dec 17 15:55 media
drwxr-xr-x  2 82 82 4096 Dec 17 15:55 uploads
```

### 2. Vérifier les permissions

```bash
sudo stat /joy-pharma-data/
```

**Devrait afficher** :
- Uid: ( 82/ UNKNOWN)
- Gid: ( 82/ UNKNOWN)

### 3. Vérifier les images

```bash
# Compter les fichiers
sudo find /joy-pharma-data/images -type f | wc -l

# Lister quelques exemples
sudo ls -lh /joy-pharma-data/images/products/ | head -10
```

---

## 🐛 Problèmes courants

### ❌ "Permission denied" lors de mkdir

**Problème** : Vous n'avez pas les droits sudo

**Solution** :

```bash
# Vérifier vos droits
sudo -l

# Si ça ne marche pas, demandez à l'admin d'exécuter :
sudo usermod -aG sudo votre-username
# Puis déconnectez-vous et reconnectez-vous
```

### ❌ Le dossier se crée mais disparaît

**Problème** : Il y a peut-être un montage ou un autre container qui utilise ce chemin

**Solution** :

```bash
# Vérifier les montages
df -h | grep joy-pharma

# Utiliser un autre chemin
sudo mkdir -p /srv/joy-pharma-data/
# Et utilisez ce chemin dans compose.yaml
```

### ❌ "No such file or directory" après création

**Problème** : La commande n'a pas vraiment été exécutée

**Solution** :

```bash
# Vérifier que vous êtes bien sur le serveur
hostname
# Devrait afficher le nom de votre serveur

# Re-créer explicitement
sudo mkdir -p /joy-pharma-data/images/products
sudo mkdir -p /joy-pharma-data/images/profile
sudo mkdir -p /joy-pharma-data/media
sudo mkdir -p /joy-pharma-data/uploads

# Vérifier immédiatement après
ls -la /joy-pharma-data/ && echo "✅ Dossier créé !" || echo "❌ Erreur"
```

### ❌ UID 82 n'existe pas

**Problème** : Sur certains systèmes, l'UID 82 peut ne pas exister

**Solution** :

```bash
# Option 1 : Utiliser www-data (si disponible)
sudo chown -R www-data:www-data /joy-pharma-data/

# Option 2 : Utiliser votre user
sudo chown -R $(whoami):$(whoami) /joy-pharma-data/

# Option 3 : Créer l'UID 82
sudo groupadd -g 82 www-data 2>/dev/null || true
sudo useradd -u 82 -g 82 -M -s /sbin/nologin www-data 2>/dev/null || true
sudo chown -R 82:82 /joy-pharma-data/
```

---

## 📋 Checklist de vérification

Cochez chaque étape :

- [ ] Je peux me connecter au serveur via SSH
- [ ] J'ai les droits sudo sur le serveur
- [ ] `/joy-pharma-data/` existe sur le serveur
- [ ] Les sous-dossiers `images/`, `media/`, `uploads/` existent
- [ ] Les permissions sont 755 et le propriétaire est 82:82
- [ ] Les images ont été copiées dans `/joy-pharma-data/images/`
- [ ] Je vois les fichiers avec `sudo ls /joy-pharma-data/images/products/`
- [ ] Le `compose.yaml` a été mis à jour avec les volumes
- [ ] J'ai commit et push le `compose.yaml`

---

## 🆘 Si rien ne marche

### Option alternative : Utiliser un autre chemin

Si `/joy-pharma-data/` pose problème, utilisez `/srv/` ou votre home :

```bash
# Sur le serveur
mkdir -p ~/joy-pharma-data/images/products
mkdir -p ~/joy-pharma-data/images/profile
mkdir -p ~/joy-pharma-data/media
mkdir -p ~/joy-pharma-data/uploads

chmod -R 755 ~/joy-pharma-data/

# Obtenir le chemin absolu
realpath ~/joy-pharma-data/
# Exemple : /home/ubuntu/joy-pharma-data
```

Puis dans `compose.yaml`, utilisez ce chemin :

```yaml
volumes:
  - /home/ubuntu/joy-pharma-data/images:/app/public/images:rw
  - /home/ubuntu/joy-pharma-data/media:/app/public/media:rw
  - /home/ubuntu/joy-pharma-data/uploads:/app/public/uploads:rw
```

---

## 💬 Besoin d'aide ?

**Envoyez-moi le résultat de ces commandes** :

```bash
# Sur le serveur
ssh user@your-server << 'EOF'
echo "=== Informations système ==="
uname -a
echo ""
echo "=== Droits sudo ==="
sudo -l | head -5
echo ""
echo "=== Contenu de / ==="
ls -la / | grep joy
echo ""
echo "=== Espace disque ==="
df -h /
echo ""
echo "=== User actuel ==="
whoami
id
EOF
```

Avec ces informations, je pourrai vous aider à diagnostiquer le problème !

