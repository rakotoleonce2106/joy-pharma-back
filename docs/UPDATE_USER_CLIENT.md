# Documentation - Mise à jour du profil utilisateur client

## Vue d'ensemble

Cette documentation décrit l'endpoint API pour mettre à jour le profil d'un utilisateur client authentifié. L'endpoint permet de modifier les informations personnelles de l'utilisateur et, pour les livreurs, de mettre à jour leurs informations de livraison.

---

## Endpoint

**URL:** `PUT /api/user/update`

**Méthode:** `PUT`

**Authentification:** Requise (`ROLE_USER`)

**Headers requis:**
```
Authorization: Bearer {JWT_TOKEN}
```

---

## Formats de requête supportés

L'endpoint accepte deux formats de requête :

1. **`multipart/form-data`** - Pour les mises à jour avec upload de fichier (image de profil)
2. **`application/json`** - Pour les mises à jour simples sans fichier

---

## Paramètres de requête

### Paramètres communs (tous les utilisateurs)

| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| `firstName` | string | Non | Prénom de l'utilisateur |
| `lastName` | string | Non | Nom de l'utilisateur |
| `phone` | string | Non | Numéro de téléphone |
| `imageFile` | file | Non | Image de profil (uniquement avec `multipart/form-data`) |

### Paramètres spécifiques aux livreurs (`ROLE_DELIVER`)

| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| `isOnline` | boolean | Non | Statut en ligne/hors ligne (uniquement avec `application/json`) |
| `vehicleType` | string | Non | Type de véhicule : `bike`, `motorcycle`, `car`, ou `van` |
| `vehiclePlate` | string | Non | Plaque d'immatriculation du véhicule |

**Note:** Les paramètres spécifiques aux livreurs ne sont traités que si l'utilisateur authentifié possède le rôle `ROLE_DELIVER`.

---

## Exemples de requêtes

### Exemple 1 : Mise à jour avec multipart/form-data (avec image)

```bash
curl -X PUT "https://api.example.com/api/user/update" \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..." \
  -F "firstName=Jean" \
  -F "lastName=Dupont" \
  -F "phone=+261340000000" \
  -F "imageFile=@/chemin/vers/avatar.jpg"
```

### Exemple 2 : Mise à jour avec multipart/form-data (livreur)

```bash
curl -X PUT "https://api.example.com/api/user/update" \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..." \
  -F "firstName=Alex" \
  -F "lastName=Livreur" \
  -F "phone=+261340000001" \
  -F "vehicleType=motorcycle" \
  -F "vehiclePlate=ABC-1234" \
  -F "imageFile=@/chemin/vers/avatar.jpg"
```

### Exemple 3 : Mise à jour avec application/json (client)

```bash
curl -X PUT "https://api.example.com/api/user/update" \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..." \
  -H "Content-Type: application/json" \
  -d '{
    "firstName": "Jean",
    "lastName": "Dupont",
    "phone": "+261340000000"
  }'
```

### Exemple 4 : Mise à jour avec application/json (livreur avec statut en ligne)

```bash
curl -X PUT "https://api.example.com/api/user/update" \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..." \
  -H "Content-Type: application/json" \
  -d '{
    "firstName": "Alex",
    "lastName": "Livreur",
    "phone": "+261340000001",
    "isOnline": true
  }'
```

### Exemple 5 : Mise à jour partielle (uniquement le téléphone)

```bash
curl -X PUT "https://api.example.com/api/user/update" \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..." \
  -H "Content-Type: application/json" \
  -d '{
    "phone": "+261340000002"
  }'
```

---

## Réponses

### Réponse de succès (200 OK)

#### Pour un client

```json
{
  "id": 123,
  "email": "client@example.com",
  "firstName": "Jean",
  "lastName": "Dupont",
  "phone": "+261340000000",
  "roles": ["ROLE_USER"],
  "userType": "customer",
  "isActive": true,
  "avatar": "/uploads/profile/avatar.jpg"
}
```

#### Pour un livreur

```json
{
  "id": 456,
  "email": "livreur@example.com",
  "firstName": "Alex",
  "lastName": "Livreur",
  "phone": "+261340000001",
  "roles": ["ROLE_DELIVER"],
  "userType": "delivery",
  "isActive": true,
  "avatar": "/uploads/profile/avatar.jpg",
  "delivery": {
    "vehicleType": "motorcycle",
    "vehiclePlate": "ABC-1234",
    "isOnline": true,
    "totalDeliveries": 45,
    "averageRating": 4.5,
    "totalEarnings": "125000.00",
    "currentLatitude": "-18.8792",
    "currentLongitude": "47.5079",
    "lastLocationUpdate": "2025-01-15T10:00:00+00:00"
  }
}
```

#### Pour un propriétaire de magasin

```json
{
  "id": 789,
  "email": "magasin@example.com",
  "firstName": "Marie",
  "lastName": "Proprietaire",
  "phone": "+261340000003",
  "roles": ["ROLE_USER", "ROLE_STORE"],
  "userType": "store",
  "isActive": true,
  "avatar": "/uploads/profile/avatar.jpg",
  "store": {
    "id": 3,
    "name": "Pharmacie ABC",
    "description": "Meilleure pharmacie de la ville",
    "phone": "+261340000004",
    "email": "pharmacie@example.com",
    "address": "123 Rue Principale",
    "city": "Antananarivo",
    "latitude": -18.8792,
    "longitude": 47.5079,
    "image": "/images/store/store.jpg",
    "isActive": true
  }
}
```

### Réponses d'erreur

#### 400 Bad Request - Erreur de validation

```json
{
  "type": "https://tools.ietf.org/html/rfc2616#section-10",
  "title": "An error occurred",
  "detail": "Validation failed",
  "violations": [
    {
      "propertyPath": "firstName",
      "message": "First name cannot be empty"
    }
  ]
}
```

#### 401 Unauthorized - Token manquant ou invalide

```json
{
  "code": 401,
  "message": "JWT Token not found"
}
```

ou

```json
{
  "code": 401,
  "message": "Invalid JWT Token"
}
```

#### 400 Bad Request - Utilisateur non authentifié

```json
{
  "code": 400,
  "message": "User not authenticated"
}
```

---

## Comportement et règles métier

### 1. Authentification

- L'utilisateur doit être authentifié avec un token JWT valide
- Seul l'utilisateur authentifié peut mettre à jour son propre profil
- L'endpoint met automatiquement à jour le profil de l'utilisateur authentifié (pas besoin de passer l'ID dans l'URL)

### 2. Mise à jour partielle

- Tous les paramètres sont optionnels
- Seuls les champs fournis seront mis à jour
- Les champs non fournis conserveront leurs valeurs actuelles

### 3. Gestion des fichiers

- **Format accepté:** `multipart/form-data` uniquement pour l'upload de fichiers
- **Paramètre:** `imageFile` pour l'image de profil
- Le fichier doit être valide et de type image
- Si un fichier est fourni, il remplacera l'image de profil existante

### 4. Livreurs (ROLE_DELIVER)

- Si l'utilisateur a le rôle `ROLE_DELIVER` et n'a pas encore d'entité `Delivery`, une nouvelle entité sera créée automatiquement
- Le paramètre `isOnline` n'est accepté qu'avec le format `application/json`
- Les paramètres `vehicleType` et `vehiclePlate` peuvent être mis à jour via `multipart/form-data` ou `application/json`

### 5. Validation

- Les données sont validées selon le groupe de validation `user:update`
- Les violations de validation sont retournées dans la réponse d'erreur avec le code 400

---

## Groupes de sérialisation

### Normalisation (réponse)
- `id:read`
- `user:read`
- `image:read`
- `media_object:read`
- `store:read` (pour les propriétaires de magasin)

### Dénormalisation (requête)
- `user:update`

---

## Notes importantes

1. **Mise à jour automatique de l'utilisateur connecté** : L'endpoint met toujours à jour le profil de l'utilisateur authentifié, indépendamment des données envoyées.

2. **Format JSON vs Multipart** :
   - Utilisez `application/json` pour les mises à jour simples sans fichier
   - Utilisez `multipart/form-data` pour les mises à jour avec upload d'image
   - Le paramètre `isOnline` n'est accepté qu'en JSON

3. **Champs non modifiables** :
   - `email` : Ne peut pas être modifié via cet endpoint
   - `password` : Utilisez un endpoint dédié pour changer le mot de passe
   - `roles` : Gérés par l'administration

4. **Création automatique de Delivery** : Pour les livreurs, si l'entité `Delivery` n'existe pas, elle sera créée automatiquement lors de la première mise à jour.

5. **Validation des types de véhicules** : Les valeurs acceptées pour `vehicleType` sont :
   - `bike`
   - `motorcycle`
   - `car`
   - `van`

---

## Exemples d'utilisation dans différents langages

### JavaScript (Fetch API)

```javascript
// Mise à jour avec JSON
const updateUser = async (userData) => {
  const response = await fetch('https://api.example.com/api/user/update', {
    method: 'PUT',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(userData)
  });
  
  return await response.json();
};

// Mise à jour avec fichier
const updateUserWithImage = async (formData) => {
  const response = await fetch('https://api.example.com/api/user/update', {
    method: 'PUT',
    headers: {
      'Authorization': `Bearer ${token}`
      // Ne pas définir Content-Type pour multipart/form-data
    },
    body: formData
  });
  
  return await response.json();
};
```

### Python (requests)

```python
import requests

# Mise à jour avec JSON
def update_user(token, user_data):
    url = "https://api.example.com/api/user/update"
    headers = {
        "Authorization": f"Bearer {token}",
        "Content-Type": "application/json"
    }
    response = requests.put(url, headers=headers, json=user_data)
    return response.json()

# Mise à jour avec fichier
def update_user_with_image(token, user_data, image_path):
    url = "https://api.example.com/api/user/update"
    headers = {
        "Authorization": f"Bearer {token}"
    }
    files = {'imageFile': open(image_path, 'rb')}
    data = user_data
    response = requests.put(url, headers=headers, data=data, files=files)
    return response.json()
```

### PHP (Guzzle)

```php
use GuzzleHttp\Client;

// Mise à jour avec JSON
$client = new Client();
$response = $client->put('https://api.example.com/api/user/update', [
    'headers' => [
        'Authorization' => 'Bearer ' . $token,
        'Content-Type' => 'application/json'
    ],
    'json' => [
        'firstName' => 'Jean',
        'lastName' => 'Dupont',
        'phone' => '+261340000000'
    ]
]);

// Mise à jour avec fichier
$response = $client->put('https://api.example.com/api/user/update', [
    'headers' => [
        'Authorization' => 'Bearer ' . $token
    ],
    'multipart' => [
        [
            'name' => 'firstName',
            'contents' => 'Jean'
        ],
        [
            'name' => 'lastName',
            'contents' => 'Dupont'
        ],
        [
            'name' => 'imageFile',
            'contents' => fopen('/chemin/vers/avatar.jpg', 'r'),
            'filename' => 'avatar.jpg'
        ]
    ]
]);
```

---

## Structure du code côté serveur

### Fichiers principaux

- **Configuration API Platform:** `src/ApiResource/User.yaml`
- **Processeur de mise à jour:** `src/State/User/UserUpdateProcessor.php`
- **Entité User:** `src/Entity/User.php`
- **Entité Delivery:** `src/Entity/Delivery.php`

### Flux de traitement

1. La requête arrive à l'endpoint `/api/user/update`
2. API Platform route la requête vers `UserUpdateProcessor`
3. Le processeur récupère l'utilisateur authentifié depuis le token
4. Les données sont traitées selon le format (JSON ou multipart)
5. Les validations sont effectuées
6. Les modifications sont persistées en base de données
7. L'utilisateur mis à jour est retourné avec les groupes de normalisation configurés

---

## Support et assistance

Pour toute question ou problème concernant cet endpoint, veuillez consulter :
- La documentation générale de l'API : `docs/API_ENDPOINTS.md`
- Le code source du processeur : `src/State/User/UserUpdateProcessor.php`

