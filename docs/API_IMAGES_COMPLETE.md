# Documentation complète : API Images et Entités

## Table des matières

1. [API MediaObject - Upload d'images](#api-mediaobject)
2. [Entités avec relations d'images](#entités-avec-images)
3. [Exemples d'utilisation par entité](#exemples-par-entité)
4. [Mappings disponibles](#mappings-disponibles)

---

## API MediaObject

### Endpoint principal

**POST** `/api/media_objects`

Endpoint unique pour uploader et gérer toutes les images de l'application.

### Caractéristiques

- **Méthode** : POST uniquement (limitation PHP pour multipart/form-data)
- **Content-Type** : `multipart/form-data`
- **Authentification** : Requise (JWT token)

### Paramètres

| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| `file` | File | ✅ Oui | Fichier image à uploader |
| `id` | Integer | ❌ Non | ID du MediaObject existant à mettre à jour |
| `mapping` | String | ❌ Non | Type de mapping (voir [Mappings disponibles](#mappings-disponibles)) |

### Créer une nouvelle image

```http
POST /api/media_objects
Content-Type: multipart/form-data
Authorization: Bearer {token}

file: [fichier binaire]
mapping: "category_images" (optionnel)
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

### Mettre à jour une image existante

```http
POST /api/media_objects
Content-Type: multipart/form-data
Authorization: Bearer {token}

id: 123
file: [nouveau fichier binaire]
mapping: "category_images" (optionnel)
```

**Comportement :**
- Si `id` est fourni **ET** le MediaObject existe → **Mise à jour** de l'image
- Si `id` est fourni **MAIS** le MediaObject n'existe pas → **Création** d'un nouveau MediaObject
- Si `id` n'est pas fourni → **Création** d'un nouveau MediaObject

### Exemples avec cURL

**Créer :**
```bash
curl -X POST "https://api.example.com/api/media_objects" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "file=@/path/to/image.jpg" \
  -F "mapping=category_images"
```

**Mettre à jour :**
```bash
curl -X POST "https://api.example.com/api/media_objects" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "id=123" \
  -F "file=@/path/to/new-image.jpg" \
  -F "mapping=category_images"
```

### Exemple JavaScript

```javascript
const formData = new FormData();
formData.append('file', fileInput.files[0]);
formData.append('mapping', 'category_images');

const response = await fetch('/api/media_objects', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`
  },
  body: formData
});

const mediaObject = await response.json();
const imageIri = mediaObject['@id']; // "/api/media_objects/123"
```

---

## Entités avec relations d'images

### 1. Category (Catégorie)

**Relations :**
- `image` : MediaObject (image principale)
- `svg` : MediaObject (icône SVG)

**Mapping recommandé :**
- `image` → `category_images`
- `svg` → `category_icons`

**Endpoints :**
- `GET /api/categories` - Liste des catégories
- `GET /api/categories/{id}` - Détails d'une catégorie
- `POST /api/admin/categories` - Créer (Admin)
- `PUT /api/admin/categories/{id}` - Mettre à jour (Admin)
- `DELETE /api/admin/categories/{id}` - Supprimer (Admin)

---

### 2. Product (Produit)

**Relations :**
- `images` : Collection<MediaObject> (plusieurs images)

**Mapping recommandé :**
- `images` → `product_images`

**Endpoints :**
- `GET /api/products` - Liste des produits
- `GET /api/products/{id}` - Détails d'un produit
- `GET /api/products/search` - Recherche de produits
- `POST /api/admin/products` - Créer (Admin)
- `PUT /api/admin/products/{id}` - Mettre à jour (Admin)
- `DELETE /api/admin/products/{id}` - Supprimer (Admin)

---

### 3. Brand (Marque)

**Relations :**
- `image` : MediaObject (logo)

**Mapping recommandé :**
- `image` → `brand_images`

**Endpoints :**
- `GET /api/brands` - Liste des marques
- `GET /api/brands/{id}` - Détails d'une marque
- `POST /api/admin/brands` - Créer (Admin)
- `PUT /api/admin/brands/{id}` - Mettre à jour (Admin)
- `DELETE /api/admin/brands/{id}` - Supprimer (Admin)

---

### 4. Manufacturer (Fabricant)

**Relations :**
- `image` : MediaObject (logo)

**Mapping recommandé :**
- `image` → `manufacturer_images`

**Endpoints :**
- `GET /api/admin/manufacturers` - Liste (Admin)
- `GET /api/admin/manufacturers/{id}` - Détails (Admin)
- `POST /api/admin/manufacturers` - Créer (Admin)
- `PUT /api/admin/manufacturers/{id}` - Mettre à jour (Admin)
- `DELETE /api/admin/manufacturers/{id}` - Supprimer (Admin)

---

### 5. Store (Magasin)

**Relations :**
- `image` : MediaObject (photo du magasin)

**Mapping recommandé :**
- `image` → `store_images`

**Endpoints :**
- `GET /api/admin/stores` - Liste (Admin)
- `GET /api/admin/stores/{id}` - Détails (Admin)
- `POST /api/admin/stores` - Créer (Admin)
- `PUT /api/admin/stores/{id}` - Mettre à jour (Admin)
- `DELETE /api/admin/stores/{id}` - Supprimer (Admin)

---

### 6. User (Utilisateur)

**Relations :**
- `image` : MediaObject (avatar)

**Mapping recommandé :**
- `image` → `user_images`

**Endpoints :**
- `GET /api/me` - Utilisateur actuel
- `PUT /api/user/update` - Mettre à jour le profil
- `GET /api/admin/users` - Liste (Admin)
- `GET /api/admin/users/{id}` - Détails (Admin)
- `POST /api/admin/users` - Créer (Admin)
- `PUT /api/admin/users/{id}` - Mettre à jour (Admin)
- `DELETE /api/admin/users/{id}` - Supprimer (Admin)

---

### 7. Delivery (Livraison)

**Relations :**
- `residenceDocument` : MediaObject (document de résidence)
- `vehicleDocument` : MediaObject (document du véhicule)

**Mapping recommandé :**
- `residenceDocument` → `media_object`
- `vehicleDocument` → `media_object`

**Note :** Les documents de livraison sont généralement gérés via l'inscription des livreurs.

---

## Exemples par entité

### Category

#### Étape 1 : Uploader l'image

```bash
curl -X POST "/api/media_objects" \
  -H "Authorization: Bearer TOKEN" \
  -F "file=@category.jpg" \
  -F "mapping=category_images"
```

**Réponse :**
```json
{
  "@id": "/api/media_objects/123",
  "contentUrl": "/images/categories/abc123.jpg",
  "id": 123
}
```

#### Étape 2 : Uploader l'icône SVG

```bash
curl -X POST "/api/media_objects" \
  -H "Authorization: Bearer TOKEN" \
  -F "file=@icon.svg" \
  -F "mapping=category_icons"
```

**Réponse :**
```json
{
  "@id": "/api/media_objects/124",
  "contentUrl": "/icons/categories/def456.svg",
  "id": 124
}
```

#### Étape 3 : Créer/Mettre à jour la catégorie

```bash
curl -X PUT "/api/admin/categories/1" \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Médicaments",
    "description": "Catégorie des médicaments",
    "image": "/api/media_objects/123",
    "icon": "/api/media_objects/124"
  }'
```

---

### Product

#### Étape 1 : Uploader les images (plusieurs)

```bash
# Image 1
curl -X POST "/api/media_objects" \
  -H "Authorization: Bearer TOKEN" \
  -F "file=@product1.jpg" \
  -F "mapping=product_images"
# Réponse: { "@id": "/api/media_objects/125" }

# Image 2
curl -X POST "/api/media_objects" \
  -H "Authorization: Bearer TOKEN" \
  -F "file=@product2.jpg" \
  -F "mapping=product_images"
# Réponse: { "@id": "/api/media_objects/126" }
```

#### Étape 2 : Créer/Mettre à jour le produit

```bash
curl -X PUT "/api/admin/products/1" \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Paracétamol 500mg",
    "code": "PARA500",
    "images": [
      "/api/media_objects/125",
      "/api/media_objects/126"
    ]
  }'
```

**Note :** Les images non incluses dans le tableau seront automatiquement supprimées.

---

### Brand

#### Étape 1 : Uploader le logo

```bash
curl -X POST "/api/media_objects" \
  -H "Authorization: Bearer TOKEN" \
  -F "file=@brand-logo.jpg" \
  -F "mapping=brand_images"
```

**Réponse :**
```json
{
  "@id": "/api/media_objects/127",
  "contentUrl": "/images/brands/ghi789.jpg",
  "id": 127
}
```

#### Étape 2 : Créer/Mettre à jour la marque

```bash
curl -X PUT "/api/admin/brands/1" \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Sanofi",
    "image": "/api/media_objects/127"
  }'
```

---

### Manufacturer

#### Étape 1 : Uploader le logo

```bash
curl -X POST "/api/media_objects" \
  -H "Authorization: Bearer TOKEN" \
  -F "file=@manufacturer-logo.jpg" \
  -F "mapping=manufacturer_images"
```

**Réponse :**
```json
{
  "@id": "/api/media_objects/128",
  "contentUrl": "/images/manufacturers/jkl012.jpg",
  "id": 128
}
```

#### Étape 2 : Créer/Mettre à jour le fabricant

```bash
curl -X PUT "/api/admin/manufacturers/1" \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Pfizer",
    "description": "Fabricant pharmaceutique",
    "image": "/api/media_objects/128"
  }'
```

---

### Store

#### Étape 1 : Uploader l'image

```bash
curl -X POST "/api/media_objects" \
  -H "Authorization: Bearer TOKEN" \
  -F "file=@store-photo.jpg" \
  -F "mapping=store_images"
```

**Réponse :**
```json
{
  "@id": "/api/media_objects/129",
  "contentUrl": "/images/stores/mno345.jpg",
  "id": 129
}
```

#### Étape 2 : Créer/Mettre à jour le magasin

```bash
curl -X PUT "/api/admin/stores/1" \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Pharmacie Centrale",
    "description": "Pharmacie principale",
    "image": "/api/media_objects/129"
  }'
```

---

### User

#### Étape 1 : Uploader l'avatar

```bash
curl -X POST "/api/media_objects" \
  -H "Authorization: Bearer TOKEN" \
  -F "file=@avatar.jpg" \
  -F "mapping=user_images"
```

**Réponse :**
```json
{
  "@id": "/api/media_objects/130",
  "contentUrl": "/images/users/pqr678.jpg",
  "id": 130
}
```

#### Étape 2 : Mettre à jour le profil utilisateur

```bash
curl -X PUT "/api/user/update" \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "firstName": "John",
    "lastName": "Doe",
    "image": "/api/media_objects/130"
  }'
```

---

## Mappings disponibles

Le paramètre `mapping` détermine où le fichier sera stocké et organisé :

| Mapping | Dossier de stockage | Usage | Exemple |
|---------|---------------------|-------|---------|
| `media_object` | `/public/media/` | Par défaut, documents génériques | Documents de livraison |
| `category_images` | `/public/images/categories/` | Images de catégories | Photos de catégories |
| `category_icons` | `/public/icons/categories/` | Icônes SVG de catégories | Icônes de catégories |
| `product_images` | `/public/images/products/` | Images de produits | Photos de produits |
| `brand_images` | `/public/images/brands/` | Logos de marques | Logos de marques |
| `manufacturer_images` | `/public/images/manufacturers/` | Logos de fabricants | Logos de fabricants |
| `user_images` | `/public/images/users/` | Avatars d'utilisateurs | Photos de profil |
| `store_images` | `/public/images/stores/` | Photos de magasins | Photos de magasins |

### URLs générées

Les URLs sont automatiquement construites selon le mapping :

- `category_images` → `/images/categories/{filename}`
- `category_icons` → `/icons/categories/{filename}`
- `product_images` → `/images/products/{filename}`
- `brand_images` → `/images/brands/{filename}`
- `manufacturer_images` → `/images/manufacturers/{filename}`
- `user_images` → `/images/users/{filename}`
- `store_images` → `/images/stores/{filename}`
- `media_object` → `/media/{filename}`

