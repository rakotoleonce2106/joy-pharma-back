# Documentation API : Gestion des Produits (Admin)

## Vue d'ensemble

Cette documentation explique comment créer, mettre à jour et gérer les produits via l'API Admin, incluant l'upload d'images.

## Authentification

Tous les endpoints admin nécessitent une authentification avec le rôle `ROLE_ADMIN`. Utilisez un token JWT dans l'en-tête `Authorization` :

```http
Authorization: Bearer VOTRE_TOKEN_JWT
```

## Endpoints disponibles

### Liste des produits
- **GET** `/api/admin/products` - Liste tous les produits (paginé)

### Détails d'un produit
- **GET** `/api/admin/products/{id}` - Récupère un produit par son ID

### Créer un produit
- **POST** `/api/admin/products` - Crée un nouveau produit

### Mettre à jour un produit
- **PUT** `/api/admin/products/{id}` - Met à jour un produit existant

### Supprimer un produit
- **DELETE** `/api/admin/products/{id}` - Supprime un produit
- **POST** `/api/admin/products/batch-delete` - Supprime plusieurs produits en lot

---

## Structure des données

### Champs du produit (ProductInput)

| Champ | Type | Requis | Description |
|-------|------|--------|-------------|
| `name` | string | ✅ Oui | Nom du produit |
| `code` | string | ✅ Oui | Code unique du produit (doit être unique) |
| `description` | string | ❌ Non | Description du produit |
| `categories` | array<int> | ❌ Non | Tableau d'IDs de catégories |
| `form` | int | ❌ Non | ID de la forme (comprimé, sirop, etc.) |
| `brand` | int | ❌ Non | ID de la marque |
| `manufacturer` | int | ❌ Non | ID du fabricant |
| `unit` | int | ❌ Non | ID de l'unité (boîte, flacon, etc.) |
| `unitPrice` | float | ❌ Non | Prix unitaire |
| `totalPrice` | float | ❌ Non | Prix total |
| `quantity` | float | ❌ Non | Quantité |
| `stock` | int | ❌ Non | Stock disponible |
| `currency` | string | ❌ Non | Code devise (ex: "MGA", "EUR") |
| `isActive` | boolean | ❌ Non | Statut actif/inactif (défaut: `true`) |
| `variants` | array | ❌ Non | Variantes du produit (structure libre) |
| `images` | array<string> | ❌ Non | Tableau d'IRIs d'images (ex: `["/api/media_objects/123"]`) |

---

## Workflow complet : Créer un produit avec images

### Étape 1 : Uploader les images

Avant de créer le produit, vous devez d'abord uploader les images via l'endpoint `/api/media_objects`.

**Endpoint :** `POST /api/media_objects`

**Format :** `multipart/form-data`

**Paramètres :**
- `file` : Le fichier image (requis)
- `mapping` : `"product_images"` (recommandé pour les produits)

**Exemple avec cURL :**
```bash
# Upload image 1
curl -X POST "https://votre-api.com/api/media_objects" \
  -H "Authorization: Bearer VOTRE_TOKEN" \
  -F "file=@/chemin/vers/image1.jpg" \
  -F "mapping=product_images"

# Réponse :
# {
#   "@id": "/api/media_objects/123",
#   "@type": "https://schema.org/MediaObject",
#   "contentUrl": "/images/products/image1.jpg",
#   "id": 123
# }

# Upload image 2
curl -X POST "https://votre-api.com/api/media_objects" \
  -H "Authorization: Bearer VOTRE_TOKEN" \
  -F "file=@/chemin/vers/image2.jpg" \
  -F "mapping=product_images"

# Réponse :
# {
#   "@id": "/api/media_objects/124",
#   "contentUrl": "/images/products/image2.jpg",
#   "id": 124
# }
```

**Exemple avec JavaScript :**
```javascript
async function uploadProductImage(imageFile) {
  const formData = new FormData();
  formData.append('file', imageFile);
  formData.append('mapping', 'product_images');
  
  const response = await fetch('/api/media_objects', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`
    },
    body: formData
  });
  
  if (!response.ok) {
    throw new Error('Échec de l\'upload de l\'image');
  }
  
  const mediaObject = await response.json();
  return mediaObject['@id']; // Retourne "/api/media_objects/123"
}

// Uploader plusieurs images
const imageFiles = [file1, file2, file3];
const imageIris = await Promise.all(
  imageFiles.map(file => uploadProductImage(file))
);
// imageIris = ["/api/media_objects/123", "/api/media_objects/124", "/api/media_objects/125"]
```

### Étape 2 : Récupérer les IDs des relations (optionnel)

Si vous devez associer le produit à des catégories, marques, etc., récupérez d'abord leurs IDs :

```bash
# Liste des catégories
curl -X GET "https://votre-api.com/api/admin/categories" \
  -H "Authorization: Bearer VOTRE_TOKEN"

# Liste des marques
curl -X GET "https://votre-api.com/api/admin/brands" \
  -H "Authorization: Bearer VOTRE_TOKEN"

# Liste des fabricants
curl -X GET "https://votre-api.com/api/admin/manufacturers" \
  -H "Authorization: Bearer VOTRE_TOKEN"

# Liste des formes
curl -X GET "https://votre-api.com/api/admin/forms" \
  -H "Authorization: Bearer VOTRE_TOKEN"

# Liste des unités
curl -X GET "https://votre-api.com/api/admin/units" \
  -H "Authorization: Bearer VOTRE_TOKEN"
```

### Étape 3 : Créer le produit

**Endpoint :** `POST /api/admin/products`

**Format :** `application/json`

**Exemple avec cURL :**
```bash
curl -X POST "https://votre-api.com/api/admin/products" \
  -H "Authorization: Bearer VOTRE_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Paracétamol 500mg",
    "code": "PARA-500-001",
    "description": "Comprimé de paracétamol 500mg, boîte de 20 comprimés",
    "categories": [1, 5],
    "form": 1,
    "brand": 2,
    "manufacturer": 3,
    "unit": 1,
    "unitPrice": 2500.00,
    "totalPrice": 50000.00,
    "quantity": 20,
    "stock": 150,
    "currency": "MGA",
    "isActive": true,
    "variants": {
      "dosage": "500mg",
      "packaging": "boîte de 20"
    },
    "images": [
      "/api/media_objects/123",
      "/api/media_objects/124"
    ]
  }'
```

**Exemple avec JavaScript :**
```javascript
async function createProduct(productData, imageIris) {
  const response = await fetch('/api/admin/products', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${token}`
    },
    body: JSON.stringify({
      name: productData.name,
      code: productData.code,
      description: productData.description,
      categories: productData.categoryIds,
      form: productData.formId,
      brand: productData.brandId,
      manufacturer: productData.manufacturerId,
      unit: productData.unitId,
      unitPrice: productData.unitPrice,
      totalPrice: productData.totalPrice,
      quantity: productData.quantity,
      stock: productData.stock,
      currency: productData.currency,
      isActive: productData.isActive,
      variants: productData.variants,
      images: imageIris // Tableau d'IRIs
    })
  });
  
  if (!response.ok) {
    const error = await response.json();
    throw new Error(error.message || 'Échec de la création du produit');
  }
  
  return await response.json();
}

// Utilisation
const product = await createProduct({
  name: 'Paracétamol 500mg',
  code: 'PARA-500-001',
  description: 'Comprimé de paracétamol 500mg',
  categoryIds: [1, 5],
  formId: 1,
  brandId: 2,
  manufacturerId: 3,
  unitId: 1,
  unitPrice: 2500.00,
  totalPrice: 50000.00,
  quantity: 20,
  stock: 150,
  currency: 'MGA',
  isActive: true,
  variants: { dosage: '500mg', packaging: 'boîte de 20' }
}, ['/api/media_objects/123', '/api/media_objects/124']);
```

**Réponse :**
```json
{
  "@id": "/api/products/42",
  "@type": "Product",
  "id": 42,
  "name": "Paracétamol 500mg",
  "code": "PARA-500-001",
  "description": "Comprimé de paracétamol 500mg, boîte de 20 comprimés",
  "images": [
    {
      "@id": "/api/media_objects/123",
      "id": 123,
      "contentUrl": "/images/products/image1.jpg"
    },
    {
      "@id": "/api/media_objects/124",
      "id": 124,
      "contentUrl": "/images/products/image2.jpg"
    }
  ],
  "categories": [...],
  "form": {...},
  "brand": {...},
  "manufacturer": {...},
  "unit": {...},
  "unitPrice": 2500.00,
  "totalPrice": 50000.00,
  "quantity": 20,
  "stock": 150,
  "currency": {...},
  "isActive": true,
  "variants": {
    "dosage": "500mg",
    "packaging": "boîte de 20"
  }
}
```

---

## Mettre à jour un produit

### Étape 1 : Récupérer le produit actuel (recommandé)

Pour ajouter des images, vous devez d'abord récupérer les images existantes :

```bash
curl -X GET "https://votre-api.com/api/admin/products/42" \
  -H "Authorization: Bearer VOTRE_TOKEN"
```

**Réponse :**
```json
{
  "@id": "/api/products/42",
  "id": 42,
  "name": "Paracétamol 500mg",
  "images": [
    {
      "@id": "/api/media_objects/123",
      "id": 123,
      "contentUrl": "/images/products/image1.jpg"
    },
    {
      "@id": "/api/media_objects/124",
      "id": 124,
      "contentUrl": "/images/products/image2.jpg"
    }
  ],
  ...
}
```

### Étape 2 : Uploader de nouvelles images (si nécessaire)

Si vous voulez ajouter des images, uploader d'abord les nouvelles images :

```bash
# Uploader une nouvelle image
curl -X POST "https://votre-api.com/api/media_objects" \
  -H "Authorization: Bearer VOTRE_TOKEN" \
  -F "file=@/chemin/vers/nouvelle-image.jpg" \
  -F "mapping=product_images"

# Réponse: { "@id": "/api/media_objects/125", "id": 125, ... }

# Uploader une autre image
curl -X POST "https://votre-api.com/api/media_objects" \
  -H "Authorization: Bearer VOTRE_TOKEN" \
  -F "file=@/chemin/vers/autre-image.jpg" \
  -F "mapping=product_images"

# Réponse: { "@id": "/api/media_objects/126", "id": 126, ... }
```

### Étape 3 : Mettre à jour le produit avec toutes les images

**Endpoint :** `PUT /api/admin/products/{id}` ou `PATCH /api/admin/products/{id}`

**Format :** `application/json` ou `application/ld+json`

**Important :** 
- Avec **PUT** : vous devez fournir tous les champs (même ceux qui ne changent pas)
- Avec **PATCH** : vous pouvez fournir uniquement les champs à modifier (recommandé pour les mises à jour partielles)
- Pour les images : fournir **TOUTES** les images que vous voulez garder (anciennes + nouvelles). Les images non incluses dans le tableau seront automatiquement supprimées.
- API Platform gère automatiquement la conversion des IRIs JSON-LD en entités

**Exemple avec cURL - Ajouter des images (garder les anciennes + nouvelles) :**
```bash
# Mettre à jour le produit en ajoutant 2 nouvelles images aux images existantes
# Images existantes: /api/media_objects/123, /api/media_objects/124
# Nouvelles images uploadées: /api/media_objects/125, /api/media_objects/126
# Résultat: 4 images au total

curl -X PATCH "https://votre-api.com/api/admin/products/42" \
  -H "Authorization: Bearer VOTRE_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "images": [
      "/api/media_objects/123",
      "/api/media_objects/124",
      "/api/media_objects/125",
      "/api/media_objects/126"
    ]
  }'
```

**Exemple avec cURL - Remplacer toutes les images :**
```bash
# Remplacer toutes les images par de nouvelles
curl -X PATCH "https://votre-api.com/api/admin/products/42" \
  -H "Authorization: Bearer VOTRE_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "images": [
      "/api/media_objects/125",
      "/api/media_objects/126"
    ]
  }'
```

**Exemple avec cURL - Mettre à jour plusieurs champs + ajouter des images :**
```bash
curl -X PUT "https://votre-api.com/api/admin/products/42" \
  -H "Authorization: Bearer VOTRE_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Paracétamol 500mg - Nouveau packaging",
    "code": "PARA-500-001",
    "description": "Comprimé de paracétamol 500mg, boîte de 30 comprimés",
    "categories": ["/api/categories/1", "/api/categories/5", "/api/categories/7"],
    "form": "/api/forms/1",
    "brand": "/api/brands/2",
    "manufacturer": "/api/manufacturers/3",
    "unit": "/api/units/1",
    "unitPrice": 2800.00,
    "totalPrice": 84000.00,
    "quantity": 30,
    "stock": 200,
    "currency": "MGA",
    "isActive": true,
    "variants": {
      "dosage": "500mg",
      "packaging": "boîte de 30"
    },
    "images": [
      "/api/media_objects/123",
      "/api/media_objects/124",
      "/api/media_objects/125",
      "/api/media_objects/126"
    ]
  }'
```

**Note :** Avec l'API native d'API Platform, vous pouvez utiliser soit des IRIs simples (`"/api/media_objects/123"`), soit le format JSON-LD complet. API Platform convertit automatiquement les IRIs en entités.

**Exemple avec JavaScript - Ajouter des images :**
```javascript
async function addImagesToProduct(productId, newImageFiles) {
  // 1. Récupérer le produit actuel pour obtenir les images existantes
  const productResponse = await fetch(`/api/admin/products/${productId}`, {
    headers: { 'Authorization': `Bearer ${token}` }
  });
  const product = await productResponse.json();
  
  // 2. Uploader les nouvelles images
  const newImageIris = await Promise.all(
    newImageFiles.map(file => uploadProductImage(file))
  );
  
  // 3. Combiner les anciennes et nouvelles images
  const currentImageIris = product.images.map(img => img['@id'] || `/api/media_objects/${img.id}`);
  const allImageIris = [...currentImageIris, ...newImageIris];
  
  // 4. Mettre à jour le produit avec PATCH (mise à jour partielle)
  const response = await fetch(`/api/admin/products/${productId}`, {
    method: 'PATCH',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${token}`
    },
    body: JSON.stringify({
      images: allImageIris
    })
  });
  
  if (!response.ok) {
    const error = await response.json();
    throw new Error(error.message || 'Échec de la mise à jour du produit');
  }
  
  return await response.json();
}

// Utilisation
await addImagesToProduct(42, [file1, file2, file3]);
```

**Exemple avec JavaScript - Mettre à jour avec JSON-LD :**
```javascript
async function updateProduct(productId, productData, imageIris) {
  const response = await fetch(`/api/admin/products/${productId}`, {
    method: 'PATCH', // Utiliser PATCH pour mise à jour partielle
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${token}`
    },
    body: JSON.stringify({
      name: productData.name,
      description: productData.description,
      unitPrice: productData.unitPrice,
      stock: productData.stock,
      // Utiliser des IRIs - API Platform les convertit automatiquement
      categories: productData.categoryIds.map(id => `/api/categories/${id}`),
      form: productData.formId ? `/api/forms/${productData.formId}` : null,
      brand: productData.brandId ? `/api/brands/${productData.brandId}` : null,
      manufacturer: productData.manufacturerId ? `/api/manufacturers/${productData.manufacturerId}` : null,
      unit: productData.unitId ? `/api/units/${productData.unitId}` : null,
      images: imageIris // Toutes les images à garder (anciennes + nouvelles)
    })
  });
  
  if (!response.ok) {
    const error = await response.json();
    throw new Error(error.message || 'Échec de la mise à jour du produit');
  }
  
  return await response.json();
}
```

---

## Gestion des images

### ⭐ Ajouter plusieurs images à un produit existant (Recommandé)

C'est le cas d'usage le plus courant : vous avez un produit avec des images existantes et vous voulez en ajouter d'autres.

**Exemple complet avec cURL :**
```bash
# 1. Récupérer le produit actuel
PRODUCT=$(curl -X GET "https://votre-api.com/api/admin/products/42" \
  -H "Authorization: Bearer VOTRE_TOKEN")

# 2. Uploader plusieurs nouvelles images
curl -X POST "https://votre-api.com/api/media_objects" \
  -H "Authorization: Bearer VOTRE_TOKEN" \
  -F "file=@image3.jpg" \
  -F "mapping=product_images"
# Réponse: { "@id": "/api/media_objects/125", ... }

curl -X POST "https://votre-api.com/api/media_objects" \
  -H "Authorization: Bearer VOTRE_TOKEN" \
  -F "file=@image4.jpg" \
  -F "mapping=product_images"
# Réponse: { "@id": "/api/media_objects/126", ... }

curl -X POST "https://votre-api.com/api/media_objects" \
  -H "Authorization: Bearer VOTRE_TOKEN" \
  -F "file=@image5.jpg" \
  -F "mapping=product_images"
# Réponse: { "@id": "/api/media_objects/127", ... }

# 3. Mettre à jour le produit avec TOUTES les images (anciennes + nouvelles)
# Supposons que le produit avait déjà: /api/media_objects/123, /api/media_objects/124
curl -X PATCH "https://votre-api.com/api/admin/products/42" \
  -H "Authorization: Bearer VOTRE_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "images": [
      "/api/media_objects/123",
      "/api/media_objects/124",
      "/api/media_objects/125",
      "/api/media_objects/126",
      "/api/media_objects/127"
    ]
  }'
```

**Exemple complet avec JavaScript :**
```javascript
async function addMultipleImagesToProduct(productId, newImageFiles) {
  // 1. Récupérer le produit actuel
  const productResponse = await fetch(`/api/admin/products/${productId}`, {
    headers: { 'Authorization': `Bearer ${token}` }
  });
  const product = await productResponse.json();
  
  // 2. Extraire les IRIs des images existantes
  const existingImageIris = product.images.map(img => 
    img['@id'] || `/api/media_objects/${img.id}`
  );
  
  // 3. Uploader toutes les nouvelles images en parallèle
  const uploadPromises = newImageFiles.map(file => {
    const formData = new FormData();
    formData.append('file', file);
    formData.append('mapping', 'product_images');
    
    return fetch('/api/media_objects', {
      method: 'POST',
      headers: { 'Authorization': `Bearer ${token}` },
      body: formData
    }).then(res => res.json()).then(data => data['@id']);
  });
  
  const newImageIris = await Promise.all(uploadPromises);
  
  // 4. Combiner toutes les images (anciennes + nouvelles)
  const allImageIris = [...existingImageIris, ...newImageIris];
  
  // 5. Mettre à jour le produit avec PATCH
  const response = await fetch(`/api/admin/products/${productId}`, {
    method: 'PATCH',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${token}`
    },
    body: JSON.stringify({
      images: allImageIris
    })
  });
  
  if (!response.ok) {
    const error = await response.json();
    throw new Error(error.message || 'Échec de l\'ajout des images');
  }
  
  return await response.json();
}

// Utilisation : ajouter 3 nouvelles images
const fileInput = document.querySelector('input[type="file"]');
await addMultipleImagesToProduct(42, Array.from(fileInput.files));
```

### Ajouter des images à un produit existant (méthode alternative)

**Workflow complet :**

1. **Récupérer le produit actuel** pour obtenir les images existantes
2. **Uploader les nouvelles images** via `/api/media_objects`
3. **Combiner les IRIs** (anciennes + nouvelles)
4. **Mettre à jour le produit** avec PATCH en incluant toutes les images

```javascript
async function addImagesToProduct(productId, newImageFiles) {
  // 1. Récupérer le produit actuel
  const productResponse = await fetch(`/api/admin/products/${productId}`, {
    headers: { 'Authorization': `Bearer ${token}` }
  });
  const product = await productResponse.json();
  
  // 2. Uploader les nouvelles images
  const newImageIris = await Promise.all(
    newImageFiles.map(file => uploadProductImage(file))
  );
  
  // 3. Combiner les anciennes et nouvelles images
  const currentImageIris = product.images.map(img => img['@id'] || `/api/media_objects/${img.id}`);
  const allImageIris = [...currentImageIris, ...newImageIris];
  
  // 4. Mettre à jour le produit avec PATCH (seulement les images)
  const response = await fetch(`/api/admin/products/${productId}`, {
    method: 'PATCH',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${token}`
    },
    body: JSON.stringify({
      images: allImageIris
    })
  });
  
  if (!response.ok) {
    throw new Error('Échec de l\'ajout des images');
  }
  
  return await response.json();
}

// Utilisation
await addImagesToProduct(42, [file1, file2, file3]); // Ajoute 3 nouvelles images
```

### Supprimer des images d'un produit

1. Récupérer les images actuelles
2. Filtrer les images à supprimer
3. Mettre à jour le produit avec le tableau filtré

```javascript
async function removeImagesFromProduct(productId, imageIdsToRemove) {
  // 1. Récupérer le produit actuel
  const productResponse = await fetch(`/api/admin/products/${productId}`, {
    headers: { 'Authorization': `Bearer ${token}` }
  });
  const product = await productResponse.json();
  
  // 2. Filtrer les images à garder
  const imageIrisToKeep = product.images
    .filter(img => !imageIdsToRemove.includes(img.id))
    .map(img => img['@id']);
  
  // 3. Mettre à jour le produit
  return await updateProduct(productId, product, imageIrisToKeep);
}
```

### Remplacer toutes les images

```javascript
async function replaceAllProductImages(productId, newImageFiles) {
  // 1. Uploader les nouvelles images
  const newImageIris = await Promise.all(
    newImageFiles.map(file => uploadProductImage(file))
  );
  
  // 2. Récupérer le produit actuel
  const productResponse = await fetch(`/api/admin/products/${productId}`, {
    headers: { 'Authorization': `Bearer ${token}` }
  });
  const product = await productResponse.json();
  
  // 3. Mettre à jour avec uniquement les nouvelles images
  return await updateProduct(productId, product, newImageIris);
}
```

---

## Exemples complets

### Exemple 1 : Créer un produit simple sans images

```bash
curl -X POST "https://votre-api.com/api/admin/products" \
  -H "Authorization: Bearer VOTRE_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Aspirine 100mg",
    "code": "ASP-100-001",
    "description": "Comprimé d'\''aspirine 100mg",
    "isActive": true
  }'
```

### Exemple 2 : Créer un produit complet avec toutes les relations

```bash
curl -X POST "https://votre-api.com/api/admin/products" \
  -H "Authorization: Bearer VOTRE_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Ibuprofène 400mg",
    "code": "IBU-400-001",
    "description": "Comprimé d'\''ibuprofène 400mg, anti-inflammatoire",
    "categories": [1, 2, 3],
    "form": 1,
    "brand": 5,
    "manufacturer": 8,
    "unit": 2,
    "unitPrice": 3500.00,
    "totalPrice": 70000.00,
    "quantity": 20,
    "stock": 100,
    "currency": "MGA",
    "isActive": true,
    "variants": {
      "dosage": "400mg",
      "packaging": "boîte de 20 comprimés",
      "prescription": false
    },
    "images": [
      "/api/media_objects/200",
      "/api/media_objects/201",
      "/api/media_objects/202"
    ]
  }'
```

### Exemple 3 : Mettre à jour uniquement le stock

```bash
# 1. Récupérer le produit actuel
PRODUCT=$(curl -X GET "https://votre-api.com/api/admin/products/42" \
  -H "Authorization: Bearer VOTRE_TOKEN")

# 2. Extraire les données et mettre à jour uniquement le stock
curl -X PUT "https://votre-api.com/api/admin/products/42" \
  -H "Authorization: Bearer VOTRE_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Paracétamol 500mg",
    "code": "PARA-500-001",
    "description": "Comprimé de paracétamol 500mg",
    "categories": [1, 5],
    "form": 1,
    "brand": 2,
    "manufacturer": 3,
    "unit": 1,
    "unitPrice": 2500.00,
    "totalPrice": 50000.00,
    "quantity": 20,
    "stock": 300,
    "currency": "MGA",
    "isActive": true,
    "variants": {},
    "images": [
      "/api/media_objects/123",
      "/api/media_objects/124"
    ]
  }'
```

---

## Gestion des erreurs

### Erreurs communes

#### Code du produit déjà existant
```json
{
  "type": "https://tools.ietf.org/html/rfc2616#section-10",
  "title": "An error occurred",
  "detail": "Product with this code already exists",
  "status": 400
}
```

**Solution :** Utiliser un code unique pour chaque produit.

#### Relation introuvable (form, brand, etc.)
```json
{
  "type": "https://tools.ietf.org/html/rfc2616#section-10",
  "title": "An error occurred",
  "detail": "Form not found",
  "status": 400
}
```

**Solution :** Vérifier que l'ID de la relation existe via les endpoints GET correspondants.

#### Produit introuvable (pour PUT)
```json
{
  "type": "https://tools.ietf.org/html/rfc2616#section-10",
  "title": "An error occurred",
  "detail": "Product not found",
  "status": 404
}
```

**Solution :** Vérifier que l'ID du produit est correct.

#### Champs requis manquants
```json
{
  "type": "https://tools.ietf.org/html/rfc2616#section-10",
  "title": "An error occurred",
  "detail": "name: This value should not be blank.",
  "status": 422
}
```

**Solution :** Fournir tous les champs requis (`name` et `code`).

---

## Mappings d'images disponibles

Le paramètre `mapping` lors de l'upload détermine où le fichier sera stocké :

| Mapping | Dossier de stockage | Usage recommandé |
|---------|---------------------|-------------------|
| `product_images` | `/public/images/products/` | Images de produits (recommandé) |
| `media_object` | `/public/media/` | Par défaut (générique) |
| `category_images` | `/public/images/categories/` | Images de catégories |
| `category_icons` | `/public/icons/categories/` | Icônes SVG de catégories |
| `brand_images` | `/public/images/brands/` | Images de marques |
| `manufacturer_images` | `/public/images/manufacturers/` | Images de fabricants |

**Pour les produits, utilisez toujours `mapping=product_images`.**

---

## Supprimer un produit

### Supprimer un seul produit

```bash
curl -X DELETE "https://votre-api.com/api/admin/products/42" \
  -H "Authorization: Bearer VOTRE_TOKEN"
```

**Note :** Les images associées seront automatiquement supprimées si elles ne sont plus utilisées par d'autres entités.

### Supprimer plusieurs produits en lot

```bash
curl -X POST "https://votre-api.com/api/admin/products/batch-delete" \
  -H "Authorization: Bearer VOTRE_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "ids": [42, 43, 44, 45]
  }'
```

---

## Bonnes pratiques

1. **Toujours uploader les images d'abord** avant de créer/mettre à jour le produit
2. **Utiliser `mapping=product_images`** pour les images de produits
3. **Vérifier l'unicité du code** avant de créer un produit
4. **Récupérer les IDs des relations** (catégories, marques, etc.) avant de créer le produit
5. **Pour les mises à jour**, fournir TOUTES les images que vous voulez garder (les autres seront supprimées)
6. **Gérer les erreurs** et afficher des messages clairs à l'utilisateur
7. **Valider les données** côté client avant d'envoyer à l'API

---

## Endpoints de référence

### Produits
- `GET /api/admin/products` - Liste des produits
- `GET /api/admin/products/{id}` - Détails d'un produit
- `POST /api/admin/products` - Créer un produit
- `PUT /api/admin/products/{id}` - Mettre à jour un produit
- `DELETE /api/admin/products/{id}` - Supprimer un produit
- `POST /api/admin/products/batch-delete` - Supprimer plusieurs produits

### Images
- `POST /api/media_objects` - Uploader une image

### Relations
- `GET /api/admin/categories` - Liste des catégories
- `GET /api/admin/brands` - Liste des marques
- `GET /api/admin/manufacturers` - Liste des fabricants
- `GET /api/admin/forms` - Liste des formes
- `GET /api/admin/units` - Liste des unités

---

## Ressources supplémentaires

- [Guide complet d'upload d'images](./GUIDE_UPLOAD_IMAGES.md)
- [Documentation API Images complète](./API_IMAGES_COMPLETE.md)
- [Pattern d'upload de fichiers](./FILE_UPLOAD_PATTERN.md)

