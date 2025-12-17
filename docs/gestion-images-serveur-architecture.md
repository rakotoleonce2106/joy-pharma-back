# 📁 Gestion des Images - Architecture Serveur Actuelle

## 🏗️ Votre architecture actuelle

```
/
├── traefik/                    # Reverse proxy
├── infrastructure/             # PostgreSQL + pgAdmin
├── joy-pharma-back/           # Backend API (auto-déployé)
└── joy-pharma-admin/          # Admin frontend (auto-déployé)
```

## ✅ Solution recommandée pour votre architecture

Créer un dossier dédié aux **données persistantes** au même niveau que vos applications :

```
/
├── traefik/
├── infrastructure/
├── joy-pharma-back/           # Code backend (remplacé à chaque déploiement)
├── joy-pharma-admin/          # Code admin (remplacé à chaque déploiement)
│
└── joy-pharma-data/           # 🆕 Données persistantes (NE CHANGE JAMAIS)
    ├── images/                # Images produits
    │   ├── products/
    │   └── profile/
    ├── media/                 # Autres médias
    └── uploads/               # Uploads utilisateurs
```

---

## 🚀 Mise en place étape par étape

### Étape 1 : Créer le dossier de données sur le serveur

```bash
# Se connecter au serveur
ssh user@your-server

# Créer la structure
sudo mkdir -p /joy-pharma-data/images/products
sudo mkdir -p /joy-pharma-data/images/profile
sudo mkdir -p /joy-pharma-data/media
sudo mkdir -p /joy-pharma-data/uploads

# Définir les permissions
sudo chown -R www-data:www-data /joy-pharma-data/
sudo chmod -R 755 /joy-pharma-data/

# Vérifier
ls -la /joy-pharma-data/
```

### Étape 2 : Copier vos images depuis votre Mac

```bash
# Depuis votre machine locale
cd /Users/mac2016/Documents/GitHub/joy-pharma-back

# Synchroniser les images (185 MB)
rsync -avz --progress \
  public/images/ \
  user@your-server:/joy-pharma-data/images/

# Vérifier
ssh user@your-server "ls -lh /joy-pharma-data/images/products/ | head -20"
```

### Étape 3 : Modifier le docker-compose.yml dans joy-pharma-back

Le fichier `joy-pharma-back/docker-compose.yml` sur votre serveur doit monter ce dossier :

```yaml
# /joy-pharma-back/docker-compose.yml
version: '3.8'

services:
  php:
    image: ${DOCKER_IMAGE:-registry.example.com/joy-pharma-back:latest}
    container_name: joy-pharma-backend
    
    # 👇 Ajouter ces volumes
    volumes:
      # Données persistantes (ne changent pas avec les déploiements)
      - /joy-pharma-data/images:/app/public/images:rw
      - /joy-pharma-data/media:/app/public/media:rw
      - /joy-pharma-data/uploads:/app/public/uploads:rw
    
    networks:
      - traefik-network
      - infrastructure_default  # Pour accéder à PostgreSQL
    
    environment:
      APP_ENV: ${APP_ENV:-prod}
      DATABASE_URL: ${DATABASE_URL}
      JWT_SECRET_KEY: ${JWT_SECRET_KEY}
      JWT_PUBLIC_KEY: ${JWT_PUBLIC_KEY}
    
    labels:
      - "traefik.enable=true"
      - "traefik.http.routers.joy-pharma-backend.rule=Host(`api.joypharma.com`)"
      - "traefik.http.routers.joy-pharma-backend.entrypoints=websecure"
      - "traefik.http.routers.joy-pharma-backend.tls.certresolver=letsencrypt"
      - "traefik.http.services.joy-pharma-backend.loadbalancer.server.port=80"
    
    restart: unless-stopped

networks:
  traefik-network:
    external: true
  infrastructure_default:
    external: true
```

### Étape 4 : Mettre à jour le script deploy.sh

Modifier `/joy-pharma-back/deploy.sh` pour ne PAS supprimer le dossier de données :

```bash
#!/bin/bash
# /joy-pharma-back/deploy.sh

set -e

echo "🚀 Déploiement de Joy Pharma Backend..."

# Charger les variables d'environnement
if [ -f .env ]; then
    export $(cat .env | grep -v '^#' | xargs)
fi

# Pull la dernière image
echo "📦 Pull de l'image Docker..."
docker pull ${DOCKER_IMAGE}

# Arrêter l'ancien container
echo "⏹️  Arrêt de l'ancien container..."
docker compose down

# ⚠️ NE PAS supprimer /joy-pharma-data/ ici !
# Les images sont stockées dans /joy-pharma-data/ qui est HORS du projet

# Démarrer le nouveau container
echo "▶️  Démarrage du nouveau container..."
docker compose up -d

# Attendre que le container soit prêt
echo "⏳ Attente du démarrage..."
sleep 5

# Exécuter les migrations
echo "📊 Exécution des migrations..."
docker compose exec -T php php bin/console doctrine:migrations:migrate --no-interaction

# Vérifier la santé
echo "🏥 Vérification de la santé..."
docker compose ps

echo "✅ Déploiement terminé !"
echo "🔍 Logs : docker compose logs -f php"
```

### Étape 5 : Configuration Nginx/Traefik pour servir les images

Si vous utilisez Nginx en plus de Traefik, ajoutez cette configuration :

```nginx
# /joy-pharma-back/nginx.conf (si vous avez Nginx)
server {
    listen 80;
    server_name api.joypharma.com;
    
    root /app/public;
    index index.php;
    
    # Servir les images statiques directement
    location /images/ {
        alias /app/public/images/;
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
    }
    
    location /media/ {
        alias /app/public/media/;
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
    }
    
    # PHP
    location / {
        try_files $uri /index.php$is_args$args;
    }
    
    location ~ ^/index\.php(/|$) {
        fastcgi_pass php:9000;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
    }
}
```

### Étape 6 : Redéployer

```bash
# Sur le serveur
cd /joy-pharma-back

# Redémarrer avec les nouveaux volumes
docker compose down
docker compose up -d

# Vérifier que le volume est bien monté
docker compose exec php ls -lh /app/public/images/products/ | head -20

# Vérifier les permissions
docker compose exec php ls -la /app/public/images/
```

---

## 🔍 Vérification complète

### 1. Vérifier que les images sont accessibles

```bash
# Depuis le container PHP
ssh user@your-server
cd /joy-pharma-back
docker compose exec php ls -lh /app/public/images/products/ | head

# Depuis l'hôte
ls -lh /joy-pharma-data/images/products/ | head
```

### 2. Tester l'accès HTTP

```bash
# Tester une image
curl -I https://api.joypharma.com/images/products/test-image.jpg

# Devrait retourner 200 OK
```

### 3. Vérifier les logs

```bash
cd /joy-pharma-back
docker compose logs -f php | grep -i image
```

---

## 📊 Architecture finale

```
/
├── traefik/                           # Reverse proxy (Traefik)
│   └── docker-compose.yml             # Labels: port 80, 443
│
├── infrastructure/                    # Base de données
│   └── docker-compose.yml             # PostgreSQL + pgAdmin
│
├── joy-pharma-data/                   # 🆕 Données persistantes
│   ├── images/
│   │   ├── products/                  # ← Vos 185 MB d'images ici
│   │   └── profile/
│   ├── media/
│   └── uploads/
│
├── joy-pharma-back/                   # Backend (auto-déployé)
│   ├── docker-compose.yml             # Monte /joy-pharma-data/images
│   ├── deploy.sh                      # Script de déploiement
│   └── .env
│
└── joy-pharma-admin/                  # Admin (auto-déployé)
    ├── docker-compose.yml
    └── deploy.sh
```

### Flux de requête pour une image

```
Navigateur
    ↓
    GET https://api.joypharma.com/images/products/doliprane.jpg
    ↓
Traefik (Port 443)
    ↓
Container PHP (joy-pharma-back)
    ↓
Volume monté: /app/public/images → /joy-pharma-data/images
    ↓
Fichier: /joy-pharma-data/images/products/doliprane.jpg
    ↓
Réponse 200 OK + Image
```

---

## 🔄 Workflow de synchronisation des images

### Script de synchronisation automatique

Créez ce script sur votre Mac :

```bash
#!/bin/bash
# sync-images.sh

SERVER="user@your-server"
LOCAL_PATH="/Users/mac2016/Documents/GitHub/joy-pharma-back/public/images/"
REMOTE_PATH="/joy-pharma-data/images/"

echo "🚀 Synchronisation des images vers le serveur..."

# Synchroniser avec rsync (ne copie que les différences)
rsync -avz --progress --delete \
  --exclude='.DS_Store' \
  --exclude='*.tmp' \
  "$LOCAL_PATH" \
  "$SERVER:$REMOTE_PATH"

# Ajuster les permissions
ssh $SERVER "sudo chown -R www-data:www-data $REMOTE_PATH && sudo chmod -R 755 $REMOTE_PATH"

echo "✅ Synchronisation terminée !"
echo "📊 Vérification..."

# Compter les fichiers
ssh $SERVER "find $REMOTE_PATH -type f | wc -l"
```

**Utilisation** :

```bash
chmod +x sync-images.sh
./sync-images.sh
```

---

## 🎯 Avantages de cette architecture

| ✅ Avantage | Description |
|-------------|-------------|
| **Séparation des préoccupations** | Code ≠ Données |
| **Déploiements sûrs** | Les images ne sont jamais supprimées |
| **Performance** | Images servies directement (pas de PHP) |
| **Scalabilité** | Facile de migrer vers un CDN plus tard |
| **Backup facile** | `tar -czf backup.tar.gz /joy-pharma-data/` |
| **Cohérence** | Même structure pour tous les environnements |

---

## 🔐 Permissions et sécurité

### Permissions recommandées

```bash
# Dossier principal
sudo chown -R www-data:www-data /joy-pharma-data/
sudo chmod -R 755 /joy-pharma-data/

# Fichiers (lecture seule pour tout le monde)
sudo find /joy-pharma-data/images -type f -exec chmod 644 {} \;

# Dossiers (exécution pour traverser)
sudo find /joy-pharma-data/images -type d -exec chmod 755 {} \;
```

### Sécurité

```nginx
# Ne pas permettre l'exécution de PHP dans /images/
location ~* ^/images/.*\.php$ {
    deny all;
}

# Limiter les types de fichiers
location /images/ {
    location ~* \.(jpg|jpeg|png|gif|webp|svg)$ {
        # OK
    }
    location ~ {
        deny all;  # Bloquer tout le reste
    }
}
```

---

## 🆘 Migration depuis l'ancien système

Si vous avez déjà des images quelque part, voici comment migrer :

```bash
# Sur le serveur
ssh user@your-server

# Si les images étaient dans le container Docker
OLD_CONTAINER_ID=$(docker ps -a -q --filter "name=joy-pharma-backend")
docker cp $OLD_CONTAINER_ID:/app/public/images /joy-pharma-data/

# Ajuster les permissions
sudo chown -R www-data:www-data /joy-pharma-data/images/
sudo chmod -R 755 /joy-pharma-data/images/
```

---

## 📋 Checklist de mise en place

### Sur le serveur

- [ ] Créer `/joy-pharma-data/images/`
- [ ] Définir les permissions (www-data:www-data, 755)
- [ ] Modifier `/joy-pharma-back/docker-compose.yml` pour ajouter les volumes
- [ ] Vérifier que `deploy.sh` ne supprime pas `/joy-pharma-data/`

### Depuis votre Mac

- [ ] Synchroniser les images avec rsync
- [ ] Vérifier que les images sont bien copiées
- [ ] Tester l'accès HTTP

### Vérification finale

- [ ] Les images s'affichent sur le frontend
- [ ] Les uploads fonctionnent
- [ ] Les permissions sont correctes
- [ ] Le déploiement n'efface pas les images

---

## 🚀 Commandes complètes pour tout faire maintenant

```bash
# ===== 1. SUR LE SERVEUR =====
ssh user@your-server << 'EOF'
  # Créer la structure
  sudo mkdir -p /joy-pharma-data/images/products
  sudo mkdir -p /joy-pharma-data/images/profile
  sudo mkdir -p /joy-pharma-data/media
  sudo mkdir -p /joy-pharma-data/uploads
  
  # Permissions
  sudo chown -R www-data:www-data /joy-pharma-data/
  sudo chmod -R 755 /joy-pharma-data/
  
  # Vérifier
  ls -la /joy-pharma-data/
EOF

# ===== 2. DEPUIS VOTRE MAC =====
cd /Users/mac2016/Documents/GitHub/joy-pharma-back

# Synchroniser les images
rsync -avz --progress \
  public/images/ \
  user@your-server:/joy-pharma-data/images/

# ===== 3. MODIFIER docker-compose.yml SUR LE SERVEUR =====
# (Voir le contenu ci-dessus)
ssh user@your-server "nano /joy-pharma-back/docker-compose.yml"
# Ajouter les lignes volumes:

# ===== 4. REDÉMARRER =====
ssh user@your-server << 'EOF'
  cd /joy-pharma-back
  docker compose down
  docker compose up -d
  
  # Vérifier
  docker compose exec php ls -lh /app/public/images/products/ | head
EOF

# ===== 5. TESTER =====
curl -I https://api.joypharma.com/images/products/test-image.jpg
```

---

## 💡 Prochaine étape (optionnel)

Une fois que ça fonctionne bien, vous pouvez :

1. **Ajouter un CDN** (Cloudflare, DigitalOcean CDN)
2. **Backup automatique** de `/joy-pharma-data/`
3. **Optimisation des images** (compression, WebP)
4. **Cache Nginx/Traefik** pour les images

---

**Résumé** : Créez `/joy-pharma-data/images/` sur votre serveur, copiez vos images dedans avec rsync, et montez ce dossier dans le docker-compose.yml de joy-pharma-back ! 🎉

