# Dépannage : Suppression des icônes de catégories

## Problème : Les images dans `icons/categories` sont supprimées sur le serveur

## Causes possibles

### 1. Volume Docker non monté (Cause principale)

**Symptôme** : Les icônes disparaissent après un redémarrage ou une recréation du conteneur Docker.

**Cause** : Le dossier `/app/public/icons/` n'était pas monté dans un volume persistant dans `compose.prod.yaml`.

**Solution** : Ajouter le volume dans la configuration Docker :

```yaml
volumes:
  - ./data/icons:/app/public/icons:rw
```

### 2. Suppression automatique par VichUploader

**Symptôme** : Les icônes sont supprimées lors de la mise à jour d'une catégorie.

**Cause** : La configuration VichUploader a `delete_on_update: true` et `delete_on_remove: true` pour `category_icons`.

**Comportement actuel** :
- Quand une catégorie est mise à jour avec une nouvelle icône, l'ancienne est automatiquement supprimée
- Quand une catégorie est supprimée, son icône est automatiquement supprimée
- Quand une icône est remplacée dans `CategoryProcessor`, l'ancienne est supprimée

**Solution** : Si vous voulez conserver les anciennes icônes, modifiez `config/packages/vich_uploader.yaml` :

```yaml
category_icons:
    uri_prefix: /icons/categories
    upload_destination: '%kernel.project_dir%/public/icons/categories'
    namer: Vich\UploaderBundle\Naming\SmartUniqueNamer
    inject_on_load: false
    delete_on_update: false  # ← Changer en false
    delete_on_remove: true  # Garder true pour nettoyer lors de la suppression
```

**⚠️ Attention** : Si vous changez `delete_on_update` à `false`, les anciennes icônes ne seront pas supprimées automatiquement et pourront s'accumuler.

### 3. Suppression manuelle ou script

**Vérification** : Vérifiez s'il y a des scripts ou des commandes qui suppriment les fichiers :

```bash
# Vérifier les fichiers dans le conteneur
docker exec -it joy-pharma-back-php ls -la /app/public/icons/categories/

# Vérifier les fichiers sur l'hôte (si volume monté)
ls -la ./data/icons/categories/
```

## Solutions

### Solution 1 : Ajouter le volume persistant (Recommandé)

1. **Vérifier que le dossier existe** :
```bash
mkdir -p ./data/icons/categories
```

2. **Ajouter le volume dans `compose.prod.yaml`** :
```yaml
volumes:
  - ./data/icons:/app/public/icons:rw
```

3. **Redémarrer le conteneur** :
```bash
docker compose -f compose.yaml -f compose.prod.yaml up -d
```

4. **Copier les icônes existantes** (si elles existent encore dans le conteneur) :
```bash
# Depuis le conteneur vers l'hôte
docker cp joy-pharma-back-php:/app/public/icons/categories ./data/icons/
```

### Solution 2 : Sauvegarder les icônes avant redéploiement

Créer un script de sauvegarde :

```bash
#!/bin/bash
# backup-icons.sh

# Créer le dossier de sauvegarde
mkdir -p ./backups/icons

# Sauvegarder depuis le conteneur
docker cp joy-pharma-back-php:/app/public/icons/categories ./backups/icons/$(date +%Y%m%d_%H%M%S)_categories

echo "Icônes sauvegardées"
```

### Solution 3 : Modifier le comportement de suppression

Si vous voulez conserver les anciennes icônes lors des mises à jour :

1. Modifier `config/packages/vich_uploader.yaml` :
```yaml
category_icons:
    delete_on_update: false  # Ne pas supprimer lors de la mise à jour
    delete_on_remove: true   # Supprimer uniquement lors de la suppression de la catégorie
```

2. Redémarrer l'application :
```bash
docker compose restart php
```

## Prévention

### 1. Vérifier la configuration des volumes

Assurez-vous que tous les dossiers de fichiers uploadés sont dans des volumes persistants :

```yaml
volumes:
  - ./data/images:/app/public/images:rw      # ✅ Images
  - ./data/icons:/app/public/icons:rw        # ✅ Icons (à ajouter)
  - ./data/media:/app/public/media:rw        # ✅ Media
  - ./data/uploads:/app/public/uploads:rw    # ✅ Uploads
```

### 2. Sauvegardes régulières

Créez un script de sauvegarde automatique :

```bash
#!/bin/bash
# backup-all-files.sh

BACKUP_DIR="./backups/$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR"

# Sauvegarder tous les dossiers de fichiers
cp -r ./data/images "$BACKUP_DIR/"
cp -r ./data/icons "$BACKUP_DIR/"
cp -r ./data/media "$BACKUP_DIR/"
cp -r ./data/uploads "$BACKUP_DIR/"

echo "Sauvegarde complète créée dans $BACKUP_DIR"
```

### 3. Monitoring

Surveillez les logs pour détecter les suppressions :

```bash
# Vérifier les logs de suppression
docker logs joy-pharma-back-php | grep -i "delete\|remove\|icon"
```

## Structure des dossiers

```
./data/
├── images/
│   ├── categories/     # Images de catégories
│   ├── products/       # Images de produits
│   ├── brands/         # Logos de marques
│   └── ...
├── icons/
│   └── categories/     # Icônes SVG de catégories ← IMPORTANT
├── media/              # Autres fichiers média
└── uploads/            # Fichiers uploadés temporaires
```

## Vérification

Pour vérifier que tout fonctionne correctement :

```bash
# 1. Vérifier que le volume est monté
docker exec -it joy-pharma-back-php ls -la /app/public/icons/categories/

# 2. Vérifier sur l'hôte
ls -la ./data/icons/categories/

# 3. Tester l'upload d'une icône via l'API
# (voir docs/API_IMAGES_COMPLETE.md)

# 4. Redémarrer le conteneur et vérifier que les fichiers sont toujours là
docker compose restart php
docker exec -it joy-pharma-back-php ls -la /app/public/icons/categories/
```

## Support

Pour plus d'informations :
- [Documentation API Images](./API_IMAGES_COMPLETE.md)
- [Guide Upload Images](./GUIDE_UPLOAD_IMAGES.md)
- [Configuration VichUploader](../config/packages/vich_uploader.yaml)

