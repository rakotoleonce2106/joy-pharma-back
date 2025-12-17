# 📸 Gestion des Images sur le Serveur - Guide Rapide

## 🎯 Résumé : Que faire maintenant ?

Vos images (185 MB) doivent être stockées dans **`/joy-pharma-data/images/`** sur votre serveur, **PAS** dans l'image Docker.

---

## 🚀 Solution Rapide (2 options)

### Option 1 : Script Automatique (LE PLUS SIMPLE) ⭐

```bash
# Sur votre Mac
cd /Users/mac2016/Documents/GitHub/joy-pharma-back

# 1. Modifier les variables dans le script
nano scripts/setup-images-on-server.sh
# Changez : SERVER_HOST="your-server.com" et SERVER_USER="root"

# 2. Rendre le script exécutable
chmod +x scripts/setup-images-on-server.sh

# 3. Exécuter le script
./scripts/setup-images-on-server.sh

# 4. Choisir "1" pour le setup complet
```

Le script va **automatiquement** :
- ✅ Créer `/joy-pharma-data/images/` sur le serveur
- ✅ Copier vos 185 MB d'images
- ✅ Configurer les permissions
- ✅ Mettre à jour docker-compose.yml
- ✅ Redémarrer les containers
- ✅ Vérifier que tout fonctionne

---

### Option 2 : Manuel (Si vous préférez contrôler chaque étape)

#### Étape 1 : Créer les dossiers sur le serveur

```bash
ssh user@your-server

sudo mkdir -p /joy-pharma-data/images/products
sudo mkdir -p /joy-pharma-data/images/profile
sudo chown -R www-data:www-data /joy-pharma-data/
sudo chmod -R 755 /joy-pharma-data/
```

#### Étape 2 : Copier vos images

```bash
# Sur votre Mac
cd /Users/mac2016/Documents/GitHub/joy-pharma-back

rsync -avz --progress \
  public/images/ \
  user@your-server:/joy-pharma-data/images/
```

#### Étape 3 : Mettre à jour docker-compose.yml sur le serveur

```bash
# Copier le fichier
scp docker-compose.prod.example.yml user@your-server:/joy-pharma-back/docker-compose.yml

# OU éditer directement sur le serveur
ssh user@your-server
nano /joy-pharma-back/docker-compose.yml
```

**Ajouter ces lignes** dans la section `services.php.volumes:` :

```yaml
volumes:
  - /joy-pharma-data/images:/app/public/images:rw
  - /joy-pharma-data/media:/app/public/media:rw
  - /joy-pharma-data/uploads:/app/public/uploads:rw
```

#### Étape 4 : Redémarrer

```bash
ssh user@your-server
cd /joy-pharma-back
docker compose down
docker compose up -d
```

#### Étape 5 : Vérifier

```bash
# Vérifier que les images sont accessibles depuis le container
docker compose exec php ls -lh /app/public/images/products/ | head

# Tester l'accès HTTP
curl -I https://api.joypharma.com/images/products/test-image.jpg
```

---

## 📁 Architecture finale

```
Serveur
/
├── traefik/                    # Reverse proxy
├── infrastructure/             # PostgreSQL
│
├── joy-pharma-data/           # 🆕 Données persistantes
│   └── images/
│       ├── products/          # ← Vos 185 MB ici
│       └── profile/
│
└── joy-pharma-back/           # Application (remplacée à chaque deploy)
    ├── docker-compose.yml     # Monte /joy-pharma-data/images
    └── deploy.sh
```

### Pourquoi cette structure ?

| ✅ Avantage | Explication |
|-------------|-------------|
| **Les images ne sont jamais supprimées** | Elles sont hors du projet auto-déployé |
| **Build Docker rapide** | L'image Docker ne contient plus les 185 MB |
| **Backup facile** | `tar -czf backup.tar.gz /joy-pharma-data/` |
| **Scalable** | Facile de migrer vers un CDN plus tard |

---

## 📊 Vérifications

### ✅ Checklist

Après le setup, vérifiez :

- [ ] Le dossier `/joy-pharma-data/images/` existe sur le serveur
- [ ] Les images sont copiées (185 MB)
- [ ] Le `docker-compose.yml` monte le volume
- [ ] Le container PHP démarre correctement
- [ ] Les images sont accessibles : `docker compose exec php ls /app/public/images/`
- [ ] Les images s'affichent via HTTP : `curl -I https://api.joypharma.com/images/products/...`

### 🐛 En cas de problème

```bash
# 1. Vérifier les logs
ssh user@your-server
cd /joy-pharma-back
docker compose logs -f php

# 2. Vérifier les permissions
sudo ls -la /joy-pharma-data/images/
sudo chown -R www-data:www-data /joy-pharma-data/images/
sudo chmod -R 755 /joy-pharma-data/images/

# 3. Redémarrer
docker compose restart
```

---

## 🔄 Déploiement futur

### ⚠️ Important

Maintenant, quand vous faites un `git push`, GitHub Actions va :

1. ✅ Build l'image Docker (SANS les images, grâce à `.dockerignore`)
2. ✅ Push l'image sur le registry (beaucoup plus rapide maintenant)
3. ✅ Déployer sur le serveur
4. ✅ Les images dans `/joy-pharma-data/` restent intactes ✨

### Ajouter de nouvelles images

Si vous avez de nouvelles images à ajouter plus tard :

```bash
# Sur votre Mac
cd /Users/mac2016/Documents/GitHub/joy-pharma-back

# Synchroniser seulement les nouvelles images
rsync -avz --progress \
  public/images/ \
  user@your-server:/joy-pharma-data/images/

# Pas besoin de redémarrer Docker !
```

---

## 📚 Documentation complète

- **`DEPLOY_INSTRUCTIONS.md`** - Instructions complètes de déploiement
- **`docs/gestion-images-serveur-architecture.md`** - Guide détaillé spécifique à votre architecture
- **`docs/gestion-images-serveur.md`** - Guide général avec toutes les options
- **`docs/fix-docker-build-images.md`** - Explication du problème et solutions
- **`docker-compose.prod.example.yml`** - Fichier de configuration prêt à l'emploi
- **`scripts/setup-images-on-server.sh`** - Script automatique de setup

---

## 🎯 Prochaines étapes (optionnel)

Une fois que tout fonctionne :

1. **Configurer les backups** automatiques de `/joy-pharma-data/`
2. **Ajouter un CDN** (Cloudflare, DigitalOcean) pour servir les images
3. **Optimiser les images** (compression, WebP)
4. **Monitoring** de l'espace disque

---

## ❓ Questions fréquentes

### Q : Les images vont-elles être supprimées lors du prochain déploiement ?

**R :** Non ! Elles sont dans `/joy-pharma-data/` qui est **hors** du dossier `/joy-pharma-back/`. Le déploiement GitHub Actions ne touche que le code.

### Q : L'image Docker est maintenant plus petite ?

**R :** Oui ! De ~800 MB à ~500 MB. Le build et le push sont 3-5x plus rapides.

### Q : Puis-je uploader de nouvelles images via l'API ?

**R :** Oui ! Les uploads se feront dans `/joy-pharma-data/uploads/` grâce au volume monté.

### Q : Comment je fais un backup ?

```bash
ssh user@your-server
sudo tar -czf /tmp/images-backup-$(date +%Y%m%d).tar.gz /joy-pharma-data/
scp user@your-server:/tmp/images-backup-*.tar.gz ./backups/
```

---

## 🚀 Action immédiate

**Choisissez votre méthode** :

### Méthode rapide (recommandée)
```bash
cd /Users/mac2016/Documents/GitHub/joy-pharma-back
nano scripts/setup-images-on-server.sh  # Modifier SERVER_HOST
chmod +x scripts/setup-images-on-server.sh
./scripts/setup-images-on-server.sh
```

### Méthode manuelle
Suivre les étapes de la **Option 2** ci-dessus.

---

**Besoin d'aide ?** Consultez `DEPLOY_INSTRUCTIONS.md` pour le guide complet ! 📖

