# Intégration n8n - Guide Complet

Ce document explique comment utiliser n8n pour automatiser les notifications push, emails, SMS et autres workflows dans l'application Joy Pharma.

## Table des matières

1. [Configuration](#configuration)
2. [Fonctionnalités disponibles](#fonctionnalités-disponibles)
3. [Webhooks n8n](#webhooks-n8n)
4. [Exemples de workflows](#exemples-de-workflows)
5. [Configuration des notifications push (FCM)](#configuration-des-notifications-push-fcm)
6. [Configuration des emails](#configuration-des-emails)
7. [Configuration SMS](#configuration-sms)

## Configuration

### 1. Démarrage de n8n

n8n est déjà configuré dans `compose.yaml`. Pour le démarrer :

```bash
docker compose up -d n8n
```

Accédez à l'interface n8n : http://localhost:5678

**Identifiants par défaut :**
- Utilisateur : `admin` (défini par `N8N_USER`)
- Mot de passe : `!ChangeMe!` (défini par `N8N_PASSWORD`)

⚠️ **Important** : Changez ces identifiants en production via les variables d'environnement.

### 2. Variables d'environnement

Ajoutez ces variables dans votre fichier `.env` :

```env
# n8n Configuration
N8N_WEBHOOK_URL=http://n8n:5678/
N8N_USER=admin
N8N_PASSWORD=!ChangeMe!
N8N_HOST=localhost
N8N_PORT=5678
N8N_PROTOCOL=http
```

## Fonctionnalités disponibles

### Services PHP

L'application fournit plusieurs services pour interagir avec n8n :

#### 1. N8nService

Service principal pour déclencher des webhooks n8n.

**Méthodes disponibles :**
- `triggerWebhook(string $webhookPath, array $data)` : Déclenche un webhook personnalisé
- `sendPushNotification(string $fcmToken, string $title, string $body, array $data)` : Envoie une notification push
- `sendEmail(string $to, string $subject, string $htmlBody, ?string $textBody, array $attachments)` : Envoie un email
- `sendSMS(string $phoneNumber, string $message)` : Envoie un SMS
- `triggerEvent(string $eventType, array $payload)` : Déclenche un événement générique

#### 2. NotificationService

Service de haut niveau pour gérer les notifications complètes (in-app + push + email).

**Méthodes disponibles :**
- `sendNotification(User $user, string $title, string $message, string $type, array $data, array $options)` : Notification complète
- `sendOrderStatusNotification(User $user, string $orderReference, string $status, array $options)` : Notification de changement de statut de commande
- `sendNewOrderNotification(User $storeOwner, string $orderReference, float $totalAmount, array $options)` : Notification de nouvelle commande
- `sendEmergencyNotification(User $admin, User $deliveryPerson, array $location, array $options)` : Notification d'urgence
- `sendPromotionNotification(User $user, string $promotionTitle, string $promotionDescription, array $options)` : Notification de promotion

### EventSubscribers automatiques

L'application déclenche automatiquement des workflows n8n lors de certains événements :

1. **OrderNotificationSubscriber** : 
   - Déclenché lors de la création d'une commande (`order.created`)
   - Déclenché lors du changement de statut d'une commande (`order.status_changed`)

2. **EmergencySOSSubscriber** :
   - Déclenché lors de la création d'une alerte SOS (`sos.created`)

## Webhooks n8n

### Structure des webhooks

Les webhooks sont accessibles à l'URL : `http://n8n:5678/webhook/{webhook-path}`

### Webhooks disponibles

#### 1. `/webhook/push-notification`

Envoie une notification push via FCM.

**Payload :**
```json
{
  "fcmToken": "string",
  "title": "string",
  "body": "string",
  "data": {
    "orderId": 123,
    "orderReference": "ORD-2024-000001"
  }
}
```

#### 2. `/webhook/send-email`

Envoie un email.

**Payload :**
```json
{
  "to": "user@example.com",
  "subject": "Sujet de l'email",
  "htmlBody": "<h1>Contenu HTML</h1>",
  "textBody": "Contenu texte (optionnel)",
  "attachments": []
}
```

#### 3. `/webhook/send-sms`

Envoie un SMS.

**Payload :**
```json
{
  "phoneNumber": "+261341234567",
  "message": "Votre commande a été livrée"
}
```

#### 4. `/webhook/event`

Déclenche un événement générique pour des workflows personnalisés.

**Payload :**
```json
{
  "eventType": "order.created",
  "payload": {
    "orderId": 123,
    "orderReference": "ORD-2024-000001",
    "customerId": 456,
    "totalAmount": 50000
  },
  "timestamp": "2024-01-15T10:30:00+00:00"
}
```

## Exemples de workflows

### Workflow 1 : Notification push pour nouvelle commande

1. Créez un nouveau workflow dans n8n
2. Ajoutez un nœud **Webhook** (méthode POST)
3. Configurez le path : `/webhook/event`
4. Ajoutez un nœud **IF** pour vérifier `eventType === "order.created"`
5. Ajoutez un nœud **HTTP Request** pour FCM :
   - URL : `https://fcm.googleapis.com/v1/projects/{project-id}/messages:send`
   - Méthode : POST
   - Headers : `Authorization: Bearer {access-token}`
   - Body :
   ```json
   {
     "message": {
       "token": "{{ $json.payload.customerFcmToken }}",
       "notification": {
         "title": "Nouvelle commande",
         "body": "Votre commande {{ $json.payload.orderReference }} a été créée"
       },
       "data": {
         "orderId": "{{ $json.payload.orderId }}",
         "orderReference": "{{ $json.payload.orderReference }}"
       }
     }
   }
   ```

### Workflow 2 : Email de confirmation de commande

1. Créez un nouveau workflow
2. Ajoutez un nœud **Webhook** (méthode POST)
3. Configurez le path : `/webhook/send-email`
4. Ajoutez un nœud **Gmail** ou **SMTP** :
   - Configurez votre compte email
   - To : `{{ $json.to }}`
   - Subject : `{{ $json.subject }}`
   - HTML Body : `{{ $json.htmlBody }}`

### Workflow 3 : SMS pour livraison

1. Créez un nouveau workflow
2. Ajoutez un nœud **Webhook** (méthode POST)
3. Configurez le path : `/webhook/send-sms`
4. Ajoutez un nœud **Twilio** ou votre fournisseur SMS :
   - To : `{{ $json.phoneNumber }}`
   - Message : `{{ $json.message }}`

## Configuration des notifications push (FCM)

### 1. Créer un projet Firebase

1. Allez sur [Firebase Console](https://console.firebase.google.com/)
2. Créez un nouveau projet
3. Ajoutez une application Android/iOS
4. Téléchargez le fichier de configuration

### 2. Configurer FCM dans n8n

1. Dans votre workflow n8n, ajoutez un nœud **HTTP Request**
2. Configurez :
   - URL : `https://fcm.googleapis.com/v1/projects/{project-id}/messages:send`
   - Méthode : POST
   - Headers :
     - `Authorization: Bearer {access-token}`
     - `Content-Type: application/json`
   - Body : Utilisez le format FCM v1

### 3. Obtenir un access token

Utilisez Google Cloud SDK ou OAuth2 pour obtenir un access token.

### 4. Stocker le token FCM dans l'application

Les utilisateurs doivent enregistrer leur token FCM via l'API :

```http
PUT /api/users/{id}
Content-Type: application/json

{
  "fcmToken": "token-from-firebase"
}
```

## Configuration des emails

### Option 1 : Gmail (via n8n)

1. Dans n8n, ajoutez un nœud **Gmail**
2. Connectez votre compte Gmail
3. Configurez les champs (To, Subject, Body)

### Option 2 : SMTP générique

1. Dans n8n, ajoutez un nœud **SMTP**
2. Configurez :
   - Host : `smtp.gmail.com` (ou votre serveur SMTP)
   - Port : `587`
   - User : votre email
   - Password : votre mot de passe
   - SSL : activé

### Option 3 : Services tiers

n8n supporte de nombreux services d'email :
- SendGrid
- Mailchimp
- Postmark
- AWS SES
- Et plus...

## Configuration SMS

### Option 1 : Twilio

1. Créez un compte [Twilio](https://www.twilio.com/)
2. Dans n8n, ajoutez un nœud **Twilio**
3. Configurez avec vos credentials Twilio

### Option 2 : Autres fournisseurs

n8n supporte de nombreux fournisseurs SMS :
- Vonage (Nexmo)
- MessageBird
- AWS SNS
- Et plus...

## Utilisation dans le code

### Exemple : Envoyer une notification manuellement

```php
use App\Service\NotificationService;
use App\Entity\User;

// Dans votre contrôleur ou service
public function __construct(
    private NotificationService $notificationService
) {}

public function notifyUser(User $user): void
{
    $this->notificationService->sendNotification(
        $user,
        'Titre de la notification',
        'Message de la notification',
        'system',
        ['customData' => 'value'],
        [
            'sendPush' => true,
            'sendEmail' => false
        ]
    );
}
```

### Exemple : Déclencher un webhook personnalisé

```php
use App\Service\N8nService;

public function __construct(
    private N8nService $n8nService
) {}

public function customWorkflow(): void
{
    $this->n8nService->triggerWebhook('my-custom-webhook', [
        'data1' => 'value1',
        'data2' => 'value2',
    ]);
}
```

## Bonnes pratiques

1. **Gestion des erreurs** : Les services gèrent automatiquement les erreurs et les loggent. Les workflows n8n ne doivent pas bloquer l'application.

2. **Performance** : Les appels à n8n sont asynchrones par défaut. Pour des opérations critiques, considérez l'utilisation d'une queue.

3. **Sécurité** : 
   - Changez les identifiants n8n en production
   - Utilisez HTTPS pour n8n en production
   - Sécurisez les webhooks avec des tokens d'authentification

4. **Monitoring** : Surveillez les logs n8n et les logs de l'application pour détecter les problèmes.

## Support

Pour plus d'informations sur n8n :
- [Documentation n8n](https://docs.n8n.io/)
- [Communauté n8n](https://community.n8n.io/)

