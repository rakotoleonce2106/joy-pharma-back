# Documentation API de Recherche

Cette documentation décrit les endpoints de recherche disponibles dans l'API Joy Pharma.

## Table des matières

1. [Recherche de produits (Elasticsearch)](#recherche-de-produits-elasticsearch)
2. [Suggestions de recherche](#suggestions-de-recherche)
3. [Filtres de recherche](#filtres-de-recherche)
4. [Exemples d'utilisation](#exemples-dutilisation)

---

## Recherche de produits (Elasticsearch)

### Endpoint

```
GET /api/products/search
```

### Description

Recherche avancée de produits utilisant Elasticsearch avec support de recherche full-text, filtres multiples et pagination.

### Paramètres de requête

| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| `q` ou `query` | string | Non | Terme de recherche (recherche full-text sur le nom, code, description) |
| `category` | integer | Non | Filtrer par ID de catégorie |
| `brand` | integer | Non | Filtrer par ID de marque |
| `manufacturer` | integer | Non | Filtrer par ID de fabricant |
| `isActive` | boolean | Non | Filtrer par statut actif (true/false) |
| `page` | integer | Non | Numéro de page (défaut: 1) |
| `perPage` ou `itemsPerPage` | integer | Non | Nombre d'éléments par page (défaut: 10, max: 50) |

### Exemples de requêtes

#### Recherche simple
```bash
GET /api/products/search?q=paracétamol
```

#### Recherche avec filtres
```bash
GET /api/products/search?q=aspirine&category=5&brand=2&isActive=true
```

#### Recherche avec pagination
```bash
GET /api/products/search?q=médicament&page=2&perPage=20
```

#### Recherche sans terme (tous les produits avec filtres)
```bash
GET /api/products/search?category=5&isActive=true
```

### Format de réponse

```json
{
  "@context": "/api/contexts/Product",
  "@id": "/api/products/search",
  "@type": "hydra:Collection",
  "hydra:member": [
    {
      "@id": "/api/products/123",
      "@type": "Product",
      "id": 123,
      "name": "Paracétamol 500mg",
      "code": "PARA500",
      "description": "Antalgique et antipyrétique",
      "unitPrice": 5.50,
      "totalPrice": 5.50,
      "stock": 150,
      "isActive": true,
      "images": [
        {
          "@id": "/api/media_objects/456",
          "contentUrl": "/images/products/paracetamol.jpg"
        }
      ],
      "brand": {
        "@id": "/api/brands/10",
        "id": 10,
        "name": "PharmaBrand"
      },
      "category": [
        {
          "@id": "/api/categories/5",
          "id": 5,
          "name": "Antalgiques"
        }
      ]
    }
  ],
  "hydra:totalItems": 1,
  "hydra:view": {
    "@id": "/api/products/search?page=1",
    "@type": "hydra:PartialCollectionView",
    "hydra:first": "/api/products/search?page=1",
    "hydra:last": "/api/products/search?page=1"
  }
}
```

---

## Suggestions de recherche

### Endpoint 1 : Suggestions simples

```
GET /api/products/search/suggestions
```

#### Description

Retourne des suggestions de titres de produits basées sur la requête de l'utilisateur. Utilise Elasticsearch avec recherche KNN similarity pour des résultats pertinents.

#### Paramètres

| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| `q` | string | Oui | Terme de recherche (minimum 1 caractère) |
| `limit` | integer | Non | Nombre de suggestions (défaut: 10, max: 20) |
| `metadata` | boolean | Non | Inclure les métadonnées de performance (défaut: false) |

#### Exemple de requête

```bash
GET /api/products/search/suggestions?q=para&limit=5
```

#### Format de réponse

```json
{
  "suggestions": [
    "Paracétamol 500mg",
    "Paracétamol 1000mg",
    "Paracétamol comprimés"
  ],
  "query": "para",
  "count": 3
}
```

#### Format de réponse avec métadonnées

```json
{
  "suggestions": [
    "Paracétamol 500mg",
    "Paracétamol 1000mg"
  ],
  "query": "para",
  "count": 2,
  "metadata": {
    "search_type": "knn_similarity",
    "elapsed_time_ms": 12.45,
    "limit": 5,
    "query_length": 4
  }
}
```

### Endpoint 2 : Suggestions détaillées

```
GET /api/products/search/suggestions/detailed
```

#### Description

Retourne des suggestions enrichies avec des informations complètes sur les produits (prix, stock, images, etc.). Idéal pour des autocomplétions avancées.

#### Paramètres

| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| `q` | string | Oui | Terme de recherche (minimum 1 caractère) |
| `limit` | integer | Non | Nombre de suggestions (défaut: 5, max: 10) |

#### Exemple de requête

```bash
GET /api/products/search/suggestions/detailed?q=aspirine&limit=3
```

#### Format de réponse

```json
{
  "suggestions": [
    {
      "@id": "/api/products/123",
      "@type": "Product",
      "id": 123,
      "name": "Aspirine 500mg",
      "code": "ASP500",
      "unitPrice": 3.50,
      "stock": 200,
      "isActive": true,
      "images": [
        {
          "@id": "/api/media_objects/456",
          "contentUrl": "/images/products/aspirine.jpg"
        }
      ]
    }
  ],
  "query": "aspirine",
  "count": 1,
  "metadata": {
    "search_type": "detailed_knn_similarity",
    "elapsed_time_ms": 15.23,
    "limit": 3
  }
}
```

---

## Filtres de recherche

### Recherche par nom (SearchFilter)

L'endpoint standard de produits supporte la recherche partielle sur le nom :

```
GET /api/products?name=paracétamol
```

Le filtre `name` utilise une recherche partielle insensible à la casse (`ipartial`).

### Recherche par catégorie (CategoryFilter)

Filtrer les produits par catégorie(s) :

```
GET /api/products?category[]=5&category[]=10
```

**Note** : Le filtre `category` utilise une logique AND - les produits doivent avoir TOUTES les catégories spécifiées.

### Recherche par catégorie parent (Category)

Filtrer les catégories par parent :

```
GET /api/category?parent=null
GET /api/category?parent=5
```

- `parent=null` : Retourne les catégories racines (sans parent)
- `parent=5` : Retourne les catégories enfants de la catégorie 5

### Recherche admin par catégorie parent

```
GET /api/admin/categories?parent=null
GET /api/admin/categories?parent=5
```

Même comportement que l'endpoint public, mais accessible uniquement aux administrateurs.

---

## Exemples d'utilisation

### Exemple 1 : Recherche avec autocomplétion

```javascript
// 1. Obtenir des suggestions pendant la saisie
const suggestions = await fetch('/api/products/search/suggestions?q=para&limit=5')
  .then(res => res.json());

// 2. Recherche complète quand l'utilisateur valide
const results = await fetch('/api/products/search?q=paracétamol&page=1&perPage=20')
  .then(res => res.json());
```

### Exemple 2 : Recherche avec filtres multiples

```javascript
const params = new URLSearchParams({
  q: 'médicament',
  category: '5',
  brand: '2',
  isActive: 'true',
  page: '1',
  perPage: '20'
});

const results = await fetch(`/api/products/search?${params}`)
  .then(res => res.json());
```

### Exemple 3 : Recherche avec cURL

```bash
# Recherche simple
curl -X GET "https://api.example.com/api/products/search?q=paracétamol"

# Recherche avec filtres
curl -X GET "https://api.example.com/api/products/search?q=aspirine&category=5&isActive=true"

# Suggestions
curl -X GET "https://api.example.com/api/products/search/suggestions?q=para&limit=5"

# Suggestions détaillées
curl -X GET "https://api.example.com/api/products/search/suggestions/detailed?q=aspirine&limit=3"
```

### Exemple 4 : Recherche de catégories racines

```javascript
// Obtenir toutes les catégories racines
const rootCategories = await fetch('/api/admin/categories?parent=null')
  .then(res => res.json());

// Obtenir les sous-catégories d'une catégorie
const subCategories = await fetch('/api/admin/categories?parent=5')
  .then(res => res.json());
```

---

## Notes techniques

### Elasticsearch

- L'index Elasticsearch est nommé `products`
- La recherche utilise des analyseurs ngram et edge_ngram pour une meilleure correspondance partielle
- Les suggestions utilisent une recherche KNN similarity pour des résultats pertinents

### Performance

- Les suggestions sont optimisées pour des temps de réponse rapides (< 50ms)
- La recherche complète supporte la pagination pour gérer de grands volumes de résultats
- Le nombre maximum d'éléments par page est limité à 50 pour éviter les surcharges

### Filtres

- Les filtres peuvent être combinés (AND logic)
- Les valeurs booléennes (`isActive`) acceptent `true`, `false`, `1`, `0`
- Les valeurs null pour `parent` peuvent être passées comme chaîne `"null"` ou valeur null

---

## Codes de réponse HTTP

| Code | Description |
|------|-------------|
| 200 | Succès - Résultats retournés |
| 400 | Requête invalide - Paramètres incorrects |
| 401 | Non autorisé - Token d'authentification manquant ou invalide |
| 403 | Interdit - Permissions insuffisantes (pour endpoints admin) |
| 500 | Erreur serveur - Problème avec Elasticsearch ou base de données |

---

## Support

Pour toute question ou problème concernant l'API de recherche, veuillez consulter la documentation principale ou contacter l'équipe de développement.

