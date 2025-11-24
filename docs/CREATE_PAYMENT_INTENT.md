# Documentation - Create Payment Intent API

## Vue d'ensemble

Cette documentation décrit l'endpoint API pour créer un intent de paiement. L'endpoint permet de créer un intent de paiement pour une commande existante en utilisant le service de paiement MVola. L'intent de paiement est nécessaire pour initier une transaction de paiement mobile.

---

## Endpoint

**URL:** `POST /api/create-payment-intent`

**Méthode:** `POST`

**Authentification:** Requise (selon la configuration de sécurité)

**Headers requis:**
```
Content-Type: application/json
Authorization: Bearer {JWT_TOKEN} (si requis)
```

---

## Format de requête

L'endpoint accepte uniquement le format `application/json`.

---

## Paramètres de requête

| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| `method` | string | Oui | Méthode de paiement. Actuellement, `"mvola"` et `"mpgs"` sont supportées |
| `amount` | string/decimal | Oui | Montant du paiement (format décimal, ex: "100.00") |
| `reference` | string | Oui | Référence de la commande (order reference) |
| `phoneNumber` | string | Conditionnel | Numéro de téléphone pour le paiement MVola (format: +261XXXXXXXXX). Requis uniquement pour `"mvola"` |

### Exemple de requête (MVola)

```json
{
  "method": "mvola",
  "amount": "15000.00",
  "reference": "ORD-2024-001234",
  "phoneNumber": "+261341234567"
}
```

### Exemple de requête (MPGS)

```json
{
  "method": "mpgs",
  "amount": "15000.00",
  "reference": "ORD-2024-001234"
}
```

---

## Réponse

### Succès (201 Created)

L'endpoint retourne un objet JSON contenant les informations de l'intent de paiement créé.

**Structure de la réponse (MVola):**
```json
{
  "id": "serverCorrelationId",
  "clientSecret": "serverCorrelationId",
  "status": "pending",
  "provider": "Mvola",
  "reference": "ORD-2024-001234"
}
```

**Structure de la réponse (MPGS):**
```json
{
  "id": "sessionId",
  "clientSecret": "sessionId",
  "status": "pending",
  "provider": "MPGS",
  "reference": "ORD-2024-001234",
  "sessionId": "sessionId",
  "sessionVersion": "sessionVersion",
  "successIndicator": "successIndicator",
  "orderId": "orderId"
}
```

**Champs de la réponse:**

| Champ | Type | Description |
|-------|------|-------------|
| `id` | string | Identifiant unique de l'intent de paiement (serverCorrelationId pour MVola, sessionId pour MPGS) |
| `clientSecret` | string | Secret client pour l'intent de paiement (utilisé pour confirmer le paiement) |
| `status` | string | Statut de l'intent de paiement (généralement "pending") |
| `provider` | string | Nom du fournisseur de paiement (ex: "Mvola", "MPGS") |
| `reference` | string | Référence de la commande associée |
| `sessionId` | string | (MPGS uniquement) Identifiant de session MPGS |
| `sessionVersion` | string | (MPGS uniquement) Version de la session MPGS |
| `successIndicator` | string | (MPGS uniquement) Indicateur de succès pour la session MPGS |
| `orderId` | string | (MPGS uniquement) Identifiant de la commande MPGS |

### Erreurs

#### 400 Bad Request

**Invalid payment method:**
```json
{
  "type": "https://tools.ietf.org/html/rfc2616#section-10",
  "title": "An error occurred",
  "status": 400,
  "detail": "Invalid payment method. Only \"mvola\" and \"mpgs\" are currently supported."
}
```

**Phone number not found:**
```json
{
  "type": "https://tools.ietf.org/html/rfc2616#section-10",
  "title": "An error occurred",
  "status": 400,
  "detail": "Phone number not found"
}
```

**Failed to create payment intent:**
```json
{
  "type": "https://tools.ietf.org/html/rfc2616#section-10",
  "title": "An error occurred",
  "status": 400,
  "detail": "Failed to create payment intent: {error_message}"
}
```

#### 404 Not Found

**Order not found:**
```json
{
  "type": "https://tools.ietf.org/html/rfc2616#section-10",
  "title": "An error occurred",
  "status": 404,
  "detail": "Order not found with reference: {reference}"
}
```

**Order owner not found:**
```json
{
  "type": "https://tools.ietf.org/html/rfc2616#section-10",
  "title": "An error occurred",
  "status": 404,
  "detail": "Order owner not found."
}
```

---

## Flux de traitement

1. **Validation de la méthode de paiement**
   - Vérifie que la méthode de paiement est `"mvola"` (seule méthode supportée actuellement)

2. **Recherche de la commande**
   - Recherche la commande par référence (`reference`)
   - Vérifie que la commande existe

3. **Vérification du propriétaire**
   - Récupère le propriétaire (owner) de la commande
   - Vérifie que le propriétaire existe

4. **Association du paiement à la commande**
   - Associe le paiement à la commande
   - Met à jour la commande dans la base de données

5. **Création de l'intent de paiement**
   - Convertit le montant en centimes
   - Valide le montant et la devise
   - Crée l'intent de paiement via le service MVola
   - Enregistre le paiement dans la base de données

6. **Retour de la réponse**
   - Retourne les informations de l'intent de paiement créé

---

## Services utilisés

### PaymentIntentService

Service principal pour la création d'intents de paiement. Gère:
- La conversion du montant en centimes
- La validation du montant et de la devise
- La création de l'intent de paiement via le service MVola
- L'enregistrement du paiement

### MvolaPaymentService

Service spécialisé pour les paiements MVola. Gère:
- La création de la requête de transaction MVola
- L'appel à l'API MVola pour initier la transaction
- La gestion des erreurs spécifiques à MVola

### OrderService

Service pour la gestion des commandes. Utilisé pour:
- Rechercher une commande par référence
- Mettre à jour une commande

### CurrencyService

Service pour la gestion des devises. Utilisé pour:
- Convertir le montant en centimes
- Récupérer la devise courante
- Valider le montant

### PaymentService

Service pour la gestion des paiements. Utilisé pour:
- Enregistrer le paiement dans la base de données

---

## Exemples d'utilisation

### cURL

```bash
curl -X POST "https://api.example.com/api/create-payment-intent" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -d '{
    "method": "mvola",
    "amount": "15000.00",
    "reference": "ORD-2024-001234",
    "phoneNumber": "+261341234567"
  }'
```

### JavaScript (Fetch API)

```javascript
const response = await fetch('/api/create-payment-intent', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': `Bearer ${token}`
  },
  body: JSON.stringify({
    method: 'mvola',
    amount: '15000.00',
    reference: 'ORD-2024-001234',
    phoneNumber: '+261341234567'
  })
});

const paymentIntent = await response.json();
console.log('Payment Intent ID:', paymentIntent.id);
console.log('Client Secret:', paymentIntent.clientSecret);
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

$response = $client->post('/api/create-payment-intent', [
    'json' => [
        'method' => 'mvola',
        'amount' => '15000.00',
        'reference' => 'ORD-2024-001234',
        'phoneNumber' => '+261341234567'
    ]
]);

$paymentIntent = json_decode($response->getBody(), true);
```

---

## Notes importantes

### Méthodes de paiement supportées

Actuellement, les méthodes `"mvola"` et `"mpgs"` sont supportées. Les autres méthodes de paiement (Stripe, PayPal, Airtel Money, Orange Money) ne sont pas encore implémentées dans cet endpoint.

**MPGS (Mastercard Payment Gateway Services):**
- Ne nécessite pas de numéro de téléphone
- Retourne un `sessionId`, `sessionVersion` et `successIndicator` pour l'intégration avec le checkout MPGS
- Utilise l'API REST de MPGS pour créer une session de checkout

### Format du numéro de téléphone

Le numéro de téléphone doit être au format international avec le préfixe `+261` pour Madagascar. Exemple: `+261341234567`.

### Référence de commande

La référence de commande doit correspondre à une commande existante dans le système. La commande doit avoir un propriétaire (owner) valide.

### Statut de l'intent de paiement

L'intent de paiement est créé avec le statut `"pending"`. Le statut sera mis à jour lors de la confirmation ou de l'échec du paiement.

### Transaction ID

Le `transactionId` est généré automatiquement lors de la création de l'intent de paiement et correspond au `serverCorrelationId` retourné par l'API MVola.

---

## Logging

L'endpoint enregistre les événements suivants dans les logs:

- **Info:** Création d'un intent de paiement (avec user_id, amount, currency, reference, payment_method, phone_number)
- **Info:** Intent de paiement MVola créé avec succès (avec payment_intent_id, user_id, phone_number)
- **Error:** Échec de création d'intent de paiement (avec error, user_id, amount, reference, payment_method, phone_number)
- **Error:** Échec de création d'intent de paiement MVola (avec user_id, error, phone_number, provider, reference)

---

## Sécurité

- L'endpoint nécessite une authentification (selon la configuration de sécurité)
- Les données sensibles (numéros de téléphone, montants) sont loggées de manière sécurisée
- Les erreurs ne révèlent pas d'informations sensibles sur la structure interne du système

---

## Dépannage

### Erreur: "Order not found with reference"

**Cause:** La référence de commande fournie n'existe pas dans le système.

**Solution:** Vérifiez que la référence de commande est correcte et que la commande existe.

### Erreur: "Order owner not found"

**Cause:** La commande existe mais n'a pas de propriétaire associé.

**Solution:** Vérifiez que la commande a un propriétaire valide avant de créer l'intent de paiement.

### Erreur: "Phone number not found"

**Cause:** Le numéro de téléphone n'est pas fourni dans la requête.

**Solution:** Assurez-vous d'inclure le champ `phoneNumber` dans la requête.

### Erreur: "Failed to create payment intent"

**Cause:** Une erreur s'est produite lors de la création de l'intent de paiement (erreur MVola, validation, etc.).

**Solution:** Vérifiez les logs pour plus de détails sur l'erreur spécifique.

---

## Version

- **Version:** 1.0.0
- **Dernière mise à jour:** 2024
- **Auteur:** Joy Pharma Development Team

