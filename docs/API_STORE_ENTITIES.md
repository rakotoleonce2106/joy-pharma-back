# Documentation API : Gestion des Entités Store (Profil, Paramètres, Produits, Commandes)

## Vue d'ensemble

Cette documentation explique comment gérer le profil du magasin, les paramètres, les produits en stock et les commandes via l'API Store, destinée aux utilisateurs ayant le rôle `ROLE_STORE`.

## � Référence Rapide des Endpoints

**Base URL:** `https://back.joy-pharma.com/api`

### Profil du Magasin
- `GET /store` - Récupérer le profil
- `PUT /store/update` - Mise à jour complète
- `PATCH /store/update` - Mise à jour partielle

### Paramètres du Magasin
- `GET /store/settings` - Récupérer les paramètres
- `PUT /store/settings` - Mise à jour complète
- `PATCH /store/settings/{id}` - Mise à jour partielle

### Produits du Magasin
- `GET /store/products` - Liste des produits
- `GET /store/products/{id}` - Détail d'un produit
- `POST /store/products` - Ajouter un produit
- `PUT /store/products/{id}` - Mise à jour complète
- `PATCH /store/products/{id}` - Mise à jour partielle

### Commandes
- `GET /orders` - Liste des commandes
- `PUT /store/orders/{id}` - Mettre à jour le statut des articles

### Statistiques
- `GET /store/statistics` - Tableau de bord et statistiques

---

## �🔐 Authentification et Sécurité

### Endpoints d'authentification

Tous les endpoints store nécessitent une authentification avec le rôle `ROLE_STORE`. Utilisez un token JWT dans l'en-tête `Authorization` :

```http
Authorization: Bearer VOTRE_TOKEN_JWT
```

### Vérification d'adresse email

Tous les comptes magasin doivent avoir une adresse email vérifiée avant de pouvoir se connecter.

#### Vérifier l'email

```bash
curl -X POST "https://votre-api.com/api/verify-email" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "magasin@example.com",
    "code": "123456"
  }'
```

#### Renvoyer l'email de vérification

```bash
curl -X POST "https://votre-api.com/api/resend-verification" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "magasin@example.com"
  }'
```

### Réinitialisation de mot de passe

#### Demander un code de réinitialisation

```bash
curl -X POST "https://votre-api.com/api/password/forgot" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "magasin@example.com"
  }'
```

#### Vérifier le code

```bash
curl -X POST "https://votre-api.com/api/password/verify-code" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "magasin@example.com",
    "code": "123456"
  }'
```

#### Réinitialiser le mot de passe

```bash
curl -X POST "https://votre-api.com/api/password/reset" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "magasin@example.com",
    "code": "123456",
    "password": "nouveauMotDePasse123"
  }'
```

> **Note :** Les emails de vérification et de réinitialisation de mot de passe sont envoyés automatiquement via n8n avec des templates professionnels.

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

### Endpoint disponible

- **GET** `/api/store/statistics` - Récupère les statistiques du tableau de bord

### Exemple d'utilisation

```bash
# Récupérer les statistiques du magasin
curl -X GET "https://votre-api.com/api/store/statistics" \
  -H "Authorization: Bearer VOTRE_TOKEN"
```

### Structure de la réponse

| Champ | Type | Description |
|-------|------|-------------|
| `pendingCount` | integer | Nombre de commandes en attente |
| `recentOrders` | array | Liste des 10 commandes les plus récentes |
| `recentOrdersCount` | integer | Nombre de commandes récentes retournées |
| `statistics` | object | Objet contenant les statistiques détaillées |

#### Objet `statistics`

| Champ | Type | Description |
|-------|------|-------------|
| `pendingOrdersCount` | integer | Nombre de commandes en attente |
| `todayOrdersCount` | integer | Nombre de commandes aujourd'hui |
| `lowStockCount` | integer | Nombre de produits en stock faible (≤ 10) |
| `todayEarnings` | float | Revenus du jour (en Ariary) |
| `weeklyEarnings` | float | Revenus de la semaine |
| `monthlyEarnings` | float | Revenus du mois |

#### Objet `recentOrders[]`

| Champ | Type | Description |
|-------|------|-------------|
| `id` | string | ID de la commande |
| `reference` | string | Référence unique de la commande |
| `status` | string | Statut de la commande |
| `totalAmount` | float | Montant total de la commande |
| `itemsCount` | integer | Nombre d'articles pour ce magasin |
| `scheduledDate` | string\|null | Date de livraison prévue |
| `location` | object\|null | Adresse de livraison |
| `owner` | object | Informations du client |

### Exemple de réponse

```json
{
  "pendingCount": 5,
  "recentOrdersCount": 10,
  "recentOrders": [
    {
      "id": "123",
      "reference": "ORD-2026-288236",
      "status": "pending",
      "totalAmount": 96500,
      "itemsCount": 3,
      "scheduledDate": "2026-01-23 14:30:00",
      "location": {
        "address": "123 Rue Example",
        "city": "Antananarivo",
        "latitude": -18.8792,
        "longitude": 47.5079
      },
      "owner": {
        "id": 42,
        "email": "client@example.com",
        "firstName": "Jean",
        "lastName": "Dupont"
      }
    }
  ],
  "statistics": {
    "pendingOrdersCount": 5,
    "todayOrdersCount": 12,
    "lowStockCount": 3,
    "todayEarnings": 450000,
    "weeklyEarnings": 2500000,
    "monthlyEarnings": 8750000
  }
}
```

### Notes importantes

- Les revenus (`earnings`) sont calculés uniquement pour les commandes **livrées** avec des articles **acceptés/approuvés**
- Le `lowStockCount` compte les produits avec un stock ≤ 10 unités
- Les `recentOrders` sont limitées aux 10 commandes les plus récentes
- Les montants sont en **Ariary (MGA)**
