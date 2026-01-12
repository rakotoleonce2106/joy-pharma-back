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

## 📦 Produits (Products)

### Endpoints disponibles

- **GET** `/api/products` - Liste tous les produits (paginée)
- **GET** `/api/products/{id}` - Détail d'un produit par son ID
- **GET** `/api/products/search` - Recherche avancée de produits (via Elasticsearch)
- **GET** `/api/products-suggestion` - Suggestions de produits pour la page d'accueil

### Liste des produits (Filtrage par catégorie)

```bash
curl -X GET "https://votre-api.com/api/products?category=5&page=1&perPage=20"
```

### Détail d'un produit

```bash
curl -X GET "https://votre-api.com/api/products/12"
```

**Réponse :**

```json
{
  "@context": "/api/contexts/Product",
  "@id": "/api/products/12",
  "@type": "Product",
  "id": 12,
  "name": "Doliprane 1000mg",
  "code": "DOL1000",
  "description": "Médicament utilisé pour le traitement symptomatique des douleurs...",
  "images": [
    {
      "@id": "/api/media_objects/45",
      "contentUrl": "/media/products/doliprane.jpg"
    }
  ],
  "form": {
    "@id": "/api/forms/2",
    "name": "Comprimé"
  },
  "unit": {
    "@id": "/api/units/1",
    "name": "Boîte"
  },
  "unitPrice": 3500,
  "totalPrice": 3500,
  "isActive": true,
  "stock": 150
}
```

### Recherche de produits

```bash
# Recherche par nom ou mot-clé
curl -X GET "https://votre-api.com/api/products/search?q=aspirine"

# Recherche avec filtres combinés
curl -X GET "https://votre-api.com/api/products/search?q=paracetamol&category=3&brand=10&page=1"
```

---

## 📂 Catégories (Categories)

### Endpoints disponibles

- **GET** `/api/categories` - Liste toutes les catégories
- **GET** `/api/categories/{id}` - Détail d'une catégorie par son ID

### Filtrer les catégories parentes/enfants

L'API permet de naviguer dans l'arborescence des catégories :

```bash
# Récupérer uniquement les catégories racines (sans parent)
curl -X GET "https://votre-api.com/api/categories?parent=null"

# Récupérer les sous-catégories d'une catégorie spécifique
curl -X GET "https://votre-api.com/api/categories?parent=5"
```

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
- **GET** `/api/orders/{id}` - Détail d'une commande
- **POST** `/api/orders` - Crée une nouvelle commande
- **POST** `/api/orders/simulate` - Simule une commande (calcul des remises, etc.)

### Créer une commande

Envoyez les détails de la commande avec les IRIs des produits.

```bash
curl -X POST "https://votre-api.com/api/orders" \
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

## 💶 Devises (Currencies)

### Endpoints disponibles

- **GET** `/api/currencies` - Liste toutes les devises disponibles
- **GET** `/api/currencies/{id}` - Récupère une devise par son ID

### Liste des devises

```bash
curl -X GET "https://votre-api.com/api/currencies"
```

**Réponse :**

```json
[
  {
    "@id": "/api/currencies/1",
    "@type": "Currency",
    "id": 1,
    "isoCode": "MGA",
    "label": "Ariary",
    "symbol": "Ar"
  },
  {
    "@id": "/api/currencies/2",
    "@type": "Currency",
    "id": 2,
    "isoCode": "EUR",
    "label": "Euro",
    "symbol": "€"
  }
]
```

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


---

## 💊 Prescriptions (Prescriptions)

### Vue d'ensemble

**Note importante :** La gestion des prescriptions médicales est actuellement réservée aux administrateurs pour des raisons de conformité médicale et de sécurité. Les utilisateurs finaux peuvent soumettre des demandes de prescription via d'autres moyens (application mobile, support client).

### Upload de prescription (Administrateur uniquement)

Si vous êtes un administrateur, vous pouvez utiliser l'endpoint suivant pour traiter automatiquement une image d'ordonnance :

```bash
curl -X POST "https://votre-api.com/api/prescriptions/upload" \
  -H "Authorization: Bearer VOTRE_TOKEN_ADMIN" \
  -H "Accept: application/ld+json" \
  -F "file=@ordonnance.jpg"
```

**Formats d'image acceptés :** JPEG, PNG, GIF, WebP

**Taille maximale :** 10MB

**Fonctionnalités automatiques :**
- Extraction automatique des données (patient, médicaments, montant)
- Recherche des produits correspondants dans le catalogue
- Création d'une entité Prescription avec association des produits trouvés

**Réponse JSON-LD :**

```json
{
  "@context": "/api/contexts/Prescription",
  "@id": "/api/prescriptions/123",
  "@type": "Prescription",
  "id": 123,
  "title": "Ordonnance - Patient Dupont - 15/01/2026",
  "notes": "Patient: Dupont Jean\nDate: 15/01/2026\nTotal: 45000 Ar\nProduits recherchés: 3\nProduits trouvés: 2\nNoms extraits: Aspirine, Doliprane, Ibuprofene",
  "user": "/api/users/456",
  "prescriptionFile": "/api/media_objects/789",
  "products": [
    {
      "@id": "/api/products/101",
      "@type": "Product",
      "id": 101,
      "name": "Aspirine 500mg",
      "code": "ASP500",
      "price": 2500
    },
    {
      "@id": "/api/products/202",
      "@type": "Product",
      "id": 202,
      "name": "Doliprane 1000mg",
      "code": "DOL1000",
      "price": 3500
    }
  ]
}
```

### Gestion des prescriptions (Administrateur uniquement)

Une fois créée, une prescription peut être associée aux articles de commande pour tracer les médicaments prescrits médicalement.

#### Associer une prescription à une commande

Lors de la création d'une commande, vous pouvez lier un article à une prescription existante :

```bash
curl -X POST "https://votre-api.com/api/orders" \
  -H "Authorization: Bearer VOTRE_TOKEN" \
  -H "Content-Type: application/ld+json" \
  -d '{
    "items": [
      {
        "product": "/api/products/101",
        "quantity": 2,
        "prescription": "/api/admin/prescriptions/123"
      }
    ],
    "phone": "+261341234567",
    "paymentMethod": "cash"
  }'
```

### Sécurité et confidentialité

- **Accès restreint :** Seuls les administrateurs peuvent gérer les prescriptions
- **Données sensibles :** Les informations médicales sont strictement confidentielles
- **Traçabilité :** Toutes les actions sur les prescriptions sont enregistrées
- **Conformité :** Respect des réglementations médicales locales

### Support utilisateur

Pour toute question concernant les prescriptions médicales ou les ordonnances, contactez le support client ou utilisez l'application mobile dédiée.

---

**Note :** Cette section décrit les fonctionnalités disponibles. Les prescriptions étant un domaine médical sensible, leur gestion est déléguée aux professionnels de santé et administrateurs autorisés uniquement.
