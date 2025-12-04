# 🚀 Guide de démarrage rapide - Intégration n8n

## ✅ Ce qui a été implémenté

### 1. Configuration Docker
- ✅ Service n8n ajouté dans `compose.yaml`
- ✅ Configuration avec PostgreSQL partagé
- ✅ Variables d'environnement configurées

### 2. Services PHP
- ✅ `N8nService` : Service de base pour communiquer avec n8n
- ✅ `NotificationService` : Service de haut niveau pour les notifications

### 3. Entités
- ✅ Champ `fcmToken` ajouté à l'entité `User`

### 4. EventSubscribers automatiques
- ✅ `OrderNotificationSubscriber` : Notifications automatiques pour les commandes
- ✅ `EmergencySOSSubscriber` : Notifications automatiques pour les alertes SOS

### 5. Documentation
- ✅ Guide complet dans `docs/N8N_INTEGRATION.md`
- ✅ Liste des fonctionnalités dans `docs/N8N_FEATURES.md`

## 📋 Étapes de démarrage

### Étape 1 : Créer la migration pour le champ fcmToken

```bash
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
```

### Étape 2 : Démarrer n8n

```bash
docker compose up -d n8n
```

### Étape 3 : Accéder à n8n

Ouvrez http://localhost:5678

**Identifiants par défaut** :
- Utilisateur : `admin`
- Mot de passe : `!ChangeMe!`

⚠️ **Changez ces identifiants en production !**

### Étape 4 : Configurer les variables d'environnement

Ajoutez dans votre fichier `.env` :

```env
N8N_WEBHOOK_URL=http://n8n:5678/
N8N_USER=admin
N8N_PASSWORD=!ChangeMe!
N8N_HOST=localhost
N8N_PORT=5678
```

### Étape 5 : Créer vos premiers workflows n8n

Consultez `docs/N8N_INTEGRATION.md` pour des exemples complets.

## 🎯 Fonctionnalités disponibles

### Notifications Push (FCM)
- Envoi automatique lors de la création/mise à jour de commandes
- Envoi automatique lors d'alertes SOS
- Envoi manuel via `NotificationService`

### Emails
- Envoi automatique pour les commandes livrées
- Envoi automatique pour les alertes SOS
- Envoi manuel via `NotificationService`

### SMS
- Envoi manuel via `N8nService::sendSMS()`
- Intégration avec Twilio, Vonage, etc.

### Événements automatiques
- `order.created` : Déclenché lors de la création d'une commande
- `order.status_changed` : Déclenché lors du changement de statut
- `sos.created` : Déclenché lors d'une alerte SOS

## 📚 Documentation

- **Guide complet** : `docs/N8N_INTEGRATION.md`
- **Liste des fonctionnalités** : `docs/N8N_FEATURES.md`
- **Documentation n8n** : https://docs.n8n.io/

## 🔧 Utilisation dans le code

### Exemple : Envoyer une notification

```php
use App\Service\NotificationService;

// Dans votre service ou contrôleur
public function __construct(
    private NotificationService $notificationService
) {}

public function notifyUser(User $user): void
{
    $this->notificationService->sendNotification(
        $user,
        'Titre',
        'Message',
        'system',
        ['customData' => 'value'],
        ['sendPush' => true, 'sendEmail' => false]
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
    $this->n8nService->triggerWebhook('my-webhook', [
        'data' => 'value'
    ]);
}
```

## 🎨 Workflows n8n recommandés

1. **Notification push pour nouvelle commande**
   - Webhook : `/webhook/event` (filtre `eventType === "order.created"`)
   - Action : Envoi FCM

2. **Email de confirmation de livraison**
   - Webhook : `/webhook/event` (filtre `eventType === "order.status_changed"` et `newStatus === "delivered"`)
   - Action : Envoi email

3. **Alerte SOS**
   - Webhook : `/webhook/event` (filtre `eventType === "sos.created"`)
   - Actions : Push + Email + SMS (optionnel)

## ⚠️ Notes importantes

1. **Sécurité** : Changez les identifiants n8n en production
2. **Performance** : Les appels à n8n sont asynchrones
3. **Erreurs** : Les erreurs sont loggées mais n'interrompent pas l'application
4. **Migration** : N'oubliez pas de créer et exécuter la migration pour `fcmToken`

## 🆘 Support

Pour plus d'aide :
- Documentation n8n : https://docs.n8n.io/
- Communauté n8n : https://community.n8n.io/
- Documentation de l'application : `docs/N8N_INTEGRATION.md`

