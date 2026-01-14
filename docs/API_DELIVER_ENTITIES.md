# Documentation API : Gestion des Entités Livreur (Deliverer)

## Vue d'ensemble

Cette documentation explique comment gérer le profil, les statistiques, les factures et les actions en temps réel (localisation, SOS) pour les livreurs via l'API Deliverer.

## 🔐 Authentification et Inscription

### Endpoints d'authentification

- **POST** `/api/auth` - Connexion (obtenir un token JWT)
- **POST** `/api/register/delivery` - Inscription d'un livreur
- **POST** `/api/verify-email` - Vérifier l'adresse email avec un code
- **POST** `/api/resend-verification` - Renvoyer l'email de vérification
- **POST** `/api/token/refresh` - Rafraîchir le token JWT

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
  "success": true,
  "message": "Inscription réussie. Un email de vérification a été envoyé à votre adresse email.",
  "user": {
    "id": 10,
    "email": "livreur@example.com",
    "firstName": "Jean",
    "lastName": "Livreur",
    "phone": "+261340000000",
    "isEmailVerified": false,
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
  },
  "requiresEmailVerification": true
}
```

> **Note :** Les comptes livreurs sont créés avec `isActive: false` par défaut et nécessitent une validation par l'administrateur avant de pouvoir se connecter. De plus, vous devez vérifier votre adresse email avant de pouvoir vous connecter.

### Vérification de l'adresse email

Après l'inscription, vous recevrez un email contenant un code de vérification. Utilisez ce code pour vérifier votre adresse email.

#### Vérifier l'email

```bash
curl -X POST "https://votre-api.com/api/verify-email" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "livreur@example.com",
    "code": "123456"
  }'
```

**Réponse (succès) :**

```json
{
  "success": true,
  "message": "Votre adresse email a été vérifiée avec succès. Vous pouvez maintenant vous connecter.",
  "email": "livreur@example.com"
}
```

#### Renvoyer l'email de vérification

```bash
curl -X POST "https://votre-api.com/api/resend-verification" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "livreur@example.com"
  }'
```

**Réponse :**

```json
{
  "success": true,
  "message": "Un nouvel email de vérification a été envoyé à votre adresse email.",
  "email": "livreur@example.com"
}
```

### Réinitialisation de mot de passe

Si vous oubliez votre mot de passe, vous pouvez le réinitialiser en utilisant les endpoints suivants :

#### Demander un code de réinitialisation

```bash
curl -X POST "https://votre-api.com/api/password/forgot" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "livreur@example.com"
  }'
```

#### Vérifier le code

```bash
curl -X POST "https://votre-api.com/api/password/verify-code" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "livreur@example.com",
    "code": "123456"
  }'
```

#### Réinitialiser le mot de passe

```bash
curl -X POST "https://votre-api.com/api/password/reset" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "livreur@example.com",
    "code": "123456",
    "password": "nouveauMotDePasse123"
  }'
```

> **Note :** Les emails de réinitialisation de mot de passe sont envoyés automatiquement via n8n avec des codes de sécurité à 6 chiffres valides 1 heure.

## Authentification

Tous les endpoints livreur nécessitent une authentification avec le rôle `ROLE_DELIVER`. Utilisez un token JWT dans l'en-tête `Authorization` :

```http
Authorization: Bearer VOTRE_TOKEN_JWT
```

> **⚠️ Important :** Avant de pouvoir vous connecter, vous devez :
> 1. Avoir vérifié votre adresse email (voir section Vérification de l'adresse email)
> 2. Avoir été activé par un administrateur (votre compte doit avoir `isActive: true`)

Si vous essayez de vous connecter sans avoir vérifié votre email, vous recevrez une erreur :

```json
{
  "code": 401,
  "status": "EMAIL_NOT_VERIFIED",
  "message": "Votre adresse email n'est pas vérifiée. Veuillez vérifier votre email avant de vous connecter."
}
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
