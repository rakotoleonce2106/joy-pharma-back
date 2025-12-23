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

## 👤 Utilisateurs (Users)

### Endpoints disponibles

- **GET** `/api/admin/users` - Liste tous les utilisateurs
- **GET** `/api/admin/users/{id}` - Récupère un utilisateur par son ID
- **POST** `/api/admin/users` - Crée un nouvel utilisateur
- **PUT** `/api/admin/users/{id}` - Met à jour un utilisateur existant (mise à jour complète)
- **PATCH** `/api/admin/users/{id}` - Met à jour un utilisateur existant (mise à jour partielle)
- **DELETE** `/api/admin/users/{id}` - Supprime un utilisateur
- **POST** `/api/admin/users/{id}/toggle-active` - Active/désactive un utilisateur

### Structure des données

| Champ | Type | Requis | Description |
|-------|------|--------|-------------|
| `email` | string | ✅ Oui (create) | Email de l'utilisateur (doit être unique) |
| `firstName` | string | ✅ Oui (create) | Prénom de l'utilisateur |
| `lastName` | string | ✅ Oui (create) | Nom de l'utilisateur |
| `plainPassword` | string | ❌ Non | Mot de passe en clair (sera hashé automatiquement). Si non fourni lors de la création, un mot de passe par défaut sera généré. |
| `roles` | array<string> | ❌ Non | Tableau des rôles (ex: `["ROLE_ADMIN", "ROLE_STORE"]`). Par défaut, `ROLE_USER` est ajouté automatiquement. |
| `active` | boolean | ❌ Non | Statut actif/inactif (défaut: `true`) |
| `phone` | string | ❌ Non | Numéro de téléphone |
| `image` | string | ❌ Non | IRI de l'avatar (ex: `"/api/media_objects/123"`) |

### Workflow complet : Créer un utilisateur avec avatar

#### Étape 1 : Uploader l'avatar (optionnel)

```bash
curl -X POST "https://votre-api.com/api/media_objects" \
  -H "Authorization: Bearer VOTRE_TOKEN" \
  -F "file=@/chemin/vers/avatar.jpg" \
  -F "mapping=user_images"

# Réponse: { "@id": "/api/media_objects/123", "id": 123, "contentUrl": "/images/users/abc123.jpg", ... }
```

**Important :** Utilisez toujours `mapping=user_images` pour les avatars d'utilisateurs. Cela garantit que les fichiers sont stockés dans `/public/images/users/` et organisés correctement.

#### Étape 2 : Créer l'utilisateur

```bash
curl -X POST "https://votre-api.com/api/admin/users" \
  -H "Authorization: Bearer VOTRE_TOKEN" \
  -H "Content-Type: application/ld+json" \
  -d '{
    "email": "user@example.com",
    "firstName": "Jean",
    "lastName": "Dupont",
    "plainPassword": "MotDePasse123!",
    "roles": ["ROLE_STORE"],
    "active": true,
    "phone": "+261341234567",
    "image": "/api/media_objects/123"
  }'
```

**Note :** Si `plainPassword` n'est pas fourni, un mot de passe par défaut sera généré automatiquement (`JoyPharma2025!`).

**Exemple avec JavaScript :**
```javascript
async function createUser(userData, avatarFile) {
  // 1. Uploader l'avatar si fourni (utiliser user_images pour les avatars)
  const avatarIri = avatarFile ? await uploadMediaObject(avatarFile, 'user_images') : null;
  
  // 2. Créer l'utilisateur
  const response = await fetch('/api/admin/users', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/ld+json',
      'Authorization': `Bearer ${token}`
    },
    body: JSON.stringify({
      email: userData.email,
      firstName: userData.firstName,
      lastName: userData.lastName,
      plainPassword: userData.password || null, // Optionnel, génère un mot de passe par défaut si null
      roles: userData.roles || [],
      active: userData.active !== undefined ? userData.active : true,
      phone: userData.phone || null,
      image: avatarIri
    })
  });
  
  if (!response.ok) {
    const error = await response.json();
    throw new Error(error.message || 'Échec de la création de l\'utilisateur');
  }
  
  return await response.json();
}
```

### Mettre à jour un utilisateur

#### Mise à jour complète (PUT)

```bash
curl -X PUT "https://votre-api.com/api/admin/users/1" \
  -H "Authorization: Bearer VOTRE_TOKEN" \
  -H "Content-Type: application/ld+json" \
  -d '{
    "email": "user@example.com",
    "firstName": "Jean",
    "lastName": "Dupont",
    "roles": ["ROLE_STORE", "ROLE_ADMIN"],
    "active": true,
    "phone": "+261341234567",
    "image": "/api/media_objects/125"
  }'
```

#### Mise à jour partielle (PATCH)

```bash
# Mettre à jour uniquement le statut actif
curl -X PATCH "https://votre-api.com/api/admin/users/1" \
  -H "Authorization: Bearer VOTRE_TOKEN" \
  -H "Content-Type: application/ld+json" \
  -d '{
    "active": false
  }'

# Mettre à jour uniquement les rôles
curl -X PATCH "https://votre-api.com/api/admin/users/1" \
  -H "Authorization: Bearer VOTRE_TOKEN" \
  -H "Content-Type: application/ld+json" \
  -d '{
    "roles": ["ROLE_ADMIN"]
  }'

# Changer le mot de passe
curl -X PATCH "https://votre-api.com/api/admin/users/1" \
  -H "Authorization: Bearer VOTRE_TOKEN" \
  -H "Content-Type: application/ld+json" \
  -d '{
    "plainPassword": "NouveauMotDePasse123!"
  }'
```

### Activer/Désactiver un utilisateur

```bash
# Désactiver un utilisateur
curl -X POST "https://votre-api.com/api/admin/users/1/toggle-active" \
  -H "Authorization: Bearer VOTRE_TOKEN"

# Réactiver un utilisateur (même endpoint)
curl -X POST "https://votre-api.com/api/admin/users/1/toggle-active" \
  -H "Authorization: Bearer VOTRE_TOKEN"
```

---

## 🏪 Magasins (Stores)

### Endpoints disponibles

- **GET** `/api/admin/stores` - Liste tous les magasins
- **GET** `/api/admin/stores/{id}` - Récupère un magasin par son ID
- **POST** `/api/admin/stores` - Crée un nouveau magasin
- **PUT** `/api/admin/stores/{id}` - Met à jour un magasin existant (mise à jour complète)
- **PATCH** `/api/admin/stores/{id}` - Met à jour un magasin existant (mise à jour partielle)
- **DELETE** `/api/admin/stores/{id}` - Supprime un magasin
- **POST** `/api/admin/stores/batch-delete` - Supprime plusieurs magasins en lot

### Structure des données

| Champ | Type | Requis | Description |
|-------|------|--------|-------------|
| `name` | string | ✅ Oui (create) | Nom du magasin |
| `description` | string | ❌ Non | Description du magasin |
| `image` | string | ❌ Non | IRI de l'image (ex: `"/api/media_objects/123"`) |
| `owner` | string | ❌ Non | IRI de l'utilisateur propriétaire (ex: `"/api/admin/users/1"`). Si non fourni, l'utilisateur doit être créé séparément. |
| `contact` | object | ❌ Non | Objet ContactInfo avec `phone` et `email` |
| `location` | object | ❌ Non | Objet Location avec `address`, `latitude`, `longitude`, `city` |

**Note :** `contact` et `location` peuvent être fournis comme objets imbriqués ou comme IRIs. Pour une création simple, utilisez des objets imbriqués.

### Workflow complet : Créer un magasin avec image

#### Étape 1 : Créer l'utilisateur propriétaire (si nécessaire)

```bash
curl -X POST "https://votre-api.com/api/admin/users" \
  -H "Authorization: Bearer VOTRE_TOKEN" \
  -H "Content-Type: application/ld+json" \
  -d '{
    "email": "storeowner@example.com",
    "firstName": "Jean",
    "lastName": "Dupont",
    "roles": ["ROLE_STORE"],
    "plainPassword": "MotDePasse123!"
  }'

# Réponse: { "@id": "/api/admin/users/1", "id": 1, ... }
```

#### Étape 2 : Uploader l'image du magasin (optionnel)

```bash
curl -X POST "https://votre-api.com/api/media_objects" \
  -H "Authorization: Bearer VOTRE_TOKEN" \
  -F "file=@/chemin/vers/store-image.jpg" \
  -F "mapping=store_images"

# Réponse: { "@id": "/api/media_objects/123", "id": 123, ... }
```

**Important :** Utilisez toujours `mapping=store_images` pour les images de magasins.

#### Étape 3 : Créer le magasin

```bash
curl -X POST "https://votre-api.com/api/admin/stores" \
  -H "Authorization: Bearer VOTRE_TOKEN" \
  -H "Content-Type: application/ld+json" \
  -d '{
    "name": "Pharmacie Centrale",
    "description": "Pharmacie principale du centre-ville",
    "image": "/api/media_objects/123",
    "owner": "/api/admin/users/1",
    "contact": {
      "phone": "+261341234567",
      "email": "pharmacie@example.com"
    },
    "location": {
      "address": "123 Rue de la République",
      "latitude": -18.8792,
      "longitude": 47.5079,
      "city": "Antananarivo"
    }
  }'
```

**Exemple avec JavaScript :**
```javascript
async function createStore(storeData, imageFile, ownerId) {
  // 1. Uploader l'image si fournie
  const imageIri = imageFile ? await uploadMediaObject(imageFile, 'store_images') : null;
  
  // 2. Créer le magasin
  const response = await fetch('/api/admin/stores', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/ld+json',
      'Authorization': `Bearer ${token}`
    },
    body: JSON.stringify({
      name: storeData.name,
      description: storeData.description || null,
      image: imageIri,
      owner: ownerId ? `/api/admin/users/${ownerId}` : null,
      contact: storeData.contact ? {
        phone: storeData.contact.phone,
        email: storeData.contact.email
      } : null,
      location: storeData.location ? {
        address: storeData.location.address,
        latitude: storeData.location.latitude,
        longitude: storeData.location.longitude,
        city: storeData.location.city || null
      } : null
    })
  });
  
  if (!response.ok) {
    const error = await response.json();
    throw new Error(error.message || 'Échec de la création du magasin');
  }
  
  return await response.json();
}
```

### Mettre à jour un magasin

#### Mise à jour complète (PUT)

```bash
curl -X PUT "https://votre-api.com/api/admin/stores/1" \
  -H "Authorization: Bearer VOTRE_TOKEN" \
  -H "Content-Type: application/ld+json" \
  -d '{
    "name": "Pharmacie Centrale - Mise à jour",
    "description": "Description mise à jour",
    "image": "/api/media_objects/125",
    "owner": "/api/admin/users/2",
    "contact": {
      "phone": "+261349876543",
      "email": "nouveau@example.com"
    },
    "location": {
      "address": "456 Nouvelle Adresse",
      "latitude": -18.9000,
      "longitude": 47.5200,
      "city": "Antananarivo"
    }
  }'
```

#### Mise à jour partielle (PATCH)

```bash
# Mettre à jour uniquement le nom et l'image
curl -X PATCH "https://votre-api.com/api/admin/stores/1" \
  -H "Authorization: Bearer VOTRE_TOKEN" \
  -H "Content-Type: application/ld+json" \
  -d '{
    "name": "Nouveau nom",
    "image": "/api/media_objects/127"
  }'

# Mettre à jour uniquement la localisation
curl -X PATCH "https://votre-api.com/api/admin/stores/1" \
  -H "Authorization: Bearer VOTRE_TOKEN" \
  -H "Content-Type: application/ld+json" \
  -d '{
    "location": {
      "address": "789 Autre Adresse",
      "latitude": -18.9100,
      "longitude": 47.5300,
      "city": "Antananarivo"
    }
  }'
```

**Note :** Lors de la création d'un magasin, un `StoreSetting` avec des `BusinessHours` par défaut est automatiquement créé. L'image est automatiquement mappée avec `store_images`, et l'ancienne image est supprimée si elle est remplacée.

---

## 📦 Produits de magasin (Store Products)

### Endpoints disponibles

- **GET** `/api/admin/store-products` - Liste tous les produits de magasin
- **GET** `/api/admin/store-products/{id}` - Récupère un produit de magasin par son ID
- **POST** `/api/admin/store-products` - Crée un nouveau produit de magasin
- **PUT** `/api/admin/store-products/{id}` - Met à jour un produit de magasin existant (mise à jour complète)
- **PATCH** `/api/admin/store-products/{id}` - Met à jour un produit de magasin existant (mise à jour partielle)

### Structure des données

| Champ | Type | Requis | Description |
|-------|------|--------|-------------|
| `product` | string | ✅ Oui (create) | IRI du produit (ex: `"/api/products/1"`) |
| `store` | string | ✅ Oui (create) | IRI du magasin (ex: `"/api/admin/stores/1"`) |
| `price` | float | ✅ Oui (create) | Prix de vente (doit être > 0) |
| `stock` | integer | ✅ Oui (create) | Quantité en stock (doit être >= 0) |
| `unitPrice` | float | ❌ Non | Prix unitaire |

### Workflow complet : Créer un produit de magasin

#### Étape 1 : Récupérer les IRIs du produit et du magasin

```bash
# Récupérer un produit
curl -X GET "https://votre-api.com/api/products/1" \
  -H "Authorization: Bearer VOTRE_TOKEN"

# Réponse: { "@id": "/api/products/1", "id": 1, ... }

# Récupérer un magasin
curl -X GET "https://votre-api.com/api/admin/stores/1" \
  -H "Authorization: Bearer VOTRE_TOKEN"

# Réponse: { "@id": "/api/admin/stores/1", "id": 1, ... }
```

#### Étape 2 : Créer le produit de magasin

```bash
curl -X POST "https://votre-api.com/api/admin/store-products" \
  -H "Authorization: Bearer VOTRE_TOKEN" \
  -H "Content-Type: application/ld+json" \
  -d '{
    "product": "/api/products/1",
    "store": "/api/admin/stores/1",
    "price": 15000.00,
    "stock": 50,
    "unitPrice": 15000.00
  }'
```

**Exemple avec JavaScript :**
```javascript
async function createStoreProduct(productId, storeId, price, stock, unitPrice = null) {
  const response = await fetch('/api/admin/store-products', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/ld+json',
      'Authorization': `Bearer ${token}`
    },
    body: JSON.stringify({
      product: `/api/products/${productId}`,
      store: `/api/admin/stores/${storeId}`,
      price: price,
      stock: stock,
      unitPrice: unitPrice || null
    })
  });
  
  if (!response.ok) {
    const error = await response.json();
    throw new Error(error.message || 'Échec de la création du produit de magasin');
  }
  
  return await response.json();
}
```

### Mettre à jour un produit de magasin

#### Mise à jour complète (PUT)

```bash
curl -X PUT "https://votre-api.com/api/admin/store-products/1" \
  -H "Authorization: Bearer VOTRE_TOKEN" \
  -H "Content-Type: application/ld+json" \
  -d '{
    "product": "/api/products/2",
    "store": "/api/admin/stores/1",
    "price": 18000.00,
    "stock": 75,
    "unitPrice": 18000.00
  }'
```

#### Mise à jour partielle (PATCH)

```bash
# Mettre à jour uniquement le prix et le stock
curl -X PATCH "https://votre-api.com/api/admin/store-products/1" \
  -H "Authorization: Bearer VOTRE_TOKEN" \
  -H "Content-Type: application/ld+json" \
  -d '{
    "price": 16000.00,
    "stock": 60
  }'

# Mettre à jour uniquement le stock
curl -X PATCH "https://votre-api.com/api/admin/store-products/1" \
  -H "Authorization: Bearer VOTRE_TOKEN" \
  -H "Content-Type: application/ld+json" \
  -d '{
    "stock": 100
  }'
```

**Note :** 
- Un produit ne peut être associé qu'une seule fois à un magasin. Si vous essayez de créer un `StoreProduct` avec un produit et un magasin qui sont déjà associés, vous obtiendrez une erreur.
- Le prix doit être supérieur à 0.
- Le stock doit être supérieur ou égal à 0.

### Supprimer un produit de magasin

```bash
curl -X DELETE "https://votre-api.com/api/admin/store-products/1" \
  -H "Authorization: Bearer VOTRE_TOKEN"
```

**Exemple avec JavaScript :**
```javascript
async function deleteStoreProduct(storeProductId) {
  const response = await fetch(`/api/admin/store-products/${storeProductId}`, {
    method: 'DELETE',
    headers: {
      'Authorization': `Bearer ${token}`
    }
  });
  
  if (!response.ok) {
    const error = await response.json();
    throw new Error(error.message || 'Échec de la suppression du produit de magasin');
  }
  
  // DELETE retourne généralement 204 No Content
  return response.status === 204 ? null : await response.json();
}
```

---

## ⚙️ Paramètres de magasin (Store Settings)

### Endpoints disponibles

- **GET** `/api/admin/store-settings` - Liste tous les paramètres de magasin
- **GET** `/api/admin/store-settings/{id}` - Récupère les paramètres d'un magasin par ID
- **PUT** `/api/admin/store-settings/{id}` - Met à jour les paramètres d'un magasin (mise à jour complète)
- **PATCH** `/api/admin/store-settings/{id}` - Met à jour les paramètres d'un magasin (mise à jour partielle)
- **DELETE** `/api/admin/store-settings/{id}` - Supprime les paramètres d'un magasin

### Structure des données

| Champ | Type | Requis | Description |
|-------|------|--------|-------------|
| `mondayHours` | object | ❌ Non | Heures d'ouverture du lundi (BusinessHours) |
| `tuesdayHours` | object | ❌ Non | Heures d'ouverture du mardi (BusinessHours) |
| `wednesdayHours` | object | ❌ Non | Heures d'ouverture du mercredi (BusinessHours) |
| `thursdayHours` | object | ❌ Non | Heures d'ouverture du jeudi (BusinessHours) |
| `fridayHours` | object | ❌ Non | Heures d'ouverture du vendredi (BusinessHours) |
| `saturdayHours` | object | ❌ Non | Heures d'ouverture du samedi (BusinessHours) |
| `sundayHours` | object | ❌ Non | Heures d'ouverture du dimanche (BusinessHours) |

**Structure de BusinessHours :**
| Champ | Type | Requis | Description |
|-------|------|--------|-------------|
| `@id` | string | ❌ Non | IRI si BusinessHours existe déjà (ex: `"/api/business_hours/1"`). Omettez pour créer un nouveau. |
| `openTime` | string | ❌ Non | Heure d'ouverture au format `"HH:mm"` (ex: `"09:00"`) |
| `closeTime` | string | ❌ Non | Heure de fermeture au format `"HH:mm"` (ex: `"18:00"`) |
| `isClosed` | boolean | ❌ Non | Si le magasin est fermé ce jour-là (défaut: `false`) |

### Workflow complet : Récupérer les paramètres d'un magasin

#### Étape 1 : Récupérer le StoreSetting ID depuis le magasin

```bash
# Récupérer un magasin pour obtenir son StoreSetting ID
curl -X GET "https://votre-api.com/api/admin/stores/1" \
  -H "Authorization: Bearer VOTRE_TOKEN"

# Réponse inclut: { "setting": { "@id": "/api/store_settings/1", "id": 1, ... } }
```

#### Étape 2 : Récupérer les paramètres

```bash
curl -X GET "https://votre-api.com/api/admin/store-settings/1" \
  -H "Authorization: Bearer VOTRE_TOKEN"
```

**Exemple avec JavaScript :**
```javascript
async function getStoreSetting(storeSettingId) {
  const response = await fetch(`/api/admin/store-settings/${storeSettingId}`, {
    headers: { 'Authorization': `Bearer ${token}` }
  });
  return await response.json();
}
```

### Mettre à jour les paramètres d'un magasin

#### Mise à jour complète (PUT)

```bash
curl -X PUT "https://votre-api.com/api/admin/store-settings/1" \
  -H "Authorization: Bearer VOTRE_TOKEN" \
  -H "Content-Type: application/ld+json" \
  -d '{
    "mondayHours": {
      "openTime": "09:00",
      "closeTime": "18:00",
      "isClosed": false
    },
    "tuesdayHours": {
      "openTime": "09:00",
      "closeTime": "18:00",
      "isClosed": false
    },
    "wednesdayHours": {
      "openTime": "09:00",
      "closeTime": "18:00",
      "isClosed": false
    },
    "thursdayHours": {
      "openTime": "09:00",
      "closeTime": "18:00",
      "isClosed": false
    },
    "fridayHours": {
      "openTime": "09:00",
      "closeTime": "18:00",
      "isClosed": false
    },
    "saturdayHours": {
      "openTime": "10:00",
      "closeTime": "16:00",
      "isClosed": false
    },
    "sundayHours": {
      "isClosed": true
    }
  }'
```

#### Mise à jour partielle (PATCH)

```bash
# Mettre à jour uniquement les heures du lundi
curl -X PATCH "https://votre-api.com/api/admin/store-settings/1" \
  -H "Authorization: Bearer VOTRE_TOKEN" \
  -H "Content-Type: application/ld+json" \
  -d '{
    "mondayHours": {
      "openTime": "08:00",
      "closeTime": "20:00",
      "isClosed": false
    }
  }'

# Fermer le magasin le dimanche
curl -X PATCH "https://votre-api.com/api/admin/store-settings/1" \
  -H "Authorization: Bearer VOTRE_TOKEN" \
  -H "Content-Type: application/ld+json" \
  -d '{
    "sundayHours": {
      "isClosed": true
    }
  }'
```

**Exemple avec JavaScript :**
```javascript
async function updateStoreSetting(storeSettingId, updates) {
  const response = await fetch(`/api/admin/store-settings/${storeSettingId}`, {
    method: 'PATCH',
    headers: {
      'Content-Type': 'application/ld+json',
      'Authorization': `Bearer ${token}`
    },
    body: JSON.stringify(updates)
  });
  
  if (!response.ok) {
    const error = await response.json();
    throw new Error(error.message || 'Échec de la mise à jour des paramètres');
  }
  
  return await response.json();
}

// Exemple d'utilisation
await updateStoreSetting(1, {
  mondayHours: {
    openTime: "08:00",
    closeTime: "20:00",
    isClosed: false
  },
  sundayHours: {
    isClosed: true
  }
});
```

### Supprimer les paramètres d'un magasin

```bash
curl -X DELETE "https://votre-api.com/api/admin/store-settings/1" \
  -H "Authorization: Bearer VOTRE_TOKEN"
```

**Exemple avec JavaScript :**
```javascript
async function deleteStoreSetting(storeSettingId) {
  const response = await fetch(`/api/admin/store-settings/${storeSettingId}`, {
    method: 'DELETE',
    headers: { 'Authorization': `Bearer ${token}` }
  });
  
  if (!response.ok) {
    const error = await response.json();
    throw new Error(error.message || 'Échec de la suppression des paramètres');
  }
  
  return response.status === 204 ? null : await response.json();
}
```

**Note :** 
- Les heures d'ouverture doivent être au format `"HH:mm"` (ex: `"09:00"`, `"18:30"`)
- Si `isClosed` est `true`, `openTime` et `closeTime` peuvent être `null`
- Pour mettre à jour un BusinessHours existant, incluez son `@id` dans l'objet. Sinon, un nouveau BusinessHours sera créé.
- La suppression d'un StoreSetting supprimera également tous les BusinessHours associés.

---

## Mappings d'images disponibles

Le paramètre `mapping` lors de l'upload détermine où le fichier sera stocké :

| Mapping | Dossier de stockage | Usage recommandé |
|---------|---------------------|-------------------|
| `category_images` | `/public/images/categories/` | Images de catégories |
| `category_icons` | `/public/icons/categories/` | Icônes SVG de catégories |
| `product_images` | `/public/images/products/` | Images de produits |
| `brand_images` | `/public/images/brands/` | Images/logos de marques |
| `manufacturer_images` | `/public/images/manufacturers/` | Images/logos de fabricants |
| `user_images` | `/public/images/users/` | **Avatars d'utilisateurs (recommandé)** |
| `store_images` | `/public/images/stores/` | Photos de magasins |
| `media_object` | `/public/media/` | Par défaut (générique, documents de livraison, etc.) |

**Pour les avatars d'utilisateurs, utilisez toujours `mapping=user_images`.**

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

### Utilisateurs
- `GET /api/admin/users` - Liste des utilisateurs
- `GET /api/admin/users/{id}` - Détails d'un utilisateur
- `POST /api/admin/users` - Créer un utilisateur
- `PUT /api/admin/users/{id}` - Mettre à jour un utilisateur (complète)
- `PATCH /api/admin/users/{id}` - Mettre à jour un utilisateur (partielle)
- `DELETE /api/admin/users/{id}` - Supprimer un utilisateur
- `POST /api/admin/users/{id}/toggle-active` - Activer/désactiver un utilisateur

### Magasins
- `GET /api/admin/stores` - Liste des magasins
- `GET /api/admin/stores/{id}` - Détails d'un magasin
- `POST /api/admin/stores` - Créer un magasin
- `PUT /api/admin/stores/{id}` - Mettre à jour un magasin (complète)
- `PATCH /api/admin/stores/{id}` - Mettre à jour un magasin (partielle)
- `DELETE /api/admin/stores/{id}` - Supprimer un magasin
- `POST /api/admin/stores/batch-delete` - Supprimer plusieurs magasins en lot

### Produits de magasin
- `GET /api/admin/store-products` - Liste des produits de magasin
- `GET /api/admin/store-products/{id}` - Détails d'un produit de magasin
- `POST /api/admin/store-products` - Créer un produit de magasin
- `PUT /api/admin/store-products/{id}` - Mettre à jour un produit de magasin (complète)
- `PATCH /api/admin/store-products/{id}` - Mettre à jour un produit de magasin (partielle)
- `DELETE /api/admin/store-products/{id}` - Supprimer un produit de magasin

### Paramètres de magasin
- `GET /api/admin/store-settings` - Liste des paramètres de magasin
- `GET /api/admin/store-settings/{id}` - Détails des paramètres d'un magasin
- `PUT /api/admin/store-settings/{id}` - Mettre à jour les paramètres d'un magasin (complète)
- `PATCH /api/admin/store-settings/{id}` - Mettre à jour les paramètres d'un magasin (partielle)
- `DELETE /api/admin/store-settings/{id}` - Supprimer les paramètres d'un magasin

### Images
- `POST /api/media_objects` - Uploader une image/icône

---

## Ressources supplémentaires

- [Documentation API Produits Admin](./API_PRODUCTS_ADMIN.md)
- [Guide complet d'upload d'images](./GUIDE_UPLOAD_IMAGES.md)
- [Documentation API Images complète](./API_IMAGES_COMPLETE.md)
- [Pattern d'upload de fichiers](./FILE_UPLOAD_PATTERN.md)

