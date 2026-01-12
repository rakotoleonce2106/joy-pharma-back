# Documentation API : Notifications & Push (FCM)

## Vue d'ensemble

Cette documentation détaille l'intégration des notifications push (Firebase Cloud Messaging) et des notifications in-app pour les applications clientes (Mobile & Web).

L'architecture repose sur :
1.  **FCM (Firebase Cloud Messaging)** pour la délivrance des pushs.
2.  **Device Tokens** pour gérer les appareils multi-plateformes (iOS, Android, Web).
3.  **In-App Notifications** pour l'historique et le centre de notifications dans l'application.

---

## 🔐 Prérequis

Toutes les requêtes nécessitent un token JWT authentifié via le header `Authorization`.

```http
Authorization: Bearer VOTRE_TOKEN_JWT
```

---

## 📱 Gestion des Appareils (Device Tokens)

Pour recevoir des notifications push, l'application cliente doit enregistrer son token FCM auprès du backend.

### 1. Enregistrer un appareil (Register)

Appelez cet endpoint :
- Après le login de l'utilisateur.
- Au démarrage de l'app (pour mettre à jour le `lastUsedAt`).
- Lorsque le callback `onTokenRefresh` de Firebase est déclenché.

**Endpoint :** `POST /api/device-tokens/register`

**Corps de la requête (JSON) :**

```json
{
  "fcmToken": "dGVzdF9mY21fdG9rZW5fZXhhbXBsZV8xMjM0NTY3ODkw...",
  "platform": "android",
  "deviceName": "Samsung Galaxy S21",
  "appVersion": "1.0.4"
}
```

| Champ | Type | Description | Valeurs possibles | Requis |
|-------|------|-------------|-------------------|--------|
| `fcmToken` | String | Le token reçu du SDK Firebase | - | ✅ Oui |
| `platform` | String | La plateforme de l'appareil | `ios`, `android`, `web` | Non |
| `deviceName` | String | Nom lisible de l'appareil | Ex: "iPhone 13" | Non |
| `appVersion` | String | Version de l'application | Ex: "1.2.0" | Non |

**Réponse (200 OK) :**

```json
{
  "success": true,
  "message": "Device token registered successfully",
  "deviceToken": {
    "id": 12,
    "platform": "android",
    "deviceName": "Samsung Galaxy S21",
    "isActive": true,
    "createdAt": "2026-01-12T10:30:00+00:00"
  }
}
```

### 2. Désinscrire un appareil (Unregister)

Appelez cet endpoint lors de la **déconnexion (logout)** de l'utilisateur pour éviter qu'il ne reçoive des notifications sur cet appareil après s'être déconnecté.

**Endpoint :** `DELETE /api/device-tokens/unregister`

**Corps de la requête (JSON) :**

```json
{
  "fcmToken": "dGVzdF9mY21fdG9rZW5fZXhhbXBsZV8xMjM0NTY3ODkw..."
}
```

**Réponse (200 OK) :**

```json
{
  "success": true,
  "message": "Device token unregistered successfully"
}
```

### 3. Lister mes appareils

Permet à l'utilisateur de voir ses appareils actifs.

**Endpoint :** `GET /api/device-tokens`

**Réponse (200 OK) :**

```json
[
  {
    "id": 12,
    "platform": "android",
    "deviceName": "Samsung Galaxy S21",
    "appVersion": "1.0.4",
    "isActive": true,
    "lastUsedAt": "2026-01-12T10:30:00+00:00",
    "createdAt": "2025-12-20T08:00:00+00:00"
  },
  {
    "id": 15,
    "platform": "web",
    "deviceName": "Chrome on MacBook",
    "isActive": true,
    "lastUsedAt": "2026-01-10T14:20:00+00:00"
  }
]
```

---

## 🔔 Notifications In-App

Ces endpoints permettent de gérer le centre de notifications dans l'application (la liste des notifs, le compteur de non-lues, etc.).

### 1. Lister les notifications

Récupère l'historique des notifications de l'utilisateur.

**Endpoint :** `GET /api/notifications`

**Paramètres de requête (Query Params) :**
- `page` : Numéro de page (défaut: 1)
- `itemsPerPage` : Nombre d'éléments (défaut: 30)
- `isRead` : Filtrer par lu/non lu (`true` ou `false`)

**Exemple :** `GET /api/notifications?page=1&isRead=false`

**Réponse (200 OK) :**

```json
{
  "hydra:member": [
    {
      "@id": "/api/notifications/101",
      "@type": "Notification",
      "id": 101,
      "title": "Commande Expédiée",
      "message": "Votre commande #12345 est en route.",
      "type": "order_status",
      "isRead": false,
      "createdAt": "2026-01-12T10:35:00+00:00",
      "data": {
        "orderId": 12345,
        "trackingUrl": "..."
      }
    }
  ],
  "hydra:totalItems": 15
}
```

### 2. Compteur de non-lues

Utile pour afficher un badge sur l'icône de cloche 🔔.

**Endpoint :** `GET /api/notifications/unread-count`

**Réponse (200 OK) :**

```json
{
  "count": 3
}
```

### 3. Marquer une notification comme lue

**Endpoint :** `PUT /api/notifications/{id}/read`

**Réponse (200 OK) :** Retourne l'objet notification mis à jour.

### 4. Tout marquer comme lu

**Endpoint :** `PUT /api/notifications/read-all`

**Réponse (200 OK) :**

```json
{
  "success": true,
  "count": 5
}
```

---

## 🛠️ Guides d'Intégration Client

### Intégration React Native (Mobile)

1.  **Configuration**
    - Installez les paquets :
      ```bash
      npm install @react-native-firebase/app @react-native-firebase/messaging
      ```
    - Configurez les projets Android et iOS (fichier `google-services.json` / `GoogleService-Info.plist`) via la console Firebase.
    - Pour iOS, assurez-vous d'avoir activé les capacités "Push Notifications" et "Background Modes" dans Xcode.

2.  **Initialisation & Enregistrement**

    ```javascript
    import messaging from '@react-native-firebase/messaging';
    import { Platform } from 'react-native';

    async function requestUserPermission() {
      const authStatus = await messaging().requestPermission();
      const enabled =
        authStatus === messaging.AuthorizationStatus.AUTHORIZED ||
        authStatus === messaging.AuthorizationStatus.PROVISIONAL;

      if (enabled) {
        getFcmToken();
      }
    }

    async function getFcmToken() {
      try {
        const token = await messaging().getToken();
        if (token) {
           // Envoyer au backend (POST /api/device-tokens/register)
           await api.post('/api/device-tokens/register', {
             fcmToken: token,
             platform: Platform.OS, // 'ios' ou 'android'
             deviceName: 'My Device' // Optionnel
           });
        }
      } catch (error) {
        console.log('FCM Token Error:', error);
      }
    }

    // Appeler au démarrage
    useEffect(() => {
      requestUserPermission();

      // Écouter le rafraîchissement du token
      const unsubscribe = messaging().onTokenRefresh(token => {
         // Mise à jour backend
         api.post('/api/device-tokens/register', { fcmToken: token, ... });
      });

      return unsubscribe;
    }, []);
    ```

3.  **Déconnexion**
    - Lors du logout, n'oubliez pas d'appeler `DELETE /api/device-tokens/unregister` avec le token actuel avant de détruire la session.

### Intégration Web (React / Next.js)

1.  **Configuration**
    - Installez `firebase`.
    - Ajoutez votre configuration Firebase Web.

2.  **Service Worker**
    - Créez un fichier `firebase-messaging-sw.js` dans votre dossier `public`.

3.  **Récupération du Token**

    ```javascript
    import { getMessaging, getToken } from "firebase/messaging";

    const messaging = getMessaging();

    getToken(messaging, { vapidKey: "VOTRE_CLÉ_VAPID_PUBLIQUE" }).then((currentToken) => {
      if (currentToken) {
        // Envoyer au backend
        fetch('/api/device-tokens/register', {
            method: 'POST',
            body: JSON.stringify({
                fcmToken: currentToken,
                platform: 'web',
                deviceName: navigator.userAgent
            })
            // ... headers Auth
        });
      } else {
        console.log('No registration token available.');
      }
    }).catch((err) => {
      console.log('An error occurred while retrieving token. ', err);
    });
    ```

---

## ⚠️ Résolution de problèmes fréquents

1.  **Erreur 401 Unauthorized** : Vérifiez que votre token JWT est valide et bien présent dans le header `Authorization`.
2.  **Les notifications n'arrivent pas** :
    - Vérifiez que le `fcmToken` stocké en base correspond bien à l'appareil testé.
    - Vérifiez si l'appareil n'est pas en mode "Ne pas déranger".
    - Sur iOS, vérifiez que les certificats APNs sont bien configurés dans la console Firebase.
