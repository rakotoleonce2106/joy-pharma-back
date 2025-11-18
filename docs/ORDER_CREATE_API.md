# Documentation - Order Create API

## Vue d'ensemble

Cette documentation décrit les endpoints API pour créer et simuler une nouvelle commande. Les endpoints permettent aux utilisateurs authentifiés de :
- **Créer une commande** : Créer une commande avec des produits, une localisation de livraison optionnelle, et un paiement associé
- **Simuler une commande** : Prévisualiser le montant total, les réductions et les détails de promotion sans créer la commande

Le processus de création est géré par le `OrderCreateProcessor` qui valide les données, crée les entités nécessaires, et gère les transactions de manière sécurisée. La simulation est gérée par le `OrderSimulationProvider` qui effectue les mêmes calculs sans persister les données.

---

## Endpoint

**URL:** `POST /api/order`

**Méthode:** `POST`

**Authentification:** Requise (utilisateur authentifié)

**Headers requis:**
```
Content-Type: application/json
Authorization: Bearer {JWT_TOKEN}
```

---

## Format de requête

L'endpoint accepte uniquement le format `application/json`.

---

## Paramètres de requête

### Paramètres requis

| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| `date` | DateTime | Oui | Date et heure prévues pour la livraison |
| `items` | array | Oui | Tableau d'objets ItemInput (minimum 1 item) |
| `phone` | string | Oui | Numéro de téléphone du client |
| `priority` | string | Oui | Priorité de la commande: `urgent`, `standard`, `planified` |
| `notes` | string | Oui | Notes internes pour la commande |
| `paymentMethod` | string | Oui | Méthode de paiement: `mvola`, `stripe`, `paypal`, `airtel_money`, `orange_money` |

### Paramètres optionnels

| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| `latitude` | string | Non | Latitude pour la localisation de livraison |
| `longitude` | string | Non | Longitude pour la localisation de livraison |
| `address` | string | Non | Adresse complète pour la livraison |
| `promotionCode` | string | Non | Code promotionnel à appliquer à la commande |

### Structure ItemInput

Chaque élément du tableau `items` doit avoir la structure suivante:

| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| `id` | integer | Oui | ID du produit |
| `quantity` | integer | Oui | Quantité du produit (doit être > 0) |

### Exemple de requête

```json
{
  "date": "2024-12-20T14:30:00+00:00",
  "items": [
    {
      "id": 1,
      "quantity": 2
    },
    {
      "id": 5,
      "quantity": 1
    }
  ],
  "phone": "+261341234567",
  "priority": "standard",
  "notes": "Livraison urgente demandée",
  "paymentMethod": "mvola",
  "latitude": "-18.8792",
  "longitude": "47.5079",
  "address": "123 Rue de l'Indépendance, Antananarivo",
  "promotionCode": "PROMO2024"
}
```

---

## Réponse

### Succès (201 Created)

L'endpoint retourne un objet Order avec toutes les informations de la commande créée.

**Structure de la réponse:**
```json
{
  "id": 123,
  "reference": "ORD-2024-001234",
  "owner": {
    "id": 45,
    "firstName": "John",
    "lastName": "Doe"
  },
  "status": "pending",
  "priority": "standard",
  "totalAmount": 13500.00,
  "discountAmount": 1500.00,
  "phone": "+261341234567",
  "notes": "Livraison urgente demandée",
  "scheduledDate": "2024-12-20T14:30:00+00:00",
  "promotion": {
    "id": 5,
    "code": "PROMO2024",
    "name": "Promotion de fin d'année"
  },
  "location": {
    "id": 78,
    "latitude": -18.8792,
    "longitude": 47.5079,
    "address": "123 Rue de l'Indépendance, Antananarivo"
  },
  "items": [
    {
      "id": 1,
      "product": {
        "id": 1,
        "name": "Paracétamol 500mg",
        "totalPrice": 5000.00
      },
      "quantity": 2,
      "totalPrice": 10000.00,
      "storeStatus": "pending"
    },
    {
      "id": 2,
      "product": {
        "id": 5,
        "name": "Ibuprofène 400mg",
        "totalPrice": 5000.00
      },
      "quantity": 1,
      "totalPrice": 5000.00,
      "storeStatus": "pending"
    }
  ],
  "payment": {
    "id": 56,
    "method": "mvola",
    "amount": "13500.00",
    "status": "pending",
    "reference": "ORD-2024-001234"
  },
  "createdAt": "2024-12-19T10:15:00+00:00"
}
```

### Erreurs

#### 400 Bad Request

**User not authenticated:**
```json
{
  "type": "https://tools.ietf.org/html/rfc2616#section-10",
  "title": "An error occurred",
  "status": 400,
  "detail": "User not authenticated"
}
```

**Validation failed:**
```json
{
  "type": "https://tools.ietf.org/html/rfc2616#section-10",
  "title": "An error occurred",
  "status": 400,
  "detail": "Validation failed: date: This value should not be blank., items: This collection should contain 1 element or more."
}
```

**Invalid input data type:**
```json
{
  "type": "https://tools.ietf.org/html/rfc2616#section-10",
  "title": "An error occurred",
  "status": 400,
  "detail": "Invalid input data type"
}
```

**Order must contain at least one item:**
```json
{
  "type": "https://tools.ietf.org/html/rfc2616#section-10",
  "title": "An error occurred",
  "status": 400,
  "detail": "Order must contain at least one item"
}
```

**Invalid item structure:**
```json
{
  "type": "https://tools.ietf.org/html/rfc2616#section-10",
  "title": "An error occurred",
  "status": 400,
  "detail": "Invalid item structure: id and quantity are required"
}
```

**Invalid quantity:**
```json
{
  "type": "https://tools.ietf.org/html/rfc2616#section-10",
  "title": "An error occurred",
  "status": 400,
  "detail": "Invalid quantity for product ID 1: quantity must be greater than 0"
}
```

**Product not found:**
```json
{
  "type": "https://tools.ietf.org/html/rfc2616#section-10",
  "title": "An error occurred",
  "status": 400,
  "detail": "Product not found with ID: 999"
}
```

**Product not active:**
```json
{
  "type": "https://tools.ietf.org/html/rfc2616#section-10",
  "title": "An error occurred",
  "status": 400,
  "detail": "Product Paracétamol 500mg (ID: 1) is not active"
}
```

**Product has invalid price:**
```json
{
  "type": "https://tools.ietf.org/html/rfc2616#section-10",
  "title": "An error occurred",
  "status": 400,
  "detail": "Product Paracétamol 500mg (ID: 1) has invalid price"
}
```

**Invalid or expired promotion code:**
```json
{
  "type": "https://tools.ietf.org/html/rfc2616#section-10",
  "title": "An error occurred",
  "status": 400,
  "detail": "Invalid or expired promotion code: PROMO2024"
}
```

**Promotion requires minimum order amount:**
```json
{
  "type": "https://tools.ietf.org/html/rfc2616#section-10",
  "title": "An error occurred",
  "status": 400,
  "detail": "Promotion code PROMO2024 requires a minimum order amount of 20,000.00 Ar. Current order total: 15,000.00 Ar"
}
```

**Promotion cannot be applied:**
```json
{
  "type": "https://tools.ietf.org/html/rfc2616#section-10",
  "title": "An error occurred",
  "status": 400,
  "detail": "Promotion code PROMO2024 cannot be applied to this order"
}
```

**Failed to create order:**
```json
{
  "type": "https://tools.ietf.org/html/rfc2616#section-10",
  "title": "An error occurred",
  "status": 400,
  "detail": "Failed to create order: {error_message}"
}
```

---

## Flux de traitement

1. **Authentification**
   - Vérifie que l'utilisateur est authentifié
   - Récupère l'utilisateur depuis le token

2. **Validation des données**
   - Valide la structure des données d'entrée
   - Vérifie que les données sont de type `OrderInput`
   - Valide tous les champs requis selon les contraintes de validation

3. **Validation des items**
   - Vérifie que le tableau `items` n'est pas vide
   - Pour chaque item:
     - Valide la structure (id et quantity présents)
     - Valide que la quantité est > 0
     - Vérifie que le produit existe
     - Vérifie que le produit est actif
     - Vérifie que le produit a un prix valide

4. **Création de la commande**
   - Démarre une transaction de base de données
   - Crée l'entité Order avec les informations de base
   - Crée la localisation si les coordonnées sont fournies
   - Sauvegarde la localisation dans les adresses de l'utilisateur si elle n'existe pas déjà
   - Traite chaque item de commande:
     - Crée l'OrderItem
     - Calcule le prix total
     - Assigne un magasin si le produit est disponible
   - Calcule le montant total de la commande (sous-total)
   - Applique le code promotionnel si fourni:
     - Vérifie que le code est valide et actif
     - Vérifie que le code n'a pas expiré
     - Vérifie que la limite d'utilisation n'est pas atteinte
     - Vérifie le montant minimum de commande requis
     - Calcule le montant de la réduction
     - Associe la promotion à la commande
     - Incrémente le compteur d'utilisation de la promotion
   - Calcule le montant final (sous-total - réduction)
   - Crée l'entité Payment associée avec le montant final
   - Persiste toutes les entités
   - Commit la transaction

5. **Gestion des erreurs**
   - En cas d'erreur, rollback la transaction
   - Log l'erreur avec les détails
   - Retourne une erreur appropriée

---

## Validation et contraintes

### OrderInput

- `date`: Requis, doit être une date valide
- `items`: Requis, doit contenir au moins 1 élément
- `phone`: Requis, doit être une chaîne non vide
- `priority`: Requis, doit être une chaîne non vide
- `notes`: Requis, doit être une chaîne non vide
- `paymentMethod`: Requis, doit être une chaîne non vide
- `latitude`: Optionnel, doit être une chaîne valide
- `longitude`: Optionnel, doit être une chaîne valide
- `address`: Optionnel, doit être une chaîne valide
- `promotionCode`: Optionnel, doit être un code promotionnel valide et actif

### ItemInput

- `id`: Requis, doit être un entier positif
- `quantity`: Requis, doit être un entier > 0

### Validations métier

- Le produit doit exister dans la base de données
- Le produit doit être actif (`isActive = true`)
- Le produit doit avoir un prix valide (> 0)
- La quantité doit être > 0
- La commande doit contenir au moins un item
- Le code promotionnel doit être valide et actif
- Le code promotionnel ne doit pas être expiré (date de fin)
- Le code promotionnel ne doit pas avoir atteint sa limite d'utilisation
- Le montant total de la commande doit respecter le montant minimum requis par la promotion (si applicable)

---

## Services et dépendances

### OrderCreateProcessor

Le processeur utilise les services suivants:

- **EntityManagerInterface**: Gestion des entités et transactions
- **TokenStorageInterface**: Récupération de l'utilisateur authentifié
- **ValidatorInterface**: Validation des données d'entrée
- **ProductRepository**: Recherche des produits
- **PromotionRepository**: Recherche et validation des codes promotionnels
- **LoggerInterface**: Journalisation des événements

### Entités créées

1. **Order**: Commande principale
2. **Location**: Localisation de livraison (si fournie, également ajoutée aux adresses sauvegardées de l'utilisateur)
3. **OrderItem**: Items de la commande (un par produit)
4. **Payment**: Paiement associé à la commande
5. **Promotion**: Association avec la promotion si un code est fourni (mise à jour du compteur d'utilisation)

---

## Gestion des transactions

Le processeur utilise des transactions de base de données pour garantir l'intégrité des données:

1. **Début de transaction**: `beginTransaction()`
2. **Persistance des entités**: Toutes les entités sont persistées
3. **Commit**: Si tout réussit, la transaction est commitée
4. **Rollback**: En cas d'erreur, la transaction est annulée

Cela garantit que soit toutes les entités sont créées, soit aucune n'est créée en cas d'erreur.

---

## Journalisation

Le processeur enregistre les événements suivants dans les logs:

### Info

**Création d'une nouvelle commande:**
```php
[
  'user_id' => 45,
  'items_count' => 2,
  'payment_method' => 'mvola'
]
```

**Commande créée avec succès:**
```php
[
  'order_id' => 123,
  'order_reference' => 'ORD-2024-001234',
  'user_id' => 45,
  'total_amount' => 13500.00,
  'discount_amount' => 1500.00,
  'promotion_code' => 'PROMO2024'
]
```

**Promotion appliquée à la commande:**
```php
[
  'promotion_code' => 'PROMO2024',
  'discount_amount' => 1500.00,
  'order_total' => 15000.00
]
```

### Error

**Échec de création de commande:**
```php
[
  'user_id' => 45,
  'error' => 'Product not found with ID: 999',
  'trace' => '...'
]
```

---

## Exemples d'utilisation

### cURL

```bash
curl -X POST "https://api.example.com/api/order" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -d '{
    "date": "2024-12-20T14:30:00+00:00",
    "items": [
      {
        "id": 1,
        "quantity": 2
      },
      {
        "id": 5,
        "quantity": 1
      }
    ],
    "phone": "+261341234567",
    "priority": "standard",
    "notes": "Livraison urgente demandée",
    "paymentMethod": "mvola",
    "latitude": "-18.8792",
    "longitude": "47.5079",
    "address": "123 Rue de l'\''Indépendance, Antananarivo",
    "promotionCode": "PROMO2024"
  }'
```

### JavaScript (Fetch API)

```javascript
const response = await fetch('/api/order', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': `Bearer ${token}`
  },
  body: JSON.stringify({
    date: '2024-12-20T14:30:00+00:00',
    items: [
      {
        id: 1,
        quantity: 2
      },
      {
        id: 5,
        quantity: 1
      }
    ],
    phone: '+261341234567',
    priority: 'standard',
    notes: 'Livraison urgente demandée',
    paymentMethod: 'mvola',
    latitude: '-18.8792',
    longitude: '47.5079',
    address: '123 Rue de l\'Indépendance, Antananarivo',
    promotionCode: 'PROMO2024'
  })
});

const order = await response.json();
console.log('Order created:', order.reference);
```

### PHP (Guzzle)

```php
use GuzzleHttp\Client;

$client = new Client([
    'base_uri' => 'https://api.example.com',
    'headers' => [
        'Authorization' => 'Bearer ' . $token,
        'Content-Type' => 'application/json',
    ]
]);

$response = $client->post('/api/order', [
    'json' => [
        'date' => '2024-12-20T14:30:00+00:00',
        'items' => [
            [
                'id' => 1,
                'quantity' => 2
            ],
            [
                'id' => 5,
                'quantity' => 1
            ]
        ],
        'phone' => '+261341234567',
        'priority' => 'standard',
        'notes' => 'Livraison urgente demandée',
        'paymentMethod' => 'mvola',
        'latitude' => '-18.8792',
        'longitude' => '47.5079',
        'address' => '123 Rue de l\'Indépendance, Antananarivo',
        'promotionCode' => 'PROMO2024'
    ]
]);

$order = json_decode($response->getBody(), true);
```

---

## Notes importantes

### Assignation automatique de magasin

Le processeur assigne automatiquement un magasin à chaque item de commande si le produit est disponible dans un magasin. Le premier magasin trouvé avec le produit en stock est assigné.

### Calcul du prix total

Le prix total de chaque item est calculé comme suit:
- Si le produit a un `totalPrice`, il est utilisé
- Sinon, si le produit a un `unitPrice`, il est utilisé
- Le prix est multiplié par la quantité

Le sous-total de la commande est la somme de tous les prix totaux des items.

Si un code promotionnel est fourni:
- Le code est validé (actif, non expiré, limite d'utilisation non atteinte)
- Le montant minimum de commande est vérifié
- La réduction est calculée selon le type de promotion:
  - **Pourcentage**: `(sous-total × valeur_pourcentage) / 100`
  - **Montant fixe**: `valeur_fixe`
- Un montant maximum de réduction peut être appliqué si défini
- La réduction ne peut pas dépasser le sous-total
- Le montant final = sous-total - réduction

Le champ `totalAmount` de la commande contient le montant final après réduction.
Le champ `discountAmount` contient le montant de la réduction appliquée.

### Statut par défaut

- **Order**: `pending` (défini dans le constructeur)
- **OrderItem**: `pending` (défini dans le constructeur)
- **Payment**: `pending` (défini dans le constructeur)

### Référence de commande

La référence de commande est générée automatiquement lors de la création de l'entité Order. Le format est généralement `ORD-{YEAR}-{NUMBER}`.

### Localisation optionnelle

La localisation de livraison est créée uniquement si les trois paramètres suivants sont fournis:
- `latitude`
- `longitude`
- `address`

Si l'un de ces paramètres est manquant, aucune localisation n'est créée.

**Sauvegarde automatique des adresses:**
Lors de la création d'une commande avec une localisation, celle-ci est automatiquement ajoutée aux adresses sauvegardées de l'utilisateur si elle n'existe pas déjà (comparaison par coordonnées et adresse). Cela permet à l'utilisateur de réutiliser facilement ses adresses précédentes.

### Codes promotionnels

Les codes promotionnels peuvent être appliqués lors de la création d'une commande. Le système vérifie:
- **Validité du code**: Le code doit exister et être actif
- **Dates de validité**: Le code doit être dans sa période de validité (startDate/endDate)
- **Limite d'utilisation**: Le code ne doit pas avoir atteint sa limite d'utilisation (usageLimit)
- **Montant minimum**: Le sous-total de la commande doit respecter le montant minimum requis (minimumOrderAmount)
- **Type de réduction**: 
  - Pourcentage: Réduction en pourcentage du montant total
  - Montant fixe: Réduction d'un montant fixe
- **Montant maximum**: Si défini, la réduction ne peut pas dépasser ce montant (maximumDiscountAmount)

Le compteur d'utilisation de la promotion est automatiquement incrémenté lors de l'application réussie du code.

---

## Sécurité

- L'endpoint nécessite une authentification (utilisateur authentifié)
- Les données sont validées avant traitement
- Les transactions garantissent l'intégrité des données
- Les erreurs sont loggées sans exposer d'informations sensibles

---

## Dépannage

### Erreur: "User not authenticated"

**Cause:** L'utilisateur n'est pas authentifié ou le token est invalide.

**Solution:** Vérifiez que le token JWT est valide et inclus dans les headers.

### Erreur: "Product not found with ID: X"

**Cause:** Le produit avec l'ID spécifié n'existe pas dans la base de données.

**Solution:** Vérifiez que l'ID du produit est correct et que le produit existe.

### Erreur: "Product X is not active"

**Cause:** Le produit existe mais n'est pas actif.

**Solution:** Activez le produit dans l'interface d'administration ou choisissez un autre produit.

### Erreur: "Product X has invalid price"

**Cause:** Le produit n'a pas de prix valide (prix = 0 ou null).

**Solution:** Définissez un prix valide pour le produit dans l'interface d'administration.

### Erreur: "Invalid or expired promotion code: X"

**Cause:** Le code promotionnel fourni n'existe pas, n'est pas actif, a expiré, ou a atteint sa limite d'utilisation.

**Solution:** Vérifiez que le code promotionnel est correct, actif, et dans sa période de validité. Vérifiez également qu'il n'a pas atteint sa limite d'utilisation.

### Erreur: "Promotion code X requires a minimum order amount of Y Ar. Current order total: Z Ar"

**Cause:** Le code promotionnel nécessite un montant minimum de commande qui n'est pas atteint.

**Solution:** Augmentez le montant de votre commande pour atteindre le montant minimum requis, ou utilisez un autre code promotionnel sans restriction de montant minimum.

### Erreur: "Promotion code X cannot be applied to this order"

**Cause:** Le code promotionnel ne peut pas être appliqué (réduction calculée = 0).

**Solution:** Vérifiez les conditions d'application de la promotion. Cela peut se produire si le montant minimum n'est pas respecté ou si la réduction calculée est nulle.

### Erreur: "Failed to create order"

**Cause:** Une erreur s'est produite lors de la création de la commande (erreur de base de données, etc.).

**Solution:** Vérifiez les logs pour plus de détails sur l'erreur spécifique.

---

## API de Simulation de Commande

### Vue d'ensemble

L'API de simulation permet de prévisualiser le montant total d'une commande, les réductions appliquées et les détails de promotion **sans créer réellement la commande**. C'est utile pour :
- Tester l'application d'un code promotionnel avant de créer la commande
- Afficher un aperçu du montant final à l'utilisateur
- Valider que tous les produits sont disponibles et actifs

**Important :** La simulation ne persiste aucune donnée en base de données. Le compteur d'utilisation des promotions n'est pas incrémenté lors d'une simulation.

---

### Endpoint de Simulation

**URL:** `POST /api/order/simulate`

**Méthode:** `POST`

**Authentification:** Requise (utilisateur authentifié)

**Headers requis:**
```
Content-Type: application/json
Authorization: Bearer {JWT_TOKEN}
```

---

### Format de requête

L'endpoint accepte uniquement le format `application/json`.

**Note :** Les paramètres d'entrée sont identiques à ceux de l'endpoint de création de commande (`POST /api/order`).

---

### Paramètres de requête

Les paramètres sont exactement les mêmes que pour la création de commande. Voir la section [Paramètres de requête](#paramètres-de-requête) ci-dessus.

---

### Exemple de requête de simulation

```json
{
  "date": "2024-12-20T14:30:00+00:00",
  "items": [
    {
      "id": 1,
      "quantity": 2
    },
    {
      "id": 5,
      "quantity": 1
    }
  ],
  "phone": "+261341234567",
  "priority": "standard",
  "notes": "Test simulation",
  "paymentMethod": "mvola",
  "promotionCode": "PROMO2024"
}
```

---

### Réponse de simulation

#### Succès (200 OK)

L'endpoint retourne un objet `OrderSimulationOutput` avec les détails de la simulation.

**Structure de la réponse (promotion valide):**
```json
{
  "subtotal": 15000.00,
  "discountAmount": 1500.00,
  "totalAmount": 13500.00,
  "promotionCode": "PROMO2024",
  "promotionValid": true,
  "promotionError": null,
  "promotion": {
    "id": 5,
    "code": "PROMO2024",
    "name": "Promotion de fin d'année",
    "description": "10% de réduction sur votre commande",
    "discountType": "percentage",
    "discountValue": 10.0
  },
  "items": [
    {
      "productId": 1,
      "productName": "Paracétamol 500mg",
      "quantity": 2,
      "unitPrice": 5000.00,
      "totalPrice": 10000.00
    },
    {
      "productId": 5,
      "productName": "Ibuprofène 400mg",
      "quantity": 1,
      "unitPrice": 5000.00,
      "totalPrice": 5000.00
    }
  ]
}
```

**Structure de la réponse (promotion invalide):**
```json
{
  "subtotal": 15000.00,
  "discountAmount": 0.00,
  "totalAmount": 15000.00,
  "promotionCode": "PROMO2024",
  "promotionValid": false,
  "promotionError": "Promotion code PROMO2024 requires a minimum order amount of 20,000.00 Ar. Current order total: 15,000.00 Ar",
  "promotion": {
    "id": 5,
    "code": "PROMO2024",
    "name": "Promotion de fin d'année"
  },
  "items": [
    {
      "productId": 1,
      "productName": "Paracétamol 500mg",
      "quantity": 2,
      "unitPrice": 5000.00,
      "totalPrice": 10000.00
    },
    {
      "productId": 5,
      "productName": "Ibuprofène 400mg",
      "quantity": 1,
      "unitPrice": 5000.00,
      "totalPrice": 5000.00
    }
  ]
}
```

**Structure de la réponse (sans code promotionnel):**
```json
{
  "subtotal": 15000.00,
  "discountAmount": 0.00,
  "totalAmount": 15000.00,
  "promotionCode": null,
  "promotionValid": false,
  "promotionError": null,
  "promotion": null,
  "items": [
    {
      "productId": 1,
      "productName": "Paracétamol 500mg",
      "quantity": 2,
      "unitPrice": 5000.00,
      "totalPrice": 10000.00
    },
    {
      "productId": 5,
      "productName": "Ibuprofène 400mg",
      "quantity": 1,
      "unitPrice": 5000.00,
      "totalPrice": 5000.00
    }
  ]
}
```

#### Champs de la réponse

| Champ | Type | Description |
|-------|------|-------------|
| `subtotal` | float | Sous-total de la commande (avant réduction) |
| `discountAmount` | float | Montant de la réduction appliquée |
| `totalAmount` | float | Montant final après réduction |
| `promotionCode` | string\|null | Code promotionnel utilisé (si fourni) |
| `promotionValid` | boolean | Indique si la promotion est valide et applicable |
| `promotionError` | string\|null | Message d'erreur si la promotion n'est pas applicable |
| `promotion` | object\|null | Détails de la promotion (si valide ou partiellement valide) |
| `items` | array | Liste des items avec leurs détails de prix |

#### Détails de l'objet promotion

| Champ | Type | Description |
|-------|------|-------------|
| `id` | integer | ID de la promotion |
| `code` | string | Code promotionnel |
| `name` | string | Nom de la promotion |
| `description` | string\|null | Description de la promotion (si valide) |
| `discountType` | string | Type de réduction: `percentage` ou `fixed_amount` |
| `discountValue` | float | Valeur de la réduction (pourcentage ou montant fixe) |

#### Détails des items

| Champ | Type | Description |
|-------|------|-------------|
| `productId` | integer | ID du produit |
| `productName` | string | Nom du produit |
| `quantity` | integer | Quantité commandée |
| `unitPrice` | float | Prix unitaire du produit |
| `totalPrice` | float | Prix total (unitPrice × quantity) |

---

### Erreurs de simulation

Les erreurs de validation sont identiques à celles de la création de commande. Voir la section [Erreurs](#erreurs) ci-dessus.

**Note importante :** Même si une promotion n'est pas valide, la simulation retourne toujours un résultat avec `promotionValid: false` et un message d'erreur explicite. La simulation ne lève pas d'exception pour les promotions invalides, contrairement à la création de commande.

---

### Exemples d'utilisation de la simulation

#### cURL

```bash
curl -X POST "https://api.example.com/api/order/simulate" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -d '{
    "date": "2024-12-20T14:30:00+00:00",
    "items": [
      {
        "id": 1,
        "quantity": 2
      },
      {
        "id": 5,
        "quantity": 1
      }
    ],
    "phone": "+261341234567",
    "priority": "standard",
    "notes": "Test simulation",
    "paymentMethod": "mvola",
    "promotionCode": "PROMO2024"
  }'
```

#### JavaScript (Fetch API)

```javascript
const response = await fetch('/api/order/simulate', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': `Bearer ${token}`
  },
  body: JSON.stringify({
    date: '2024-12-20T14:30:00+00:00',
    items: [
      {
        id: 1,
        quantity: 2
      },
      {
        id: 5,
        quantity: 1
      }
    ],
    phone: '+261341234567',
    priority: 'standard',
    notes: 'Test simulation',
    paymentMethod: 'mvola',
    promotionCode: 'PROMO2024'
  })
});

const simulation = await response.json();
console.log('Subtotal:', simulation.subtotal);
console.log('Discount:', simulation.discountAmount);
console.log('Total:', simulation.totalAmount);
console.log('Promotion valid:', simulation.promotionValid);
```

#### PHP (Guzzle)

```php
use GuzzleHttp\Client;

$client = new Client([
    'base_uri' => 'https://api.example.com',
    'headers' => [
        'Authorization' => 'Bearer ' . $token,
        'Content-Type' => 'application/json',
    ]
]);

$response = $client->post('/api/order/simulate', [
    'json' => [
        'date' => '2024-12-20T14:30:00+00:00',
        'items' => [
            [
                'id' => 1,
                'quantity' => 2
            ],
            [
                'id' => 5,
                'quantity' => 1
            ]
        ],
        'phone' => '+261341234567',
        'priority' => 'standard',
        'notes' => 'Test simulation',
        'paymentMethod' => 'mvola',
        'promotionCode' => 'PROMO2024'
    ]
]);

$simulation = json_decode($response->getBody(), true);
echo "Total: " . $simulation['totalAmount'] . "\n";
```

---

### Cas d'usage de la simulation

1. **Aperçu avant paiement**
   - Afficher le montant total à l'utilisateur avant qu'il confirme la commande
   - Permettre à l'utilisateur de tester différents codes promotionnels

2. **Validation de promotion**
   - Vérifier si un code promotionnel est valide avant de créer la commande
   - Afficher les messages d'erreur explicites si la promotion n'est pas applicable

3. **Calcul dynamique**
   - Mettre à jour le montant total en temps réel lorsque l'utilisateur modifie les quantités
   - Recalculer automatiquement lors de l'ajout ou de la suppression d'items

4. **Interface utilisateur**
   - Afficher un résumé détaillé des prix avant confirmation
   - Montrer la répartition des coûts (sous-total, réduction, total)

---

### Différences entre Simulation et Création

| Aspect | Simulation | Création |
|--------|------------|----------|
| **Persistance** | Aucune donnée n'est sauvegardée | Toutes les entités sont créées en base |
| **Promotion** | Le compteur d'utilisation n'est pas incrémenté | Le compteur d'utilisation est incrémenté |
| **Location** | La localisation n'est pas ajoutée aux adresses utilisateur | La localisation est ajoutée aux adresses utilisateur |
| **Réponse** | Retourne `OrderSimulationOutput` | Retourne `Order` complet |
| **Erreurs promotion** | Retourne un message d'erreur dans la réponse | Lève une exception si la promotion est invalide |
| **Validation** | Même validation que la création | Validation complète avec persistance |

---

### Notes importantes sur la simulation

1. **Pas de persistance** : Aucune donnée n'est sauvegardée lors d'une simulation. C'est une opération en lecture seule.

2. **Validation complète** : La simulation effectue les mêmes validations que la création de commande (produits existants, actifs, prix valides, etc.).

3. **Promotions** : Les promotions sont validées mais leur compteur d'utilisation n'est pas incrémenté. Cela permet de tester plusieurs fois le même code sans affecter sa disponibilité.

4. **Performance** : La simulation est généralement plus rapide que la création car elle ne nécessite pas de transactions de base de données.

5. **Workflow recommandé** : 
   - Utiliser la simulation pour afficher un aperçu à l'utilisateur
   - Utiliser la création de commande une fois que l'utilisateur confirme

---

## Version

- **Version:** 1.1.0
- **Dernière mise à jour:** 2024
- **Auteur:** Joy Pharma Development Team

