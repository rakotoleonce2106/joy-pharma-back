# Documentation API : Gestion des Entités Client (Profil, Favoris, Commandes)

## Vue d'ensemble

Cette documentation explique comment gérer l'authentification, l'inscription, le profil utilisateur, les favoris et les commandes via l'API Client, destinée aux utilisateurs finaux (patients/clients).

## 🔐 Authentification et Inscription

### Endpoints d'authentification

- **POST** `/api/auth` - Connexion (obtenir un token JWT)
- **POST** `/api/register` - Inscription d'un nouvel utilisateur
- **POST** `/api/token/refresh` - Rafraîchir le token JWT

### Connexion (Login)

Pour vous connecter et obtenir un token JWT :

```bash
curl -X POST "https://votre-api.com/api/auth" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "utilisateur@example.com",
    "password": "votreMotDePasse"
  }'
```

**Réponse :**

```json
{
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "refresh_token": "def50200...",
  "user": {
    "id": 1,
    "email": "utilisateur@example.com",
    "firstName": "Jean",
    "lastName": "Dupont",
    "roles": ["ROLE_USER"]
  }
}
```

### Inscription (Register)

Pour créer un nouveau compte utilisateur :

```bash
curl -X POST "https://votre-api.com/api/register" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "nouveau@example.com",
    "password": "motDePasse123",
    "firstName": "Jean",
    "lastName": "Dupont",
    "phone": "+261341234567"
  }'
```

**Champs requis :**
- `email` (unique)
- `password` (minimum 8 caractères)
- `firstName`
- `lastName`

**Champs optionnels :**
- `phone`
- `image` (IRI d'un media_object : `/api/media_objects/123`)

**Réponse :**

```json
{
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "user": {
    "id": 2,
    "email": "nouveau@example.com",
    "firstName": "Jean",
    "lastName": "Dupont",
    "roles": ["ROLE_USER"]
  }
}
```

### Rafraîchir le token

Pour obtenir un nouveau token JWT sans se reconnecter :

```bash
curl -X POST "https://votre-api.com/api/token/refresh" \
  -H "Content-Type: application/json" \
  -d '{
    "refresh_token": "def50200..."
  }'
```

## Utilisation du Token JWT

Tous les endpoints protégés nécessitent un token JWT dans l'en-tête `Authorization` :

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

- **GET** `/api/me` - Récupère le profil de l'utilisateur connecté
- **PUT** `/api/user/update` - Mise à jour complète du profil
- **PATCH** `/api/user/update` - Mise à jour partielle du profil
- **POST** `/api/user/update-password` - Modification du mot de passe

### Récupérer mon profil

Récupère toutes les informations de l'utilisateur connecté :

```bash
curl -X GET "https://votre-api.com/api/me" \
  -H "Authorization: Bearer VOTRE_TOKEN"
```

**Réponse :**

```json
{
  "@context": "/api/contexts/User",
  "@id": "/api/users/1",
  "@type": "User",
  "id": 1,
  "email": "utilisateur@example.com",
  "firstName": "Jean",
  "lastName": "Dupont",
  "phone": "+261341234567",
  "image": {
    "@id": "/api/media_objects/123",
    "contentUrl": "/media/images/avatar.jpg"
  },
  "roles": ["ROLE_USER"],
  "createdAt": "2025-01-01T10:00:00+00:00"
}
```

### Mise à jour partielle (PATCH)

Permet de modifier uniquement les champs envoyés. Idéal pour mettre à jour un ou plusieurs champs sans toucher aux autres.

```bash
# Mettre à jour le nom et le téléphone
curl -X PATCH "https://votre-api.com/api/user/update" \
  -H "Authorization: Bearer VOTRE_TOKEN" \
  -H "Content-Type: application/ld+json" \
  -d '{
    "firstName": "Jean",
    "lastName": "Dupont",
    "phone": "+261341234567"
  }'
```

### Mise à jour complète (PUT)

Remplace toutes les données du profil. Tous les champs doivent être envoyés.

```bash
curl -X PUT "https://votre-api.com/api/user/update" \
  -H "Authorization: Bearer VOTRE_TOKEN" \
  -H "Content-Type: application/ld+json" \
  -d '{
    "email": "utilisateur@example.com",
    "firstName": "Jean",
    "lastName": "Dupont",
    "phone": "+261341234567",
    "image": "/api/media_objects/123"
  }'
```

### Changer l'avatar

Pour modifier l'image de profil, utilisez l'IRI d'un `media_object` :

```bash
curl -X PATCH "https://votre-api.com/api/user/update" \
  -H "Authorization: Bearer VOTRE_TOKEN" \
  -H "Content-Type: application/ld+json" \
  -d '{
    "image": "/api/media_objects/456"
  }'
```

**Note :** Pour uploader une image, utilisez d'abord l'endpoint `/api/media_objects` (POST multipart/form-data).

### Changer le mot de passe

```bash
curl -X POST "https://votre-api.com/api/user/update-password" \
  -H "Authorization: Bearer VOTRE_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "currentPassword": "ancienMotDePasse",
    "newPassword": "nouveauMotDePasse123",
    "confirmPassword": "nouveauMotDePasse123"
  }'
```

**Champs requis :**
- `currentPassword` : Le mot de passe actuel (pour vérification)
- `newPassword` : Le nouveau mot de passe (minimum 8 caractères)
- `confirmPassword` : Confirmation du nouveau mot de passe (doit correspondre)

### Champs modifiables

| Champ | Type | Description | Requis |
|-------|------|-------------|---------|
| `email` | string | Adresse email (unique) | Oui (PUT) |
| `firstName` | string | Prénom | Oui (PUT) |
| `lastName` | string | Nom de famille | Oui (PUT) |
| `phone` | string | Numéro de téléphone | Non |
| `image` | IRI | Avatar (ex: `/api/media_objects/123`) | Non |

---

## ❤️ Favoris (Favorites)

### Endpoints disponibles

- **GET** `/api/favorites` - Liste mes produits favoris
- **POST** `/api/favorites` - Ajoute un produit aux favoris
- **DELETE** `/api/favorites/{id}` - Supprime un produit des favoris

### Ajouter un favori

Pour ajouter un favori, envoyez simplement l'IRI du produit. L'utilisateur est automatiquement assigné.

```bash
curl -X POST "https://votre-api.com/api/favorites" \
  -H "Authorization: Bearer VOTRE_TOKEN" \
  -H "Content-Type: application/ld+json" \
  -d '{
    "product": "/api/products/45"
  }'
```

---

## 📋 Commandes (Orders)

### Endpoints disponibles

- **GET** `/api/orders` - Liste l'historique de mes commandes
- **GET** `/api/order/{id}` - Détail d'une commande
- **POST** `/api/order` - Crée une nouvelle commande
- **POST** `/api/order/simulate` - Simule une commande (calcul des remises, etc.)

### Créer une commande

Envoyez les détails de la commande avec les IRIs des produits.

```bash
curl -X POST "https://votre-api.com/api/order" \
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
