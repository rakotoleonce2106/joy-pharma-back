# 📧 Configuration n8n - Joy Pharma Backend

## Vue d'ensemble

Cette documentation explique comment configurer n8n pour l'intégration avec l'API Joy Pharma. n8n est utilisé pour gérer tous les envois d'emails, notifications push, et potentiellement les SMS.

## 🏗️ Architecture

### Flux de données

```
API Symfony → N8nService → Webhook HTTP → n8n Workflow → Service externe
```

### Webhooks utilisés

| Webhook | Description | Statut |
|---------|-------------|--------|
| `send-email` | Envoi d'emails (vérification, réinitialisation) | ✅ Actif |
| `push-notification` | Notifications push Firebase | ✅ Actif |
| `send-sms` | Envoi de SMS | 🔄 Configurable |
| `event` | Événements génériques | 🔄 Configurable |

## ⚙️ Configuration de base

### 1. Installation et démarrage de n8n

#### Via Docker (recommandé)

```bash
# Démarrer n8n avec Docker
docker run -d \
  --name n8n \
  -p 5678:5678 \
  -v n8n_data:/home/node/.n8n \
  -e N8N_BASIC_AUTH_ACTIVE=true \
  -e N8N_BASIC_AUTH_USER=admin \
  -e N8N_BASIC_AUTH_PASSWORD=joypharma2024 \
  n8nio/n8n:latest
```

#### Accès à n8n

- **URL** : http://localhost:5678
- **Utilisateur** : admin
- **Mot de passe** : joypharma2024

### 2. Configuration dans l'API

#### Variables d'environnement

Ajoutez à votre fichier `.env` :

```env
# Configuration n8n
N8N_WEBHOOK_URL=http://n8n:5678/

# En développement local
# N8N_WEBHOOK_URL=http://localhost:5678/
```

## 📧 Webhook : Envoi d'emails (`send-email`)

### Payload reçu

```json
{
  "to": "user@example.com",
  "subject": "Sujet de l'email",
  "htmlBody": "<h1>Contenu HTML</h1>",
  "textBody": "Contenu texte (optionnel)",
  "attachments": []
}
```

### Workflows n8n nécessaires

#### 1. Email de vérification d'inscription

**Déclencheur** : Webhook `send-email` avec sujet contenant "vérification"

**Actions** :
1. Recevoir le webhook
2. Envoyer email via Gmail/SMTP
3. Logger le succès/échec

#### 2. Email de réinitialisation de mot de passe

**Déclencheur** : Webhook `send-email` avec sujet contenant "réinitialisation"

**Actions** :
1. Recevoir le webhook
2. Envoyer email via Gmail/SMTP
3. Logger le succès/échec

### Configuration Gmail/SMTP

#### Créer un workflow d'envoi d'email

```json
{
  "name": "Joy Pharma Email Service",
  "nodes": [
    {
      "parameters": {
        "httpMethod": "POST",
        "path": "send-email",
        "responseMode": "responseNode",
        "options": {}
      },
      "name": "Webhook",
      "type": "n8n-nodes-base.webhook",
      "position": [240, 300]
    },
    {
      "parameters": {
        "toEmail": "={{ $json.to }}",
        "subject": "={{ $json.subject }}",
        "html": "={{ $json.htmlBody }}",
        "text": "={{ $json.textBody }}",
        "options": {}
      },
      "name": "Send Email",
      "type": "n8n-nodes-base.gmail",
      "position": [460, 300]
    }
  ],
  "connections": {
    "Webhook": {
      "main": [
        [
          {
            "node": "Send Email",
            "type": "main",
            "index": 0
          }
        ]
      ]
    }
  }
}
```

#### Configuration Gmail

1. **Activer l'authentification 2FA** sur votre compte Gmail
2. **Générer un mot de passe d'application** :
   - Aller dans Paramètres Gmail → Sécurité
   - Activer la vérification en 2 étapes
   - Générer un mot de passe d'application pour "Mail"

3. **Configuration dans n8n** :
   - Service : Gmail
   - Email : votre-email@gmail.com
   - Mot de passe : [mot de passe d'application]

## 📱 Webhook : SMS (`send-sms`) - Optionnel

### Payload reçu

```json
{
  "phoneNumber": "+261340000000",
  "message": "Votre commande a été livrée"
}
```

### Configuration SMS (Twilio exemple)

#### 1. Créer un compte Twilio

1. S'inscrire sur https://www.twilio.com/
2. Obtenir un numéro de téléphone virtuel
3. Récupérer SID et Token d'API

#### 2. Workflow n8n pour SMS

```json
{
  "name": "Joy Pharma SMS Service",
  "nodes": [
    {
      "parameters": {
        "httpMethod": "POST",
        "path": "send-sms",
        "responseMode": "responseNode",
        "options": {}
      },
      "name": "Webhook",
      "type": "n8n-nodes-base.webhook",
      "position": [240, 300]
    },
    {
      "parameters": {
        "accountSid": "YOUR_TWILIO_SID",
        "authToken": "YOUR_TWILIO_TOKEN",
        "from": "+1234567890",
        "to": "={{ $json.phoneNumber }}",
        "message": "={{ $json.message }}"
      },
      "name": "Twilio",
      "type": "n8n-nodes-base.twilio",
      "position": [460, 300]
    }
  ],
  "connections": {
    "Webhook": {
      "main": [
        [
          {
            "node": "Twilio",
            "type": "main",
            "index": 0
          }
        ]
      ]
    }
  }
}
```

## 🎯 Webhook : Événements (`event`) - Optionnel

### Payload reçu

```json
{
  "eventType": "order.created",
  "payload": {
    "orderId": 123,
    "userId": 456,
    "amount": 50000
  },
  "timestamp": "2024-01-15T10:30:00+00:00"
}
```

### Utilisation

Ce webhook peut être utilisé pour :
- Logging d'événements
- Intégrations tierces (Slack, Discord)
- Analytics
- Automatisations métier

## 🔧 Configuration avancée

### Logging et monitoring

#### Ajouter un nœud de logging

```json
{
  "parameters": {
    "values": {
      "boolean": [
        {
          "name": "Success",
          "value": true
        }
      ],
      "string": [
        {
          "name": "Event Type",
          "value": "={{ $json.eventType || 'email' }}"
        },
        {
          "name": "Recipient",
          "value": "={{ $json.to || $json.fcmToken || $json.phoneNumber }}"
        },
        {
          "name": "Timestamp",
          "value": "={{ new Date().toISOString() }}"
        }
      ]
    }
  },
  "name": "Log Success",
  "type": "n8n-nodes-base.set",
  "position": [680, 300]
}
```

### Gestion des erreurs

#### Workflow avec gestion d'erreur

```json
{
  "nodes": [
    // ... vos nœuds existants ...
    {
      "parameters": {
        "httpCode": 500,
        "responseBody": "{\"error\": \"Failed to send notification\"}",
        "options": {}
      },
      "name": "Error Response",
      "type": "n8n-nodes-base.httpRequest",
      "position": [680, 500]
    }
  ],
  "connections": {
    // Connexion en cas d'erreur
    "Send Email": {
      "main": [
        [
          {
            "node": "Error Response",
            "type": "main",
            "index": 1
          }
        ]
      ]
    }
  }
}
```

## 🧪 Tests et débogage

### Tester un webhook

```bash
# Tester l'envoi d'email
curl -X POST "http://localhost:5678/webhook/send-email" \
  -H "Content-Type: application/json" \
  -d '{
    "to": "test@example.com",
    "subject": "Test Email",
    "htmlBody": "<h1>Test</h1>",
    "textBody": "Test"
  }'


### Logs n8n

Les logs sont disponibles dans l'interface n8n :
- Onglet "Executions" pour voir les exécutions passées
- Onglet "Logs" pour les erreurs détaillées

## 🚀 Déploiement en production

### Configuration Docker Compose

```yaml
version: '3.8'
services:
  n8n:
    image: n8nio/n8n:latest
    restart: unless-stopped
    ports:
      - "5678:5678"
    environment:
      - N8N_BASIC_AUTH_ACTIVE=true
      - N8N_BASIC_AUTH_USER=${N8N_USER}
      - N8N_BASIC_AUTH_PASSWORD=${N8N_PASSWORD}
      - N8N_ENCRYPTION_KEY=${N8N_ENCRYPTION_KEY}
    volumes:
      - n8n_data:/home/node/.n8n
    networks:
      - joy-pharma-network

volumes:
  n8n_data:

networks:
  joy-pharma-network:
    external: true
```

### Variables d'environnement

```env
# n8n
N8N_USER=admin
N8N_PASSWORD=secure_password_here
N8N_ENCRYPTION_KEY=your_32_character_encryption_key

# API
N8N_WEBHOOK_URL=https://n8n.joy-pharma.com/
```

### Sécurité

1. **Changement des credentials par défaut**
2. **Utilisation de HTTPS en production**
3. **Sauvegarde régulière des workflows**
4. **Monitoring des logs et alertes**

## 📞 Support et dépannage

### Problèmes courants

#### Email non envoyé
- Vérifier la configuration Gmail/SMTP
- Contrôler les logs n8n
- Tester avec un email simple

#### Notifications push non reçues
- Vérifier le token FCM
- Contrôler la configuration Firebase
- Tester avec Firebase Console

#### Webhook non déclenché
- Vérifier l'URL dans les variables d'environnement
- Contrôler la connectivité réseau
- Vérifier les logs de l'API Symfony

### Ressources utiles

- [Documentation n8n](https://docs.n8n.io/)
- [Guide Firebase Cloud Messaging](https://firebase.google.com/docs/cloud-messaging)
- [Documentation Twilio](https://www.twilio.com/docs)

---

## 📋 Checklist de configuration

- [ ] n8n installé et accessible
- [ ] Webhook `send-email` configuré avec Gmail/SMTP
- [ ] Webhook `push-notification` configuré avec Firebase
- [ ] Variables d'environnement configurées dans l'API
- [ ] Tests de tous les webhooks effectués
- [ ] Logs et monitoring configurés
- [ ] Sauvegarde des workflows effectuée

**Version :** 1.0.0
**Dernière mise à jour :** Janvier 2026
**Équipe :** Joy Pharma