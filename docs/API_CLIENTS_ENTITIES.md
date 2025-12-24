# Documentation API : Gestion des Entités Client (Profil, Favoris, Commandes)

## Vue d'ensemble

Cette documentation explique comment gérer le profil utilisateur, les favoris et les commandes via l'API Client, destinée aux utilisateurs finaux (patients/clients).

## Authentification

Tous ces endpoints nécessitent une authentification. Utilisez un token JWT dans l'en-tête `Authorization` :

```http
Authorization: Bearer VOTRE_TOKEN_JWT
```

## Format des données (LD+JSON)

**L'API utilise le format JSON-LD (`application/ld+json`). Toutes les relations doivent être envoyées comme des IRIs (chaînes), pas comme des IDs.**

- ✅ **Correct** : `"product": "/api/products/1"`
- ❌ **Incorrect** : `"product": 1`

**⚠️ Header requis :** Siempre utiliser `Content-Type: application/ld+json`.

---

## 👤 Profil Utilisateur (User Profile)

### Endpoints disponibles

- **GET** `/me` - Récupère le profil de l'utilisateur connecté
- **PUT** `/user/update` - Mise à jour complète du profil
- **PATCH** `/user/update` - Mise à jour partielle du profil

### Exemples d'utilisation

```bash
# Récupérer mon profil
curl -X GET "https://votre-api.com/me" \
  -H "Authorization: Bearer VOTRE_TOKEN"

# Mettre à jour mon nom et mon avatar
curl -X PATCH "https://votre-api.com/user/update" \
  -H "Authorization: Bearer VOTRE_TOKEN" \
  -H "Content-Type: application/ld+json" \
  -d '{
    "firstName": "Jean",
    "lastName": "Dupont",
    "image": "/api/media_objects/123"
  }'
```

---

## ❤️ Favoris (Favorites)

### Endpoints disponibles

- **GET** `/favorites` - Liste mes produits favoris
- **POST** `/favorites` - Ajoute un produit aux favoris
- **DELETE** `/favorites/{id}` - Supprime un produit des favoris

### Ajouter un favori

Pour ajouter un favori, envoyez simplement l'IRI du produit. L'utilisateur est automatiquement assigné.

```bash
curl -X POST "https://votre-api.com/favorites" \
  -H "Authorization: Bearer VOTRE_TOKEN" \
  -H "Content-Type: application/ld+json" \
  -d '{
    "product": "/api/products/45"
  }'
```

---

## 📋 Commandes (Orders)

### Endpoints disponibles

- **GET** `/orders` - Liste l'historique de mes commandes
- **GET** `/order/{id}` - Détail d'une commande
- **POST** `/order` - Crée une nouvelle commande
- **POST** `/order/simulate` - Simule une commande (calcul des remises, etc.)

### Créer une commande

Envoyez les détails de la commande avec les IRIs des produits.

```bash
curl -X POST "https://votre-api.com/order" \
  -H "Authorization: Bearer VOTRE_TOKEN" \
  -H "Content-Type: application/ld+json" \
  -d '{
    "items": [
      {
        "product": "/api/products/1",
        "quantity": 2
      },
      {
        "product": "/api/products/10",
        "quantity": 1
      }
    ],
    "phone": "+261341234567",
    "notes": "Livrer à l'\''accueil",
    "priority": "standard",
    "paymentMethod": "cash",
    "location": {
      "address": "Antananarivo, Madagascar",
      "latitude": -18.8792,
      "longitude": 47.5079
    }
  }'
```

**Note :** Le champ `paymentMethod` accepte par exemple "cash" ou "mobile_money". Le champ `location` peut être un objet (création d'une nouvelle adresse) ou une IRI d'une adresse existante.

---

## 💳 Paiements (Payments)

### Endpoints disponibles

- **POST** `/api/create-payment-intent` - Crée une intention de paiement (Mvola / MPGS)
- **GET** `/api/verify-payment/{orderId}` - Vérifie le statut d'un paiement pour une commande
- **GET** `/api/payment/order/{orderId}` - Récupère les infos de paiement par ID commande

### Créer une intention de paiement

Envoyez le montant, la méthode et la référence de la commande (ou son IRI).

```bash
curl -X POST "https://votre-api.com/api/create-payment-intent" \
  -H "Authorization: Bearer VOTRE_TOKEN" \
  -H "Content-Type: application/ld+json" \
  -d '{
    "amount": 50000,
    "method": "mvola",
    "phoneNumber": "0340000000",
    "order": "/api/admin/orders/123"
  }'
```

**Note :** Le champ `order` peut être une IRI (`/api/admin/orders/123`) ou simplement la référence de la commande (`ORD-2025-ABCDEF`).

### Vérifier le statut d'un paiement

Cet endpoint retourne le détail du paiement au format JSON-LD.

```bash
curl -X GET "https://votre-api.com/api/verify-payment/ORD-2025-ABCDEF" \
  -H "Authorization: Bearer VOTRE_TOKEN"
```
