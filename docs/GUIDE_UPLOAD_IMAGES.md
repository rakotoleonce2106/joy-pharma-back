# Guide : Comment utiliser l'API Media pour les images

## Vue d'ensemble

L'API Media (`/api/media_objects`) est l'endpoint central pour uploader et gérer toutes les images de l'application. Une fois une image uploadée, vous recevez un IRI (Identifiant de Ressource) que vous pouvez utiliser dans les autres endpoints.

## Endpoint principal

**POST** `/api/media_objects`

Cet endpoint accepte uniquement `multipart/form-data` (POST uniquement, limitation PHP).

## Workflow complet

### Étape 1 : Uploader l'image via l'API Media

#### Créer une nouvelle image

```http
POST /api/media_objects
Content-Type: multipart/form-data

file: [fichier binaire]
mapping: "category_images" (optionnel)
```

**Exemple avec cURL :**
```bash
curl -X POST "https://votre-api.com/api/media_objects" \
  -H "Authorization: Bearer VOTRE_TOKEN" \
  -F "file=@/chemin/vers/image.jpg" \
  -F "mapping=category_images"
```

**Exemple avec JavaScript :**
```javascript
const formData = new FormData();
formData.append('file', fileInput.files[0]);
formData.append('mapping', 'category_images');

const response = await fetch('/api/media_objects', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer VOTRE_TOKEN'
  },
  body: formData
});

const mediaObject = await response.json();
const imageIri = mediaObject['@id']; // "/api/media_objects/123"
```

**Réponse :**
```json
{
  "@id": "/api/media_objects/123",
  "@type": "https://schema.org/MediaObject",
  "contentUrl": "/images/categories/abc123.jpg",
  "id": 123
}
```

#### Mettre à jour une image existante

Si vous voulez remplacer une image existante (au lieu d'en créer une nouvelle) :

```http
POST /api/media_objects
Content-Type: multipart/form-data

id: 123 (ID du MediaObject existant à mettre à jour)
file: [nouveau fichier binaire]
mapping: "category_images" (optionnel)
```

**Exemple avec cURL :**
```bash
curl -X POST "https://votre-api.com/api/media_objects" \
  -H "Authorization: Bearer VOTRE_TOKEN" \
  -F "id=123" \
  -F "file=@/chemin/vers/nouvelle-image.jpg" \
  -F "mapping=category_images"
```

**Exemple avec JavaScript :**
```javascript
const formData = new FormData();
formData.append('id', 123); // ID du MediaObject existant
formData.append('file', fileInput.files[0]);
formData.append('mapping', 'category_images');

const response = await fetch('/api/media_objects', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer VOTRE_TOKEN'
  },
  body: formData
});
```

**Comportement :**
- Si `id` est fourni **ET** le MediaObject existe → **Mise à jour** de l'image existante
- Si `id` est fourni **MAIS** le MediaObject n'existe pas → **Création** d'un nouveau MediaObject
- Si `id` n'est pas fourni → **Création** d'un nouveau MediaObject

### Étape 2 : Utiliser l'IRI dans les autres endpoints

Une fois l'image uploadée, utilisez l'IRI retourné (`/api/media_objects/123`) dans vos requêtes de création/mise à jour.

## Exemples par entité

### 1. Category (Catégorie)

**Uploader l'image :**
```bash
curl -X POST "/api/media_objects" \
  -F "file=@category.jpg" \
  -F "mapping=category_images"
# Réponse: { "@id": "/api/media_objects/123" }
```

**Créer/Mettre à jour la catégorie :**
```bash
curl -X PUT "/api/admin/categories/1" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Médicaments",
    "image": "/api/media_objects/123"
  }'
```

**Pour l'icône SVG :**
```bash
# Upload
curl -X POST "/api/media_objects" \
  -F "file=@icon.svg" \
  -F "mapping=category_icons"
# Réponse: { "@id": "/api/media_objects/124" }

# Utilisation
curl -X PUT "/api/admin/categories/1" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Médicaments",
    "icon": "/api/media_objects/124"
  }'
```

### 2. Product (Produit)

**Uploader les images (plusieurs images possibles) :**
```bash
# Image 1
curl -X POST "/api/media_objects" \
  -F "file=@product1.jpg" \
  -F "mapping=product_images"
# Réponse: { "@id": "/api/media_objects/125" }

# Image 2
curl -X POST "/api/media_objects" \
  -F "file=@product2.jpg" \
  -F "mapping=product_images"
# Réponse: { "@id": "/api/media_objects/126" }
```

**Créer/Mettre à jour le produit :**
```bash
curl -X PUT "/api/admin/products/1" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Paracétamol 500mg",
    "images": [
      "/api/media_objects/125",
      "/api/media_objects/126"
    ]
  }'
```

### 3. Brand (Marque)

**Uploader l'image :**
```bash
curl -X POST "/api/media_objects" \
  -F "file=@brand-logo.jpg" \
  -F "mapping=media_object"
# Réponse: { "@id": "/api/media_objects/127" }
```

**Créer/Mettre à jour la marque :**
```bash
curl -X PUT "/api/admin/brands/1" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Sanofi",
    "image": "/api/media_objects/127"
  }'
```

### 4. Manufacturer (Fabricant)

**Uploader l'image :**
```bash
curl -X POST "/api/media_objects" \
  -F "file=@manufacturer-logo.jpg" \
  -F "mapping=media_object"
# Réponse: { "@id": "/api/media_objects/128" }
```

**Créer/Mettre à jour le fabricant :**
```bash
curl -X PUT "/api/admin/manufacturers/1" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Pfizer",
    "image": "/api/media_objects/128"
  }'
```

### 5. Store (Magasin)

**Uploader l'image :**
```bash
curl -X POST "/api/media_objects" \
  -F "file=@store-photo.jpg" \
  -F "mapping=media_object"
# Réponse: { "@id": "/api/media_objects/129" }
```

**Créer/Mettre à jour le magasin :**
```bash
curl -X PUT "/api/admin/stores/1" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Pharmacie Centrale",
    "image": "/api/media_objects/129"
  }'
```

## Mappings disponibles

Le paramètre `mapping` détermine où le fichier sera stocké :

| Mapping | Dossier de stockage | Usage |
|---------|---------------------|-------|
| `media_object` | `/public/media/` | Par défaut (Brand, Manufacturer, Store) |
| `category_images` | `/public/images/categories/` | Images de catégories |
| `category_icons` | `/public/icons/categories/` | Icônes SVG de catégories |
| `product_images` | `/public/images/products/` | Images de produits |

## Cas d'usage : Mettre à jour une image existante

### Scénario : Remplacer l'image d'une catégorie

**Option 1 : Mettre à jour le MediaObject existant**
```javascript
// 1. Mettre à jour l'image existante (ID 123)
const formData = new FormData();
formData.append('id', 123); // ID du MediaObject existant
formData.append('file', newFile);
formData.append('mapping', 'category_images');

await fetch('/api/media_objects', {
  method: 'POST',
  body: formData
});

// 2. Pas besoin de mettre à jour la catégorie, l'IRI reste le même
// L'image est déjà liée à la catégorie
```

**Option 2 : Créer un nouveau MediaObject et remplacer**
```javascript
// 1. Créer un nouveau MediaObject
const formData = new FormData();
formData.append('file', newFile);
formData.append('mapping', 'category_images');

const response = await fetch('/api/media_objects', {
  method: 'POST',
  body: formData
});

const newMediaObject = await response.json();
const newImageIri = newMediaObject['@id']; // "/api/media_objects/456"

// 2. Mettre à jour la catégorie avec le nouvel IRI
await fetch('/api/admin/categories/1', {
  method: 'PUT',
  headers: {
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    name: 'Médicaments',
    image: newImageIri
  })
});

// L'ancienne image (ID 123) sera automatiquement supprimée
```

## Workflow complet : Exemple JavaScript

```javascript
async function uploadCategoryImage(categoryId, imageFile) {
  // Étape 1: Uploader l'image
  const formData = new FormData();
  formData.append('file', imageFile);
  formData.append('mapping', 'category_images');
  
  const uploadResponse = await fetch('/api/media_objects', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`
    },
    body: formData
  });
  
  if (!uploadResponse.ok) {
    throw new Error('Échec de l\'upload de l\'image');
  }
  
  const mediaObject = await uploadResponse.json();
  const imageIri = mediaObject['@id'];
  
  // Étape 2: Mettre à jour la catégorie avec l'IRI
  const updateResponse = await fetch(`/api/admin/categories/${categoryId}`, {
    method: 'PUT',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${token}`
    },
    body: JSON.stringify({
      image: imageIri
    })
  });
  
  if (!updateResponse.ok) {
    throw new Error('Échec de la mise à jour de la catégorie');
  }
  
  return await updateResponse.json();
}

// Utilisation
const fileInput = document.querySelector('input[type="file"]');
await uploadCategoryImage(1, fileInput.files[0]);
```

## Résumé

1. **Toujours uploader d'abord** via `POST /api/media_objects`
2. **Utiliser le champ `id`** pour mettre à jour un MediaObject existant au lieu d'en créer un nouveau
3. **Récupérer l'IRI** (`@id`) de la réponse
4. **Utiliser l'IRI** dans vos requêtes PUT/PATCH pour les autres entités
5. **Les anciennes images** sont automatiquement supprimées lors du remplacement

## Endpoints concernés

- ✅ **POST** `/api/media_objects` - Upload/Mise à jour d'images
- ✅ **PUT/PATCH** `/api/admin/categories/{id}` - Utilise `image` et `icon`
- ✅ **PUT/PATCH** `/api/admin/products/{id}` - Utilise `images` (tableau)
- ✅ **PUT/PATCH** `/api/admin/brands/{id}` - Utilise `image`
- ✅ **PUT/PATCH** `/api/admin/manufacturers/{id}` - Utilise `image`
- ✅ **PUT/PATCH** `/api/admin/stores/{id}` - Utilise `image`

