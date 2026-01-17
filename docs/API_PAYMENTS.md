# Documentation API : Paiements (Mvola & MPGS)

## Vue d'ensemble

Cette documentation explique comment initier et vérifier des paiements via l'API Joy Pharma. Les méthodes de paiement actuellement supportées sont **Mvola** (via dahromy/mvola-bundle) et **MPGS** (Mastercard Payment Gateway Services).

---

## 💳 Créer une Intention de Paiement

### Endpoint
- **POST** `/api/create-payment-intent`

### Description
Initie une transaction de paiement pour une commande existante. Cet endpoint génère un `transactionId` et, selon la méthode, un `clientSecret` ou un `sessionId`.

### Authentification
Nécessite un token JWT valide (`ROLE_USER`).

### Paramètres de la requête (JSON)

| Champ | Type | Requis | Description |
| :--- | :--- | :--- | :--- |
| `method` | `string` | **Oui** | La méthode de paiement. Valeurs acceptées : `"mvola"`, `"mpgs"`. |
| `order` | `string` | **Oui** | L'IRI de la commande (ex: `/api/orders/123`). |
| `reference` | `string` | **Oui** | La référence de la commande (ex: `"CMD-2025-ABCDE"`). |
| `phoneNumber` | `string` | **Conditionnel** | Requis pour **Mvola**. Le numéro de téléphone du payeur. |

**Note :** Il est fortement recommandé de fournir à la fois l'IRI `order` et la `reference`. L'API validera que la référence fournie correspond bien à celle de la commande.

### Exemples

#### 🔸 Mvola (Mobile Money)
```json
{
  "method": "mvola",
  "order": "/api/orders/123",
  "reference": "CMD-2025-001",
  "phoneNumber": "0340012345"
}
```

#### 🔸 MPGS (Carte Bancaire)
```json
{
  "method": "mpgs",
  "order": "/api/orders/45",
  "reference": "CMD-2025-045"
}
```

### Réponse (201 Created)
```json
{
  "id": "TXN-2026-123456",
  "clientSecret": "sec_...",
  "status": "pending",
  "provider": "Mvola",
  "reference": "CMD-2025-001",
  "sessionId": "SESSION0001...", // (MPGS uniquement)
  "successIndicator": "abc..."    // (MPGS uniquement)
}
```

---

## 🔍 Vérifier un Paiement

### Endpoint
- **GET** `/api/verify-payment/{orderReference}`

### Description
Permet de vérifier le statut actuel d'un paiement associé à une commande.

### Exemple
```bash
curl -X GET "https://votre-api.com/api/verify-payment/CMD-2025-001" \
  -H "Authorization: Bearer VOTRE_TOKEN"
```

### Réponse
Retourne l'objet `Payment` correspondant avec son statut mis à jour.

---

## 🔄 Flux de Paiement

### Flux Mvola
1. Le client appelle `/api/create-payment-intent` avec son numéro de téléphone.
2. Le serveur initie la demande auprès de Mvola.
3. Le client reçoit une notification STK Push sur son téléphone pour valider le paiement.
4. Une fois validé, le statut de la commande passera à `processing` ou `completed` via un webhook ou une vérification manuelle.

### Flux MPGS
1. Le client appelle `/api/create-payment-intent`.
2. Le serveur retourne un `sessionId`.
3. Le client utilise le SDK MPGS (Frontend) avec ce `sessionId` pour afficher le formulaire de paiement sécurisé.
4. Après le paiement, le client est redirigé vers l'URL de retour, et le serveur reçoit la confirmation via `MPGSWebhook`.

---

## 🛠️ Gestion des Erreurs

| Code | Message | Cause |
| :--- | :--- | :--- |
| `400` | `Invalid payment method...` | Méthode non supportée (ex: Stripe n'est pas encore activé). |
| `404` | `Order not found...` | La référence ou l'IRI de commande est invalide. |
| `404` | `Phone number not found` | Tentative de paiement Mvola sans numéro de téléphone. |
| `400` | `Failed to create payment intent...` | Erreur de communication avec le fournisseur de paiement. |
