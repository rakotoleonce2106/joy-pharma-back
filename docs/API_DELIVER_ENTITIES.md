# Documentation API : Gestion des Entités Livreur (Deliverer)

## Vue d'ensemble

Cette documentation explique comment gérer le profil, les statistiques, les factures et les actions en temps réel (localisation, SOS) pour les livreurs via l'API Deliverer.

## 🔐 Inscription (Register)

Pour devenir un livreur, vous devez vous inscrire via l'endpoint dédié. Cet endpoint accepte du `multipart/form-data` car il nécessite l'envoi de documents justificatifs.

- **POST** `/api/register/delivery`

### Exemple d'inscription

```bash
curl -X POST "https://votre-api.com/api/register/delivery" \
  -F "email=livreur@example.com" \
  -F "password=MotDePasseSecret123" \
  -F "firstName=Jean" \
  -F "lastName=Livreur" \
  -F "phone=+261340000000" \
  -F "vehicleType=motorcycle" \
  -F "vehiclePlate=1234 TAB" \
  -F "residenceDocument=@justificatif_domicile.pdf" \
  -F "vehicleDocument=@carte_grise.pdf"
```

**Paramètres requis :**
- `email`, `password`, `firstName`, `lastName`, `phone`
- `vehicleType` : un parmi `bike`, `motorcycle`, `car`, `van`
- `residenceDocument` : Fichier (PDF, Image)
- `vehicleDocument` : Fichier (PDF, Image)

**Réponse :**

```json
{
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "refresh_token": "def50200...",
  "user": {
    "id": 10,
    "email": "livreur@example.com",
    "firstName": "Jean",
    "lastName": "Livreur",
    "phone": "+261340000000",
    "roles": ["ROLE_DELIVER"],
    "userType": "delivery",
    "isActive": false,
    "delivery": {
      "vehicleType": "motorcycle",
      "vehiclePlate": "1234 TAB",
      "isOnline": false,
      "totalDeliveries": 0,
      "averageRating": 0,
      "totalEarnings": 0
    }
  }
}
```

> **Note :** Les comptes livreurs sont créés avec `isActive: false` par défaut et nécessitent une validation par l'administrateur avant de pouvoir se connecter.

## Authentification

Tous les endpoints livreur nécessitent une authentification avec le rôle `ROLE_DELIVER`. Utilisez un token JWT dans l'en-tête `Authorization` :

```http
Authorization: Bearer VOTRE_TOKEN_JWT
```

## Format des données (Important)

**⚠️ Content-Type requis :** Pour la création et la mise à jour, vous **DEVEZ** utiliser le Content-Type `application/ld+json`.

- ✅ **Correct** : `Content-Type: application/ld+json`
- ❌ **Incorrect** : `Content-Type: application/json`

---

## 👤 Profil du Livreur (Deliverer Profile)

### Endpoints disponibles

- **GET** `/api/deliver` - Récupère le profil du livreur connecté
- **PUT** `/api/deliver/update` - Met à jour complètement le profil
- **PATCH** `/api/deliver/update` - Met à jour partiellement le profil

### Structure des données

| Champ | Type | Requis | Description |
|-------|------|--------|-------------|
| `firstName` | string | ✅ Oui | Prénom |
| `lastName` | string | ✅ Oui | Nom |
| `phone` | string | ❌ Non | Numéro de téléphone |
| `image` | string (IRI) | ❌ Non | IRI de l'avatar (ex: `"/api/media_objects/123"`) |
| `plainPassword` | string | ❌ Non | Nouveau mot de passe |

### Exemples

```bash
# Récupérer mon profil
curl -X GET "https://votre-api.com/api/deliver" \
  -H "Authorization: Bearer VOTRE_TOKEN"

# Mettre à jour mon avatar (Partiel)
curl -X PATCH "https://votre-api.com/api/deliver/update" \
  -H "Authorization: Bearer VOTRE_TOKEN" \
  -H "Content-Type: application/ld+json" \
  -d '{
    "image": "/api/media_objects/456"
  }'
```

---

## 📊 Statistiques et Tableaux de Bord

### Dashboard
- **GET** `/api/deliver/statistics?period={today|week|month|year}`

```bash
curl -X GET "https://votre-api.com/api/deliver/statistics?period=today" \
  -H "Authorization: Bearer VOTRE_TOKEN"
```

### Gains (Earnings)
- **GET** `/api/deliver/earnings?period={week|month|year}`

```bash
curl -X GET "https://votre-api.com/api/deliver/earnings?period=week" \
  -H "Authorization: Bearer VOTRE_TOKEN"
```

---

## 📄 Factures (Invoices)

### Liste des factures
- **GET** `/api/deliver/invoices`

### Télécharger une facture (PDF)
- **GET** `/api/deliver/invoices/{id}/download`

```bash
curl -X GET "https://votre-api.com/api/deliver/invoices/123/download" \
  -H "Authorization: Bearer VOTRE_TOKEN"
```

---

## 📍 Localisation et Sécurité

### Mettre à jour la localisation
- **POST** `/api/deliver/location`

```bash
curl -X POST "https://votre-api.com/api/deliver/location" \
  -H "Authorization: Bearer VOTRE_TOKEN" \
  -H "Content-Type: application/ld+json" \
  -d '{
    "latitude": -18.8792,
    "longitude": 47.5079
  }'
```

### Envoyer un SOS d'urgence
- **POST** `/api/deliver/emergency/sos`

```bash
curl -X POST "https://votre-api.com/api/deliver/emergency/sos" \
  -H "Authorization: Bearer VOTRE_TOKEN" \
  -H "Content-Type: application/ld+json" \
  -d '{
    "reason": "Accident",
    "latitude": -18.8792,
    "longitude": 47.5079
  }'
```

---

## 🛠️ Support et Divers

### Contacter le support
- **POST** `/api/deliver/support/contact`

### Déconnexion
- **POST** `/api/deliver/logout`
