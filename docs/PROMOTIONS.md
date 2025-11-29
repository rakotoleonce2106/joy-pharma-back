# Documentation des Promotions

Ce document décrit le système de promotions de l'application, qui comprend deux types distincts de promotions :

1. **Codes Promotionnels** : Réductions appliquées au total de la commande
2. **Promotions Produits** : Réductions appliquées directement sur le prix des produits individuels

---

## Table des matières

- [Codes Promotionnels](#codes-promotionnels)
  - [Créer un code promotionnel](#créer-un-code-promotionnel)
  - [Lister les codes promotionnels](#lister-les-codes-promotionnels)
  - [Obtenir un code promotionnel](#obtenir-un-code-promotionnel)
  - [Modifier un code promotionnel](#modifier-un-code-promotionnel)
  - [Supprimer un code promotionnel](#supprimer-un-code-promotionnel)
  - [Utiliser un code promotionnel dans une commande](#utiliser-un-code-promotionnel-dans-une-commande)

- [Promotions Produits](#promotions-produits)
  - [Créer une promotion produit](#créer-une-promotion-produit)
  - [Lister les promotions produits](#lister-les-promotions-produits)
  - [Obtenir une promotion produit](#obtenir-une-promotion-produit)
  - [Modifier une promotion produit](#modifier-une-promotion-produit)
  - [Supprimer une promotion produit](#supprimer-une-promotion-produit)
  - [Application automatique](#application-automatique)

- [Exemples complets](#exemples-complets)

---

## Codes Promotionnels

Les codes promotionnels sont des réductions appliquées au **total de la commande**. Ils peuvent être de type :
- **Pourcentage** : Réduction en pourcentage (ex: 10%)
- **Montant fixe** : Réduction en montant fixe (ex: 5000 Ar)

### Caractéristiques

- Code unique (3-50 caractères)
- Type de réduction : `percentage` ou `fixed_amount`
- Valeur de réduction
- Montant minimum de commande (optionnel)
- Montant maximum de réduction (optionnel)
- Dates de début et fin (optionnel)
- Limite d'utilisation (optionnel)
- Statut actif/inactif

### Créer un code promotionnel

**Endpoint** : `POST /admin/promotions`

**Authentification** : Requiert `ROLE_ADMIN`

**Corps de la requête** :

```json
{
  "code": "PROMO10",
  "name": "Promotion 10%",
  "description": "Réduction de 10% sur votre commande",
  "discountType": "percentage",
  "discountValue": 10.0,
  "minimumOrderAmount": 50000.0,
  "maximumDiscountAmount": 10000.0,
  "startDate": "2024-01-01T00:00:00+00:00",
  "endDate": "2024-12-31T23:59:59+00:00",
  "usageLimit": 100,
  "isActive": true
}
```

**Exemple avec montant fixe** :

```json
{
  "code": "FIXE5000",
  "name": "Réduction fixe 5000 Ar",
  "description": "Réduction de 5000 Ar sur votre commande",
  "discountType": "fixed_amount",
  "discountValue": 5000.0,
  "minimumOrderAmount": 30000.0,
  "startDate": "2024-01-01T00:00:00+00:00",
  "endDate": "2024-12-31T23:59:59+00:00",
  "isActive": true
}
```

**Réponse** :

```json
{
  "id": 1,
  "code": "PROMO10",
  "name": "Promotion 10%",
  "description": "Réduction de 10% sur votre commande",
  "discountType": "percentage",
  "discountValue": 10.0,
  "minimumOrderAmount": 50000.0,
  "maximumDiscountAmount": 10000.0,
  "startDate": "2024-01-01T00:00:00+00:00",
  "endDate": "2024-12-31T23:59:59+00:00",
  "usageLimit": 100,
  "usageCount": 0,
  "isActive": true,
  "createdAt": "2024-01-01T10:00:00+00:00",
  "updatedAt": "2024-01-01T10:00:00+00:00"
}
```

### Lister les codes promotionnels

**Endpoint** : `GET /admin/promotions`

**Authentification** : Requiert `ROLE_ADMIN`

**Réponse** :

```json
{
  "hydra:member": [
    {
      "id": 1,
      "code": "PROMO10",
      "name": "Promotion 10%",
      "discountType": "percentage",
      "discountValue": 10.0,
      "isActive": true,
      ...
    }
  ],
  "hydra:totalItems": 1
}
```

### Obtenir un code promotionnel

**Endpoint** : `GET /admin/promotions/{id}`

**Authentification** : Requiert `ROLE_ADMIN`

**Réponse** : Même format que la création

### Modifier un code promotionnel

**Endpoint** : `PUT /admin/promotions/{id}`

**Authentification** : Requiert `ROLE_ADMIN`

**Corps de la requête** : Même format que la création

### Supprimer un code promotionnel

**Endpoint** : `DELETE /admin/promotions/{id}`

**Authentification** : Requiert `ROLE_ADMIN`

**Note** : Ne peut pas supprimer les promotions qui ont été utilisées dans des commandes

### Utiliser un code promotionnel dans une commande

Lors de la création d'une commande, incluez le champ `promotionCode` dans le `OrderInput` :

```json
{
  "latitude": "-18.8792",
  "longitude": "47.5079",
  "address": "Antananarivo",
  "date": "2024-01-15T10:00:00+00:00",
  "items": [
    {
      "id": 1,
      "quantity": 2
    }
  ],
  "phone": "+261341234567",
  "priority": "standard",
  "notes": "Livraison à domicile",
  "paymentMethod": "cash",
  "promotionCode": "PROMO10"
}
```

**Validation automatique** :
- Le code doit être valide et non expiré
- Le montant minimum de commande doit être respecté
- La limite d'utilisation ne doit pas être atteinte
- Le code est automatiquement incrémenté après utilisation

---

## Promotions Produits

Les promotions produits sont des réductions en **pourcentage** appliquées directement sur le **prix des produits individuels**. Elles sont appliquées automatiquement lors du calcul du prix dans une commande.

### Caractéristiques

- Associée à un produit spécifique
- Réduction en pourcentage (0-100%)
- Dates de début et fin (optionnel)
- Statut actif/inactif
- Application automatique si valide

### Créer une promotion produit

**Endpoint** : `POST /admin/product-promotions`

**Authentification** : Requiert `ROLE_ADMIN`

**Corps de la requête** :

```json
{
  "productId": 1,
  "name": "Promotion Été 2024",
  "description": "Réduction spéciale été sur ce produit",
  "discountPercentage": 15.0,
  "startDate": "2024-06-01T00:00:00+00:00",
  "endDate": "2024-08-31T23:59:59+00:00",
  "isActive": true
}
```

**Réponse** :

```json
{
  "id": 1,
  "product": {
    "id": 1,
    "name": "Produit Exemple"
  },
  "name": "Promotion Été 2024",
  "description": "Réduction spéciale été sur ce produit",
  "discountPercentage": 15.0,
  "startDate": "2024-06-01T00:00:00+00:00",
  "endDate": "2024-08-31T23:59:59+00:00",
  "isActive": true,
  "createdAt": "2024-01-01T10:00:00+00:00",
  "updatedAt": "2024-01-01T10:00:00+00:00"
}
```

### Lister les promotions produits

**Endpoint** : `GET /admin/product-promotions`

**Authentification** : Requiert `ROLE_ADMIN`

**Réponse** :

```json
{
  "hydra:member": [
    {
      "id": 1,
      "product": {
        "id": 1,
        "name": "Produit Exemple"
      },
      "name": "Promotion Été 2024",
      "discountPercentage": 15.0,
      "isActive": true,
      ...
    }
  ],
  "hydra:totalItems": 1
}
```

### Obtenir une promotion produit

**Endpoint** : `GET /admin/product-promotions/{id}`

**Authentification** : Requiert `ROLE_ADMIN`

**Réponse** : Même format que la création

### Modifier une promotion produit

**Endpoint** : `PUT /admin/product-promotions/{id}`

**Authentification** : Requiert `ROLE_ADMIN`

**Corps de la requête** : Même format que la création

### Supprimer une promotion produit

**Endpoint** : `DELETE /admin/product-promotions/{id}`

**Authentification** : Requiert `ROLE_ADMIN`

### Application automatique

Les promotions produits sont **automatiquement appliquées** lors de :
- La création d'une commande (`POST /order`)
- La simulation d'une commande (`POST /order/simulate`)

**Règles d'application** :
- La promotion doit être active (`isActive = true`)
- La date actuelle doit être entre `startDate` et `endDate` (si définies)
- Si plusieurs promotions sont actives pour un produit, la plus avantageuse (pourcentage le plus élevé) est utilisée

**Exemple de calcul** :
- Prix produit original : 10000 Ar
- Promotion active : 15%
- Prix après promotion : 8500 Ar
- Commande de 2 unités : 17000 Ar

---

## Exemples complets

### Scénario 1 : Commande avec promotion produit uniquement

**1. Créer une promotion produit** :

```bash
POST /admin/product-promotions
{
  "productId": 1,
  "discountPercentage": 20.0,
  "isActive": true
}
```

**2. Créer une commande** (la promotion est appliquée automatiquement) :

```bash
POST /order
{
  "items": [
    {
      "id": 1,
      "quantity": 3
    }
  ],
  "date": "2024-01-15T10:00:00+00:00",
  "phone": "+261341234567",
  "priority": "standard",
  "notes": "",
  "paymentMethod": "cash"
}
```

**Résultat** : Le prix du produit est automatiquement réduit de 20% avant le calcul du total.

### Scénario 2 : Commande avec code promotionnel uniquement

**1. Créer un code promotionnel** :

```bash
POST /admin/promotions
{
  "code": "WELCOME10",
  "name": "Bienvenue 10%",
  "discountType": "percentage",
  "discountValue": 10.0,
  "isActive": true
}
```

**2. Créer une commande avec le code** :

```bash
POST /order
{
  "items": [
    {
      "id": 1,
      "quantity": 2
    }
  ],
  "date": "2024-01-15T10:00:00+00:00",
  "phone": "+261341234567",
  "priority": "standard",
  "notes": "",
  "paymentMethod": "cash",
  "promotionCode": "WELCOME10"
}
```

**Résultat** : 10% de réduction appliquée sur le total de la commande.

### Scénario 3 : Commande avec les deux types de promotions

**Ordre d'application** :
1. **Promotion produit** : Appliquée sur chaque produit individuellement
2. **Code promotionnel** : Appliquée sur le total de la commande (après les promotions produits)

**Exemple** :
- Produit A : 10000 Ar → Promotion produit 20% → 8000 Ar
- Produit B : 15000 Ar → Pas de promotion → 15000 Ar
- **Sous-total** : 23000 Ar
- **Code promotionnel 10%** : -2300 Ar
- **Total final** : 20700 Ar

### Simulation de commande

Pour prévisualiser le total avec les promotions sans créer la commande :

```bash
POST /order/simulate
{
  "items": [
    {
      "id": 1,
      "quantity": 2
    }
  ],
  "promotionCode": "PROMO10"
}
```

**Réponse** :

```json
{
  "subtotal": 20000.0,
  "totalAmount": 18000.0,
  "discountAmount": 2000.0,
  "promotionCode": "PROMO10",
  "promotionValid": true,
  "items": [
    {
      "productId": 1,
      "productName": "Produit Exemple",
      "quantity": 2,
      "unitPrice": 8500.0,
      "originalPrice": 10000.0,
      "totalPrice": 17000.0,
      "hasPromotion": true,
      "promotionDiscount": 15.0
    }
  ],
  "promotion": {
    "id": 1,
    "code": "PROMO10",
    "name": "Promotion 10%",
    "discountType": "percentage",
    "discountValue": 10.0
  }
}
```

---

## Validation et erreurs

### Codes promotionnels

**Erreurs possibles** :
- `Invalid or expired promotion code` : Le code n'existe pas ou est expiré
- `Promotion code requires a minimum order amount` : Le montant minimum n'est pas atteint
- `Promotion code cannot be applied to this order` : Le code ne peut pas être appliqué

### Promotions produits

Les promotions produits sont appliquées silencieusement. Si aucune promotion valide n'existe, le prix original est utilisé.

---

## Notes importantes

1. **Deux types distincts** : Les promotions produits et les codes promotionnels sont complètement indépendants
2. **Application automatique** : Les promotions produits sont toujours appliquées automatiquement
3. **Codes optionnels** : Les codes promotionnels doivent être fournis explicitement dans la commande
4. **Ordre d'application** : Promotions produits d'abord, puis codes promotionnels
5. **Validation des dates** : Les promotions sont vérifiées à chaque utilisation
6. **Limite d'utilisation** : Les codes promotionnels peuvent avoir une limite d'utilisation

---

## Endpoints résumés

### Codes Promotionnels (Admin)

- `GET /admin/promotions` - Lister
- `GET /admin/promotions/{id}` - Détails
- `POST /admin/promotions` - Créer
- `PUT /admin/promotions/{id}` - Modifier
- `DELETE /admin/promotions/{id}` - Supprimer

### Promotions Produits (Admin)

- `GET /admin/product-promotions` - Lister
- `GET /admin/product-promotions/{id}` - Détails
- `POST /admin/product-promotions` - Créer
- `PUT /admin/product-promotions/{id}` - Modifier
- `DELETE /admin/product-promotions/{id}` - Supprimer

### Utilisation dans les commandes

- `POST /order` - Créer une commande (avec `promotionCode` optionnel)
- `POST /order/simulate` - Simuler une commande (avec `promotionCode` optionnel)

