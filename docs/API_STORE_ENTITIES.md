# Documentation API : Gestion des Entités Store (Profil, Paramètres, Produits, Commandes)

## Vue d'ensemble

Cette documentation explique comment gérer le profil du magasin, les paramètres, les produits en stock et les commandes via l'API Store, destinée aux utilisateurs ayant le rôle `ROLE_STORE`.

## Authentification

Tous les endpoints store nécessitent une authentification avec le rôle `ROLE_STORE`. Utilisez un token JWT dans l'en-tête `Authorization` :

```http
Authorization: Bearer VOTRE_TOKEN_JWT
```

## Format des relations (Important)

**Toutes les relations doivent être envoyées comme des IRIs (chaînes), pas comme des IDs entiers.**

- ✅ **Correct** : `"product": "/api/products/1"` ou `"image": "/api/media_objects/123"`
- ❌ **Incorrect** : `"product": 1` ou `"image": 123`

**⚠️ Content-Type requis :** Lorsque vous utilisez des IRIs pour les relations, vous **DEVEZ** utiliser le Content-Type `application/ld+json`.

---

## 🏪 Profil du Magasin (Store Profile)

### Endpoints disponibles

- **GET** `/api/store` - Récupère le profil du magasin de l'utilisateur connecté
- **PUT** `/api/store/update` - Mise à jour complète du profil
- **PATCH** `/api/store/update` - Mise à jour partielle du profil

### Exemples d'utilisation

```bash
# Récupérer mon profil de magasin
curl -X GET "https://votre-api.com/api/store" \
  -H "Authorization: Bearer VOTRE_TOKEN"

# Mettre à jour partiellement le nom et l'image
curl -X PATCH "https://votre-api.com/api/store/update" \
  -H "Authorization: Bearer VOTRE_TOKEN" \
  -H "Content-Type: application/ld+json" \
  -d '{
    "name": "Nouveau Nom de Pharmacie",
    "image": "/api/media_objects/456"
  }'
```

### Structure des données

| Champ | Type | Description |
|-------|------|-------------|
| `name` | string | Nom du magasin |
| `description` | string | Description du magasin |
| `image` | string | IRI de l'image (ex: `"/api/media_objects/123"`) |
| `contact` | object | Objet ContactInfo (`phone`, `email`) |
| `location` | object | Objet Location (`address`, `latitude`, `longitude`, `city`) |

---

## ⚙️ Paramètres du Magasin (Store Settings)

### Endpoints disponibles

- **GET** `/api/store/settings` - Récupère les paramètres (horaires, etc.)
- **PUT** `/api/store/settings` - Mise à jour complète
- **PATCH** `/api/store/settings` - Mise à jour partielle

### Exemples d'utilisation

```bash
# Récupérer les paramètres
curl -X GET "https://votre-api.com/api/store/settings" \
  -H "Authorization: Bearer VOTRE_TOKEN"

# Mettre à jour les horaires
curl -X PATCH "https://votre-api.com/api/store/settings/789" \
  -H "Authorization: Bearer VOTRE_TOKEN" \
  -H "Content-Type: application/ld+json" \
  -d '{
    "mondayHours": {
      "openTime": "07:30",
      "closeTime": "18:30",
      "isClosed": false
    }
  }'
```

---

## 📦 Produits du Magasin (Store Products)

### Endpoints disponibles

- **GET** `/api/store/products` - Liste les produits du magasin
- **GET** `/api/store/products/{id}` - Détail d'un produit du magasin
- **POST** `/api/store/products` - Ajoute un produit au stock (le magasin est auto-assigné)
- **PUT** `/api/store/products/{id}` - Mise à jour complète (prix, stock)
- **PATCH** `/api/store/products/{id}` - Mise à jour partielle

### Exemples d'utilisation

```bash
# Lister mes produits en stock
curl -X GET "https://votre-api.com/api/store/products" \
  -H "Authorization: Bearer VOTRE_TOKEN"

# Ajouter un nouveau produit au stock (le magasin est auto-assigné à l'utilisateur connecté)
curl -X POST "https://votre-api.com/api/store/products" \
  -H "Authorization: Bearer VOTRE_TOKEN" \
  -H "Content-Type: application/ld+json" \
  -d '{
    "product": "/api/products/1",
    "price": 15000,
    "stock": 50,
    "unitPrice": 300
  }'

# Mettre à jour le stock ou le prix d'un produit existant
curl -X PATCH "https://votre-api.com/api/store/products/12" \
  -H "Authorization: Bearer VOTRE_TOKEN" \
  -H "Content-Type: application/ld+json" \
  -d '{
    "stock": 100,
    "price": 14500
  }'
```

---

## 📋 Commandes (Orders)

### Endpoints disponibles

- **GET** `/api/orders` - Liste toutes les commandes
- **PUT** `/api/store/orders/{id}` - Mise à jour du statut des articles par le magasin

### Exemples d'utilisation

```bash
# Lister les commandes
curl -X GET "https://votre-api.com/api/orders" \
  -H "Authorization: Bearer VOTRE_TOKEN"

# Accepter/Refuser des articles dans une commande
curl -X PUT "https://votre-api.com/api/store/orders/1" \
  -H "Authorization: Bearer VOTRE_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "items": [
      { "orderItemId": 123, "action": "accept" },
      { "orderItemId": 124, "action": "refuse" }
    ]
  }'
```

---

## 📊 Statistiques (Statistics)

```bash
# Récupérer les statistiques du magasin
curl -X GET "https://votre-api.com/api/store/statistics" \
  -H "Authorization: Bearer VOTRE_TOKEN"
```
