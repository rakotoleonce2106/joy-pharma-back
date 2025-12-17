# 🚀 Instructions de Déploiement - Joy Pharma Backend

## 📋 Prérequis sur le serveur

Votre serveur doit avoir cette structure :

```
/
├── traefik/                    # Reverse proxy (déjà en place)
├── infrastructure/             # PostgreSQL (déjà en place)
├── joy-pharma-back/           # Application backend
└── joy-pharma-data/           # Données persistantes (À CRÉER)
```

---

## 🎯 Setup initial (à faire UNE SEULE FOIS)

### Étape 1 : Créer le dossier de données sur le serveur

```bash
ssh user@your-server

# Créer la structure des données persistantes
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

### Étape 2 : Copier le fichier docker-compose.yml correct sur le serveur

```bash
# Copier docker-compose.prod.example.yml vers le serveur
scp docker-compose.prod.example.yml user@your-server:/joy-pharma-back/docker-compose.yml
```

Ou manuellement sur le serveur :

```bash
ssh user@your-server
cd /joy-pharma-back
nano docker-compose.yml
# Coller le contenu de docker-compose.prod.example.yml
```

### Étape 3 : Vérifier le fichier .env sur le serveur

```bash
ssh user@your-server
cd /joy-pharma-back
cat .env
```

Doit contenir au minimum :

```bash
# .env sur le serveur
DOCKER_IMAGE=your-registry/joy-pharma-backend:latest
APP_ENV=prod
APP_SECRET=your-secret-key
DATABASE_URL=postgresql://user:pass@postgres:5432/joy_pharma
API_DOMAIN=api.joypharma.com
CORS_ALLOW_ORIGIN=https://admin.joypharma.com,https://app.joypharma.com

# JWT
JWT_SECRET_KEY=/app/config/jwt/private.pem
JWT_PUBLIC_KEY=/app/config/jwt/public.pem
JWT_PASSPHRASE=your-passphrase

# Elasticsearch
ELASTICSEARCH_HOST=http://elasticsearch:9200
```

---

## 📤 Upload des images (à faire UNE SEULE FOIS)

### Depuis votre Mac vers le serveur

```bash
cd /Users/mac2016/Documents/GitHub/joy-pharma-back

# Option 1 : Rsync (recommandé)
rsync -avz --progress \
  public/images/ \
  user@your-server:/joy-pharma-data/images/

# Option 2 : SCP (plus simple mais plus lent)
scp -r public/images/* user@your-server:/joy-pharma-data/images/

# Ajuster les permissions sur le serveur
ssh user@your-server "sudo chown -R www-data:www-data /joy-pharma-data/images/ && sudo chmod -R 755 /joy-pharma-data/images/"
```

### Vérifier que les images sont bien copiées

```bash
ssh user@your-server "ls -lh /joy-pharma-data/images/products/ | head -20"
```

---

## 🔄 Déploiement automatique via GitHub Actions

### Workflow actuel (.github/workflows/deploy-backend.yml)

Le workflow GitHub Actions fait déjà :
1. ✅ Build de l'image Docker (sans les images grâce à .dockerignore)
2. ✅ Push vers le registry
3. ✅ Connexion au serveur
4. ✅ Pull de la nouvelle image
5. ✅ Redémarrage du container

### Ce que le workflow NE fait PAS (et c'est bien !) :

- ❌ Ne touche PAS à `/joy-pharma-data/` (les images restent intactes)
- ❌ Ne supprime PAS les images lors du déploiement
- ❌ Ne modifie PAS les uploads utilisateurs

---

## 🔍 Vérifications après déploiement

### 1. Vérifier que le container tourne

```bash
ssh user@your-server
cd /joy-pharma-back
docker compose ps
```

### 2. Vérifier que les volumes sont montés

```bash
docker compose exec php ls -lh /app/public/images/products/ | head -20
```

### 3. Vérifier les logs

```bash
docker compose logs -f php
```

### 4. Tester l'accès aux images via HTTP

```bash
# Remplacer par une vraie image
curl -I https://api.joypharma.com/images/products/test-image.jpg

# Devrait retourner : HTTP/2 200
```

### 5. Tester l'API

```bash
# Health check
curl https://api.joypharma.com/api/health

# Liste des produits
curl https://api.joypharma.com/api/products
```

---

## 🆘 Dépannage

### Problème : Les images ne s'affichent pas (404)

**Solution 1 : Vérifier les permissions**

```bash
ssh user@your-server
sudo chown -R www-data:www-data /joy-pharma-data/images/
sudo chmod -R 755 /joy-pharma-data/images/
docker compose restart
```

**Solution 2 : Vérifier que le volume est monté**

```bash
docker compose exec php df -h | grep images
docker compose exec php ls -la /app/public/images/
```

**Solution 3 : Vérifier les logs Traefik**

```bash
cd /traefik
docker compose logs -f traefik | grep -i image
```

### Problème : Container ne démarre pas

```bash
# Voir les logs
docker compose logs php

# Vérifier la config
docker compose config

# Recréer le container
docker compose down
docker compose up -d
```

### Problème : Erreur de connexion à la base de données

```bash
# Vérifier que PostgreSQL tourne
cd /infrastructure
docker compose ps

# Tester la connexion depuis le container PHP
docker compose exec php php bin/console dbal:run-sql "SELECT 1"
```

### Problème : Images trop volumineuses dans le build Docker

**C'est normal maintenant !** Le `.dockerignore` exclut les images du build.

Vérifier :

```bash
cat .dockerignore | grep images
# Devrait afficher : public/images/
```

---

## 🔄 Workflow complet de déploiement

### 1. Développement local

```bash
# Faire vos modifications
git add .
git commit -m "feat: nouvelle fonctionnalité"
git push origin main
```

### 2. GitHub Actions s'exécute automatiquement

- Build de l'image Docker (sans les images)
- Push vers le registry
- Déploiement sur le serveur
- Exécution des migrations

### 3. Vérification

```bash
# Vérifier que le déploiement a réussi
# Aller sur : https://github.com/your-org/joy-pharma-back/actions

# Tester l'API
curl https://api.joypharma.com/api/products
```

---

## 📊 Comparaison avant/après

| Aspect | Avant | Après |
|--------|-------|-------|
| **Images dans Docker** | ✅ Oui (800 MB) | ❌ Non (500 MB) |
| **Temps de build** | 🔴 5-10 min | ✅ 2-3 min |
| **Images perdues au redéploiement** | 🔴 Oui | ✅ Non |
| **Stockage séparé** | ❌ Non | ✅ Oui |
| **Facile de backup** | 🔴 Non | ✅ Oui |

---

## 🎯 Architecture finale

```
Requête HTTP
    ↓
[Traefik] :443 → Route vers joy-pharma-backend
    ↓
[Container PHP] → Lecture de /app/public/images/
    ↓
[Volume monté] → /app/public/images → /joy-pharma-data/images/
    ↓
[Fichier sur disque] /joy-pharma-data/images/products/image.jpg
    ↓
Réponse 200 OK + Image
```

---

## 📝 Checklist de mise en production

### Setup initial (une seule fois)

- [ ] Créer `/joy-pharma-data/` sur le serveur
- [ ] Copier `docker-compose.prod.example.yml` → `/joy-pharma-back/docker-compose.yml`
- [ ] Vérifier le `.env` sur le serveur
- [ ] Upload des images initiales (185 MB)
- [ ] Ajuster les permissions

### À chaque déploiement (automatique via GitHub)

- [ ] Push vers GitHub
- [ ] GitHub Actions build et deploy
- [ ] Vérifier les logs
- [ ] Tester l'API
- [ ] Vérifier que les images s'affichent

### Maintenance régulière

- [ ] Backup de `/joy-pharma-data/` (hebdomadaire)
- [ ] Vérifier l'espace disque
- [ ] Optimiser les images si nécessaire
- [ ] Nettoyer les anciennes images Docker

---

## 🔐 Backup des données

### Script de backup automatique

```bash
#!/bin/bash
# backup-images.sh (à mettre sur le serveur)

BACKUP_DIR="/backups/joy-pharma"
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="$BACKUP_DIR/images_$DATE.tar.gz"

# Créer le dossier de backup
mkdir -p $BACKUP_DIR

# Créer l'archive
tar -czf $BACKUP_FILE /joy-pharma-data/images/

# Garder seulement les 7 derniers backups
ls -t $BACKUP_DIR/images_*.tar.gz | tail -n +8 | xargs rm -f

echo "✅ Backup créé : $BACKUP_FILE"
```

**Automatiser avec cron** :

```bash
# Sur le serveur
crontab -e

# Ajouter cette ligne (backup tous les jours à 2h du matin)
0 2 * * * /path/to/backup-images.sh >> /var/log/joy-pharma-backup.log 2>&1
```

---

## 🎉 Vous êtes prêt !

Suivez ces étapes dans l'ordre :

1. ✅ Créer `/joy-pharma-data/` sur le serveur
2. ✅ Copier les images (rsync)
3. ✅ Mettre à jour `docker-compose.yml`
4. ✅ Commit et push (le `.dockerignore` est déjà à jour)
5. ✅ GitHub Actions déploiera automatiquement
6. ✅ Vérifier que tout fonctionne

**Questions ?** Consultez `docs/gestion-images-serveur-architecture.md` pour plus de détails.

