# Documentation API : Gestion des Entités Admin (Category, Brand, Manufacturer, Form, Unit)

## Vue d'ensemble

Cette documentation explique comment créer, mettre à jour et gérer les entités administratives (Catégories, Marques, Fabricants, Formes, Unités) via l'API Admin, incluant l'upload d'images et d'icônes.

## Authentification

Tous les endpoints admin nécessitent une authentification avec le rôle `ROLE_ADMIN`. Utilisez un token JWT dans l'en-tête `Authorization` :

```http
Authorization: Bearer VOTRE_TOKEN_JWT
```

## Format des relations (Important)

**Toutes les relations ManyToOne doivent être envoyées comme des IRIs (chaînes), pas comme des IDs entiers.**

- ✅ **Correct** : `"parent": "/api/admin/categories/1"` ou `"image": "/api/media_objects/123"`
- ❌ **Incorrect** : `"parent": 1` ou `"image": 123`

**⚠️ Content-Type requis :** Lorsque vous utilisez des IRIs pour les relations, vous **DEVEZ** utiliser le Content-Type `application/ld+json` au lieu de `application/json`. Sinon, vous obtiendrez une erreur "Invalid IRI".

- ✅ **Correct** : `Content-Type: application/ld+json`
- ❌ **Incorrect** : `Content-Type: application/json` (si vous utilisez des IRIs)

API Platform désérialise automatiquement les IRIs en entités.

---

## 📁 Catégories (Categories)

### Endpoints disponibles

- **GET** `/api/admin/categories` - Liste toutes les catégories
- **GET** `/api/admin/categories/{id}` - Récupère une catégorie par son ID
- **POST** `/api/admin/categories` - Crée une nouvelle catégorie
- **PUT** `/api/admin/categories/{id}` - Met à jour une catégorie existante (mise à jour complète)
- **PATCH** `/api/admin/categories/{id}` - Met à jour une catégorie existante (mise à jour partielle)
- **DELETE** `/api/admin/categories/{id}` - Supprime une catégorie
- **POST** `/api/admin/categories/batch-delete` - Supprime plusieurs catégories en lot

### Structure des données

| Champ | Type | Requis | Description |
|-------|------|--------|-------------|
| `name` | string | ✅ Oui (create) | Nom de la catégorie |
| `description` | string | ❌ Non | Description de la catégorie |
| `parent` | string | ❌ Non | IRI de la catégorie parente (ex: `"/api/admin/categories/1"`) |
| `image` | string | ❌ Non | IRI de l'image (ex: `"/api/media_objects/123"`) |
| `svg` | string | ❌ Non | IRI de l'icône SVG (ex: `"/api/media_objects/124"`) |
| `color` | string | ❌ Non | Code couleur (ex: `"#FF5733"`) |

### Workflow complet : Créer une catégorie avec image et icône

#### Étape 1 : Uploader l'image et l'icône

```bash
# Uploader l'image de la catégorie
curl -X POST "https://votre-api.com/api/media_objects" \
  -H "Authorization: Bearer VOTRE_TOKEN" \
  -F "file=@/chemin/vers/image.jpg" \
  -F "mapping=category_images"

# Réponse: { "@id": "/api/media_objects/123", "id": 123, ... }

# Uploader l'icône SVG
curl -X POST "https://votre-api.com/api/media_objects" \
  -H "Authorization: Bearer VOTRE_TOKEN" \
  -F "file=@/chemin/vers/icon.svg" \
  -F "mapping=category_icons"

# Réponse: { "@id": "/api/media_objects/124", "id": 124, ... }
```

#### Étape 2 : Créer la catégorie

```bash
curl -X POST "https://votre-api.com/api/admin/categories" \
  -H "Authorization: Bearer VOTRE_TOKEN" \
  -H "Content-Type: application/ld+json" \
  -d '{
    "name": "Médicaments",
    "description": "Catégorie principale pour les médicaments",
    "parent": null,
    "image": "/api/media_objects/123",
    "svg": "/api/media_objects/124",
    "color": "#FF5733"
  }'
```

**Exemple avec JavaScript :**
```javascript
async function createCategory(categoryData, imageFile, iconFile) {
  // 1. Uploader l'image
  const imageIri = await uploadMediaObject(imageFile, 'category_images');
  
  // 2. Uploader l'icône
  const iconIri = await uploadMediaObject(iconFile, 'category_icons');
  
  // 3. Créer la catégorie
  const response = await fetch('/api/admin/categories', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/ld+json',
      'Authorization': `Bearer ${token}`
    },
    body: JSON.stringify({
      name: categoryData.name,
      description: categoryData.description,
      parent: categoryData.parentId ? `/api/admin/categories/${categoryData.parentId}` : null,
      image: imageIri,
      svg: iconIri,
      color: categoryData.color
    })
  });
  
  if (!response.ok) {
    const error = await response.json();
    throw new Error(error.message || 'Échec de la création de la catégorie');
  }
  
  return await response.json();
}

async function uploadMediaObject(file, mapping) {
  const formData = new FormData();
  formData.append('file', file);
  formData.append('mapping', mapping);
  
  const response = await fetch('/api/media_objects', {
    method: 'POST',
    headers: { 'Authorization': `Bearer ${token}` },
    body: formData
  });
  
  if (!response.ok) {
    throw new Error('Échec de l\'upload');
  }
  
  const mediaObject = await response.json();
  return mediaObject['@id'];
}
```

### Mettre à jour une catégorie

#### Mise à jour complète (PUT)

```bash
curl -X PUT "https://votre-api.com/api/admin/categories/1" \
  -H "Authorization: Bearer VOTRE_TOKEN" \
  -H "Content-Type: application/ld+json" \
  -d '{
    "name": "Médicaments - Mise à jour",
    "description": "Description mise à jour",
    "parent": "/api/admin/categories/5",
    "image": "/api/media_objects/125",
    "svg": "/api/media_objects/126",
    "color": "#00FF00"
  }'
```

#### Mise à jour partielle (PATCH)

```bash
# Mettre à jour uniquement le nom et la couleur
curl -X PATCH "https://votre-api.com/api/admin/categories/1" \
  -H "Authorization: Bearer VOTRE_TOKEN" \
  -H "Content-Type: application/ld+json" \
  -d '{
    "name": "Nouveau nom",
    "color": "#FF0000"
  }'

# Mettre à jour uniquement l'image
curl -X PATCH "https://votre-api.com/api/admin/categories/1" \
  -H "Authorization: Bearer VOTRE_TOKEN" \
  -H "Content-Type: application/ld+json" \
  -d '{
    "image": "/api/media_objects/127"
  }'
```

---

## 🏷️ Marques (Brands)

### Endpoints disponibles

- **GET** `/api/admin/brands` - Liste toutes les marques
- **GET** `/api/admin/brands/{id}` - Récupère une marque par son ID
- **POST** `/api/admin/brands` - Crée une nouvelle marque
- **PUT** `/api/admin/brands/{id}` - Met à jour une marque existante (mise à jour complète)
- **PATCH** `/api/admin/brands/{id}` - Met à jour une marque existante (mise à jour partielle)
- **DELETE** `/api/admin/brands/{id}` - Supprime une marque
- **POST** `/api/admin/brands/batch-delete` - Supprime plusieurs marques en lot

### Structure des données

| Champ | Type | Requis | Description |
|-------|------|--------|-------------|
| `name` | string | ✅ Oui (create) | Nom de la marque |
| `image` | string | ❌ Non | IRI du logo (ex: `"/api/media_objects/123"`) |

### Workflow complet : Créer une marque avec logo

#### Étape 1 : Uploader le logo

```bash
curl -X POST "https://votre-api.com/api/media_objects" \
  -H "Authorization: Bearer VOTRE_TOKEN" \
  -F "file=@/chemin/vers/logo.jpg" \
  -F "mapping=brand_images"

# Réponse: { "@id": "/api/media_objects/123", "id": 123, ... }
```

#### Étape 2 : Créer la marque

```bash
curl -X POST "https://votre-api.com/api/admin/brands" \
  -H "Authorization: Bearer VOTRE_TOKEN" \
  -H "Content-Type: application/ld+json" \
  -d '{
    "name": "Pfizer",
    "image": "/api/media_objects/123"
  }'
```

**Exemple avec JavaScript :**
```javascript
async function createBrand(brandData, logoFile) {
  // 1. Uploader le logo
  const logoIri = logoFile ? await uploadMediaObject(logoFile, 'brand_images') : null;
  
  // 2. Créer la marque
  const response = await fetch('/api/admin/brands', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/ld+json',
      'Authorization': `Bearer ${token}`
    },
    body: JSON.stringify({
      name: brandData.name,
      image: logoIri
    })
  });
  
  if (!response.ok) {
    const error = await response.json();
    throw new Error(error.message || 'Échec de la création de la marque');
  }
  
  return await response.json();
}
```

### Mettre à jour une marque

```bash
# Mise à jour partielle - changer uniquement le logo
curl -X PATCH "https://votre-api.com/api/admin/brands/1" \
  -H "Authorization: Bearer VOTRE_TOKEN" \
  -H "Content-Type: application/ld+json" \
  -d '{
    "image": "/api/media_objects/125"
  }'
```

---

## 🏭 Fabricants (Manufacturers)

### Endpoints disponibles

- **GET** `/api/admin/manufacturers` - Liste tous les fabricants
- **GET** `/api/admin/manufacturers/{id}` - Récupère un fabricant par son ID
- **POST** `/api/admin/manufacturers` - Crée un nouveau fabricant
- **PUT** `/api/admin/manufacturers/{id}` - Met à jour un fabricant existant (mise à jour complète)
- **PATCH** `/api/admin/manufacturers/{id}` - Met à jour un fabricant existant (mise à jour partielle)
- **DELETE** `/api/admin/manufacturers/{id}` - Supprime un fabricant
- **POST** `/api/admin/manufacturers/batch-delete` - Supprime plusieurs fabricants en lot

### Structure des données

| Champ | Type | Requis | Description |
|-------|------|--------|-------------|
| `name` | string | ✅ Oui (create) | Nom du fabricant |
| `description` | string | ❌ Non | Description du fabricant |
| `image` | string | ❌ Non | IRI du logo (ex: `"/api/media_objects/123"`) |

### Workflow complet : Créer un fabricant avec logo

```bash
# 1. Uploader le logo
curl -X POST "https://votre-api.com/api/media_objects" \
  -H "Authorization: Bearer VOTRE_TOKEN" \
  -F "file=@/chemin/vers/logo.jpg" \
  -F "mapping=manufacturer_images"

# Réponse: { "@id": "/api/media_objects/123", "id": 123, ... }

# 2. Créer le fabricant
curl -X POST "https://votre-api.com/api/admin/manufacturers" \
  -H "Authorization: Bearer VOTRE_TOKEN" \
  -H "Content-Type: application/ld+json" \
  -d '{
    "name": "Sanofi",
    "description": "Fabricant pharmaceutique français",
    "image": "/api/media_objects/123"
  }'
```

**Exemple avec JavaScript :**
```javascript
async function createManufacturer(manufacturerData, logoFile) {
  const logoIri = logoFile ? await uploadMediaObject(logoFile, 'manufacturer_images') : null;
  
  const response = await fetch('/api/admin/manufacturers', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/ld+json',
      'Authorization': `Bearer ${token}`
    },
    body: JSON.stringify({
      name: manufacturerData.name,
      description: manufacturerData.description,
      image: logoIri
    })
  });
  
  if (!response.ok) {
    const error = await response.json();
    throw new Error(error.message || 'Échec de la création du fabricant');
  }
  
  return await response.json();
}
```

---

## 💊 Formes (Forms)

### Endpoints disponibles

- **GET** `/api/admin/forms` - Liste toutes les formes
- **GET** `/api/admin/forms/{id}` - Récupère une forme par son ID
- **POST** `/api/admin/forms` - Crée une nouvelle forme
- **PUT** `/api/admin/forms/{id}` - Met à jour une forme existante (mise à jour complète)
- **PATCH** `/api/admin/forms/{id}` - Met à jour une forme existante (mise à jour partielle)
- **DELETE** `/api/admin/forms/{id}` - Supprime une forme
- **POST** `/api/admin/forms/batch-delete` - Supprime plusieurs formes en lot

### Structure des données

| Champ | Type | Requis | Description |
|-------|------|--------|-------------|
| `label` | string | ✅ Oui (create) | Libellé de la forme (ex: "Comprimé", "Sirop", "Gélule") |

### Créer une forme

```bash
curl -X POST "https://votre-api.com/api/admin/forms" \
  -H "Authorization: Bearer VOTRE_TOKEN" \
  -H "Content-Type: application/ld+json" \
  -d '{
    "label": "Comprimé"
  }'
```

**Exemple avec JavaScript :**
```javascript
async function createForm(formData) {
  const response = await fetch('/api/admin/forms', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/ld+json',
      'Authorization': `Bearer ${token}`
    },
    body: JSON.stringify({
      label: formData.label
    })
  });
  
  if (!response.ok) {
    const error = await response.json();
    throw new Error(error.message || 'Échec de la création de la forme');
  }
  
  return await response.json();
}
```

### Mettre à jour une forme

```bash
# Mise à jour partielle
curl -X PATCH "https://votre-api.com/api/admin/forms/1" \
  -H "Authorization: Bearer VOTRE_TOKEN" \
  -H "Content-Type: application/ld+json" \
  -d '{
    "label": "Comprimé pelliculé"
  }'
```

---

## 📦 Unités (Units)

### Endpoints disponibles

- **GET** `/api/admin/units` - Liste toutes les unités
- **GET** `/api/admin/units/{id}` - Récupère une unité par son ID
- **POST** `/api/admin/units` - Crée une nouvelle unité
- **PUT** `/api/admin/units/{id}` - Met à jour une unité existante (mise à jour complète)
- **PATCH** `/api/admin/units/{id}` - Met à jour une unité existante (mise à jour partielle)
- **DELETE** `/api/admin/units/{id}` - Supprime une unité
- **POST** `/api/admin/units/batch-delete` - Supprime plusieurs unités en lot

### Structure des données

| Champ | Type | Requis | Description |
|-------|------|--------|-------------|
| `label` | string | ✅ Oui (create) | Libellé de l'unité (ex: "Boîte", "Flacon", "Sachet") |

### Créer une unité

```bash
curl -X POST "https://votre-api.com/api/admin/units" \
  -H "Authorization: Bearer VOTRE_TOKEN" \
  -H "Content-Type: application/ld+json" \
  -d '{
    "label": "Boîte"
  }'
```

**Exemple avec JavaScript :**
```javascript
async function createUnit(unitData) {
  const response = await fetch('/api/admin/units', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/ld+json',
      'Authorization': `Bearer ${token}`
    },
    body: JSON.stringify({
      label: unitData.label
    })
  });
  
  if (!response.ok) {
    const error = await response.json();
    throw new Error(error.message || 'Échec de la création de l\'unité');
  }
  
  return await response.json();
}
```

### Mettre à jour une unité

```bash
# Mise à jour partielle
curl -X PATCH "https://votre-api.com/api/admin/units/1" \
  -H "Authorization: Bearer VOTRE_TOKEN" \
  -H "Content-Type: application/ld+json" \
  -d '{
    "label": "Boîte de 20"
  }'
```

---

## Mappings d'images disponibles

Le paramètre `mapping` lors de l'upload détermine où le fichier sera stocké :

| Mapping | Dossier de stockage | Usage recommandé |
|---------|---------------------|-------------------|
| `category_images` | `/public/images/categories/` | Images de catégories |
| `category_icons` | `/public/icons/categories/` | Icônes SVG de catégories |
| `brand_images` | `/public/images/brands/` | Images/logos de marques |
| `manufacturer_images` | `/public/images/manufacturers/` | Images/logos de fabricants |
| `media_object` | `/public/media/` | Par défaut (générique) |

---

## Gestion des erreurs

### Erreurs communes

#### Invalid IRI (Content-Type incorrect)
```json
{
  "type": "https://tools.ietf.org/html/rfc2616#section-10",
  "title": "An error occurred",
  "detail": "Invalid IRI \"/api/media_objects/1\".",
  "status": 500
}
```

**Solution :** Utiliser le Content-Type `application/ld+json` au lieu de `application/json` lorsque vous envoyez des IRIs.

#### Champ requis manquant
```json
{
  "type": "https://tools.ietf.org/html/rfc2616#section-10",
  "title": "An error occurred",
  "detail": "name: This value should not be blank.",
  "status": 422
}
```

**Solution :** Fournir tous les champs requis (`name` pour Category/Brand/Manufacturer, `label` pour Form/Unit).

#### Entité introuvable
```json
{
  "type": "https://tools.ietf.org/html/rfc2616#section-10",
  "title": "An error occurred",
  "detail": "Category not found",
  "status": 404
}
```

**Solution :** Vérifier que l'ID de l'entité est correct.

---

## Bonnes pratiques

1. **Toujours uploader les images/icônes d'abord** avant de créer/mettre à jour l'entité
2. **Utiliser le mapping approprié** pour chaque type d'image (`category_images`, `brand_images`, etc.)
3. **Utiliser `Content-Type: application/ld+json`** lorsque vous envoyez des IRIs (obligatoire)
4. **Utiliser PATCH pour les mises à jour partielles** (recommandé)
5. **Utiliser PUT pour les mises à jour complètes** (nécessite tous les champs)
6. **Récupérer les IRIs des relations** via les endpoints GET (utiliser le champ `@id` dans les réponses)
7. **Gérer les erreurs** et afficher des messages clairs à l'utilisateur
8. **Valider les données** côté client avant d'envoyer à l'API

---

## Exemples complets

### Exemple 1 : Créer une catégorie avec sous-catégorie

```bash
# 1. Créer la catégorie parente
curl -X POST "https://votre-api.com/api/admin/categories" \
  -H "Authorization: Bearer VOTRE_TOKEN" \
  -H "Content-Type: application/ld+json" \
  -d '{
    "name": "Médicaments",
    "description": "Catégorie principale"
  }'

# Réponse: { "@id": "/api/admin/categories/1", "id": 1, ... }

# 2. Créer la sous-catégorie
curl -X POST "https://votre-api.com/api/admin/categories" \
  -H "Authorization: Bearer VOTRE_TOKEN" \
  -H "Content-Type: application/ld+json" \
  -d '{
    "name": "Antibiotiques",
    "description": "Sous-catégorie d'antibiotiques",
    "parent": "/api/admin/categories/1"
  }'
```

### Exemple 2 : Mettre à jour uniquement l'image d'une marque

```bash
# 1. Uploader la nouvelle image
curl -X POST "https://votre-api.com/api/media_objects" \
  -H "Authorization: Bearer VOTRE_TOKEN" \
  -F "file=@nouveau-logo.jpg" \
  -F "mapping=brand_images"

# Réponse: { "@id": "/api/media_objects/125", "id": 125, ... }

# 2. Mettre à jour uniquement l'image avec PATCH
curl -X PATCH "https://votre-api.com/api/admin/brands/1" \
  -H "Authorization: Bearer VOTRE_TOKEN" \
  -H "Content-Type: application/ld+json" \
  -d '{
    "image": "/api/media_objects/125"
  }'
```

### Exemple 3 : Créer plusieurs unités en lot

```javascript
const units = ['Boîte', 'Flacon', 'Sachet', 'Tube', 'Pilulier'];

async function createMultipleUnits(units) {
  const results = [];
  
  for (const label of units) {
    try {
      const unit = await createUnit({ label });
      results.push(unit);
    } catch (error) {
      console.error(`Erreur lors de la création de "${label}":`, error);
    }
  }
  
  return results;
}

await createMultipleUnits(units);
```

---

## Endpoints de référence

### Catégories
- `GET /api/admin/categories` - Liste des catégories
- `GET /api/admin/categories/{id}` - Détails d'une catégorie
- `POST /api/admin/categories` - Créer une catégorie
- `PUT /api/admin/categories/{id}` - Mettre à jour une catégorie (complète)
- `PATCH /api/admin/categories/{id}` - Mettre à jour une catégorie (partielle)
- `DELETE /api/admin/categories/{id}` - Supprimer une catégorie

### Marques
- `GET /api/admin/brands` - Liste des marques
- `GET /api/admin/brands/{id}` - Détails d'une marque
- `POST /api/admin/brands` - Créer une marque
- `PUT /api/admin/brands/{id}` - Mettre à jour une marque (complète)
- `PATCH /api/admin/brands/{id}` - Mettre à jour une marque (partielle)
- `DELETE /api/admin/brands/{id}` - Supprimer une marque

### Fabricants
- `GET /api/admin/manufacturers` - Liste des fabricants
- `GET /api/admin/manufacturers/{id}` - Détails d'un fabricant
- `POST /api/admin/manufacturers` - Créer un fabricant
- `PUT /api/admin/manufacturers/{id}` - Mettre à jour un fabricant (complète)
- `PATCH /api/admin/manufacturers/{id}` - Mettre à jour un fabricant (partielle)
- `DELETE /api/admin/manufacturers/{id}` - Supprimer un fabricant

### Formes
- `GET /api/admin/forms` - Liste des formes
- `GET /api/admin/forms/{id}` - Détails d'une forme
- `POST /api/admin/forms` - Créer une forme
- `PUT /api/admin/forms/{id}` - Mettre à jour une forme (complète)
- `PATCH /api/admin/forms/{id}` - Mettre à jour une forme (partielle)
- `DELETE /api/admin/forms/{id}` - Supprimer une forme

### Unités
- `GET /api/admin/units` - Liste des unités
- `GET /api/admin/units/{id}` - Détails d'une unité
- `POST /api/admin/units` - Créer une unité
- `PUT /api/admin/units/{id}` - Mettre à jour une unité (complète)
- `PATCH /api/admin/units/{id}` - Mettre à jour une unité (partielle)
- `DELETE /api/admin/units/{id}` - Supprimer une unité

### Images
- `POST /api/media_objects` - Uploader une image/icône

---

## Ressources supplémentaires

- [Documentation API Produits Admin](./API_PRODUCTS_ADMIN.md)
- [Guide complet d'upload d'images](./GUIDE_UPLOAD_IMAGES.md)
- [Documentation API Images complète](./API_IMAGES_COMPLETE.md)
- [Pattern d'upload de fichiers](./FILE_UPLOAD_PATTERN.md)

