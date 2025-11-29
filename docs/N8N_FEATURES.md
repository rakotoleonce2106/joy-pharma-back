# Fonctionnalités n8n - Joy Pharma

## Vue d'ensemble

Cette application intègre n8n pour automatiser les communications et workflows. Voici toutes les fonctionnalités disponibles.

## 📋 Liste complète des fonctionnalités

### 1. Notifications Push (FCM)

**Description** : Envoi de notifications push aux applications mobiles via Firebase Cloud Messaging (FCM).

**Utilisation** :
- Automatique lors de la création/mise à jour de commandes
- Automatique lors d'alertes SOS
- Manuelle via `NotificationService::sendPushNotification()`

**Configuration requise** :
- Token FCM stocké dans `User.fcmToken`
- Configuration Firebase dans n8n
- Workflow n8n configuré pour `/webhook/push-notification`

**Exemples d'utilisation** :
- Notification de nouvelle commande
- Notification de changement de statut
- Notification d'urgence (SOS)
- Notification de promotion

---

### 2. Envoi d'Emails

**Description** : Envoi d'emails transactionnels et marketing via n8n.

**Utilisation** :
- Automatique pour les commandes livrées
- Automatique pour les alertes SOS (administrateurs)
- Automatique pour les nouvelles commandes (propriétaires de magasins)
- Manuelle via `NotificationService::sendEmailNotification()`

**Configuration requise** :
- Workflow n8n configuré pour `/webhook/send-email`
- Service email configuré (Gmail, SMTP, SendGrid, etc.)

**Types d'emails** :
- Confirmation de commande
- Notification de livraison
- Alertes d'urgence
- Promotions et offres spéciales
- Rappels et notifications système

---

### 3. Envoi de SMS

**Description** : Envoi de SMS via différents fournisseurs (Twilio, Vonage, etc.).

**Utilisation** :
- Manuelle via `N8nService::sendSMS()`
- Intégrée dans des workflows n8n personnalisés

**Configuration requise** :
- Workflow n8n configuré pour `/webhook/send-sms`
- Compte fournisseur SMS (Twilio, etc.)

**Cas d'usage** :
- Notifications de livraison
- Codes de vérification
- Alertes importantes

---

### 4. Événements automatiques

**Description** : Déclenchement automatique de workflows n8n lors d'événements importants.

**Événements disponibles** :

#### `order.created`
Déclenché lors de la création d'une nouvelle commande.

**Payload** :
```json
{
  "eventType": "order.created",
  "payload": {
    "orderId": 123,
    "orderReference": "ORD-2024-000001",
    "customerId": 456,
    "customerEmail": "customer@example.com",
    "totalAmount": 50000,
    "status": "pending"
  },
  "timestamp": "2024-01-15T10:30:00+00:00"
}
```

#### `order.status_changed`
Déclenché lors du changement de statut d'une commande.

**Payload** :
```json
{
  "eventType": "order.status_changed",
  "payload": {
    "orderId": 123,
    "orderReference": "ORD-2024-000001",
    "oldStatus": "pending",
    "newStatus": "confirmed",
    "customerId": 456
  },
  "timestamp": "2024-01-15T10:30:00+00:00"
}
```

#### `sos.created`
Déclenché lors de la création d'une alerte SOS.

**Payload** :
```json
{
  "eventType": "sos.created",
  "payload": {
    "sosId": 789,
    "deliveryPersonId": 101,
    "deliveryPersonName": "John Doe",
    "latitude": "-18.8792",
    "longitude": "47.5079",
    "orderId": 123,
    "notes": "Besoin d'assistance urgente"
  },
  "timestamp": "2024-01-15T10:30:00+00:00"
}
```

---

### 5. Webhooks personnalisés

**Description** : Déclenchement de webhooks n8n personnalisés pour des workflows spécifiques.

**Utilisation** :
```php
$n8nService->triggerWebhook('my-custom-webhook', [
    'data1' => 'value1',
    'data2' => 'value2',
]);
```

**Cas d'usage** :
- Intégrations avec systèmes externes
- Workflows métier personnalisés
- Automatisations spécifiques

---

### 6. Notifications in-app

**Description** : Système de notifications intégré à l'application (stockées en base de données).

**Fonctionnalités** :
- Création automatique lors d'événements
- API pour récupérer les notifications
- Marquage comme lu/non lu
- Compteur de notifications non lues

**Types de notifications** :
- `order_new` : Nouvelle commande
- `order_status` : Changement de statut
- `system` : Notifications système
- `promotion` : Promotions et offres
- `emergency` : Alertes d'urgence

---

## 🔧 Services PHP disponibles

### N8nService

Service de base pour communiquer avec n8n.

**Méthodes** :
- `triggerWebhook(string $webhookPath, array $data, array $options = [])` : Déclenche un webhook
- `sendPushNotification(string $fcmToken, string $title, string $body, array $data = [])` : Envoie une notification push
- `sendEmail(string $to, string $subject, string $htmlBody, ?string $textBody = null, array $attachments = [])` : Envoie un email
- `sendSMS(string $phoneNumber, string $message)` : Envoie un SMS
- `triggerEvent(string $eventType, array $payload)` : Déclenche un événement

### NotificationService

Service de haut niveau pour gérer les notifications complètes.

**Méthodes** :
- `sendNotification(User $user, string $title, string $message, string $type, array $data, array $options)` : Notification complète
- `sendPushNotification(User $user, string $title, string $body, array $data = [])` : Notification push uniquement
- `sendEmailNotification(User $user, string $subject, string $htmlBody, array $data = [])` : Email uniquement
- `sendOrderStatusNotification(User $user, string $orderReference, string $status, array $options = [])` : Notification de statut de commande
- `sendNewOrderNotification(User $storeOwner, string $orderReference, float $totalAmount, array $options = [])` : Notification de nouvelle commande
- `sendEmergencyNotification(User $admin, User $deliveryPerson, array $location, array $options = [])` : Notification d'urgence
- `sendPromotionNotification(User $user, string $promotionTitle, string $promotionDescription, array $options = [])` : Notification de promotion

---

## 📱 Intégration mobile

### Enregistrement du token FCM

Les applications mobiles doivent enregistrer le token FCM via l'API :

```http
PUT /api/users/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "fcmToken": "token-from-firebase-sdk"
}
```

### Réception des notifications

Les notifications push sont envoyées automatiquement lorsque :
- Une commande est créée
- Le statut d'une commande change
- Une alerte SOS est déclenchée
- Une promotion est publiée

---

## 🎯 Cas d'usage par rôle

### Client (Customer)

**Notifications reçues** :
- ✅ Confirmation de commande (push + in-app)
- ✅ Changement de statut de commande (push + in-app)
- ✅ Commande livrée (push + email + in-app)
- ✅ Promotions (push + email + in-app)

### Propriétaire de magasin (Store Owner)

**Notifications reçues** :
- ✅ Nouvelle commande (push + email + in-app)
- ✅ Commande annulée (push + in-app)
- ✅ Problèmes signalés (push + email + in-app)

### Livreur (Delivery Person)

**Notifications reçues** :
- ✅ Nouvelle commande assignée (push + in-app)
- ✅ Instructions de livraison (push + in-app)
- ✅ Alertes SOS (push + email + in-app)

### Administrateur (Admin)

**Notifications reçues** :
- ✅ Alertes SOS (push + email + in-app)
- ✅ Problèmes critiques (push + email + in-app)
- ✅ Rapports et statistiques (email)

---

## 🚀 Démarrage rapide

### 1. Démarrer n8n

```bash
docker compose up -d n8n
```

### 2. Accéder à n8n

Ouvrez http://localhost:5678 et connectez-vous.

### 3. Créer votre premier workflow

1. Créez un nouveau workflow
2. Ajoutez un nœud **Webhook** (POST)
3. Configurez le path : `/webhook/push-notification`
4. Ajoutez un nœud **HTTP Request** pour FCM
5. Configurez selon votre projet Firebase

### 4. Tester

Utilisez l'API pour créer une commande et vérifiez que le workflow se déclenche.

---

## 📊 Monitoring et logs

Tous les appels à n8n sont loggés dans les logs Symfony :
- Succès : niveau INFO
- Erreurs : niveau ERROR

Consultez les logs n8n dans l'interface n8n pour plus de détails.

---

## 🔒 Sécurité

- Les webhooks n8n sont accessibles uniquement depuis le réseau Docker
- Changez les identifiants n8n en production
- Utilisez HTTPS pour n8n en production
- Validez les données dans vos workflows n8n

---

## 📚 Documentation supplémentaire

- [Guide d'intégration complet](./N8N_INTEGRATION.md)
- [Documentation n8n officielle](https://docs.n8n.io/)
- [Documentation Firebase Cloud Messaging](https://firebase.google.com/docs/cloud-messaging)

