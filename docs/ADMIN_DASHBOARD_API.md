# API Dashboard Admin

## Vue d'ensemble

L'API `/api/dashboard` a été simplifiée pour retourner uniquement les compteurs et les données financières. Les données de carte (map) et les commandes récentes (recentOrders) ont été retirées et sont maintenant disponibles via des endpoints dédiés.

---

## Endpoint Dashboard Principal

### GET `/api/dashboard`

Retourne les statistiques générales et financières du dashboard.

**Authentification :** JWT + `ROLE_ADMIN`

**Réponse :**

```json
{
  "counters": {
    "orders": {
      "total": 1200,
      "pending": 45,
      "completed": 980
    },
    "users": {
      "total": 5600,
      "deliverers": {
        "total": 120,
        "online": 18
      }
    },
    "inventory": {
      "products": 320,
      "stores": 45
    }
  },
  "financials": {
    "totalRevenue": 1520000.0,
    "lastMonthRevenue": 82000.0,
    "todayOrders": 34,
    "todayRevenue": 175000.0
  }
}
```

---

## Endpoints API pour les Données Complémentaires

### 1. Commandes Récentes

#### GET `/api/admin/orders`

Récupère la liste des commandes avec possibilité de filtrage et tri.

**Authentification :** JWT + `ROLE_ADMIN`

**Paramètres de requête :**

| Paramètre | Type | Description | Valeurs |
|-----------|------|-------------|---------|
| `page` | integer | Numéro de page | Défaut: 1 |
| `itemsPerPage` | integer | Nombre d'éléments par page | Défaut: 30 |
| `order[createdAt]` | string | Tri par date de création | `desc` (récent) ou `asc` |
| `order[id]` | string | Tri par ID | `desc` ou `asc` |
| `status` | string | Filtrer par statut | `pending`, `confirmed`, `processing`, `shipped`, `delivered`, `cancelled` |

**Exemples :**

```bash
# 10 commandes les plus récentes
GET /api/admin/orders?itemsPerPage=10&order[createdAt]=desc

# Commandes récentes en attente
GET /api/admin/orders?itemsPerPage=10&order[createdAt]=desc&status=pending

# Commandes récentes livrées
GET /api/admin/orders?itemsPerPage=10&order[createdAt]=desc&status=delivered
```

**Réponse :**

```json
{
  "member": [
    {
      "id": 450,
      "reference": "ORD-2025-001",
      "status": "pending",
      "totalAmount": 45000,
      "createdAt": "2025-01-01T08:45:00+00:00",
      "owner": {
        "id": 10,
        "fullName": "Tahina Rakoto",
        "phone": "+261 34 12 345 67"
      },
      "location": {
        "id": 25,
        "address": "Lot II A 45, Ankorondrano, Antananarivo",
        "latitude": -18.8792,
        "longitude": 47.5079
      }
    }
  ],
  "totalItems": 1200,
  "view": {
    "first": "/api/admin/orders?page=1",
    "last": "/api/admin/orders?page=120",
    "next": "/api/admin/orders?page=2"
  }
}
```

---

### 2. Liste des Magasins

#### GET `/api/admin/stores`

Récupère la liste complète des magasins avec leurs informations de localisation.

**Authentification :** JWT + `ROLE_ADMIN`

**Paramètres de requête :**

| Paramètre | Type | Description | Valeurs |
|-----------|------|-------------|---------|
| `page` | integer | Numéro de page | Défaut: 1 |
| `itemsPerPage` | integer | Nombre d'éléments par page | Défaut: 30 |
| `status` | string | Filtrer par statut | `active`, `pending`, `suspended`, `inactive` |

**Exemples :**

```bash
# Tous les magasins actifs
GET /api/admin/stores?status=active

# Tous les magasins (avec pagination)
GET /api/admin/stores?page=1&itemsPerPage=50
```

**Réponse :**

```json
{
  "member": [
    {
      "id": 1,
      "name": "Pharmacie Centrale",
      "phone": "+261 20 22 123 45",
      "email": "contact@pharmacie-centrale.mg",
      "status": "active",
      "location": {
        "id": 5,
        "address": "Avenue de l'Indépendance, Antananarivo",
        "latitude": -18.9088,
        "longitude": 47.5173
      },
      "owner": {
        "id": 15,
        "fullName": "Jean Dupont",
        "email": "jean@pharmacie-centrale.mg"
      }
    }
  ],
  "totalItems": 45,
  "view": {
    "first": "/api/admin/stores?page=1",
    "last": "/api/admin/stores?page=2",
    "next": "/api/admin/stores?page=2"
  }
}
```

---

### 3. Liste des Livreurs

#### GET `/api/admin/users?type=delivers`

Récupère la liste des livreurs avec leurs informations.

**Authentification :** JWT + `ROLE_ADMIN`

**Paramètres de requête :**

| Paramètre | Type | Description | Valeurs |
|-----------|------|-------------|---------|
| `type` | string | Filtrer par type d'utilisateur | `all`, `delivers`, `stores`, `customers` |
| `role` | string | Filtrer par rôle spécifique | `ROLE_DELIVER`, `ROLE_STORE`, `ROLE_USER`, `ROLE_ADMIN` |
| `page` | integer | Numéro de page | Défaut: 1 |
| `itemsPerPage` | integer | Nombre d'éléments par page | Défaut: 30 |

**Exemples :**

```bash
# Tous les livreurs
GET /api/admin/users?type=delivers

# Tous les livreurs (alternative avec role)
GET /api/admin/users?role=ROLE_DELIVER

# Livreurs avec pagination
GET /api/admin/users?type=delivers&page=1&itemsPerPage=50
```

**Réponse :**

```json
{
  "member": [
    {
      "id": 99,
      "fullName": "Toky Randria",
      "email": "toky@joy.pharma",
      "phone": "+261 34 98 765 43",
      "roles": ["ROLE_DELIVER"],
      "isActive": true,
      "createdAt": "2024-06-15T10:30:00+00:00",
      "avatar": {
        "id": 12,
        "contentUrl": "/media/avatars/toky.jpg"
      }
    }
  ],
  "totalItems": 120,
  "view": {
    "first": "/api/admin/users?type=delivers&page=1",
    "last": "/api/admin/users?type=delivers&page=4",
    "next": "/api/admin/users?type=delivers&page=2"
  }
}
```

---

### 4. Localisation des Livreurs en Ligne (Optionnel)

Pour obtenir la position en temps réel des livreurs, vous pouvez utiliser l'endpoint de localisation.

**Note :** Un livreur est considéré "en ligne" si sa position a été mise à jour dans les 15 dernières minutes.

**Endpoint :** À implémenter si nécessaire via un endpoint dédié `/api/admin/deliverers/online`

---

## Comparaison Avant/Après

### Avant

```bash
GET /api/dashboard
```

Retournait : `counters`, `financials`, `map` (avec stores, deliverers, orders), `lists` (avec recentOrders, availableOrders)

### Après

```bash
# Dashboard simplifié
GET /api/dashboard

# Commandes récentes séparées
GET /api/admin/orders?itemsPerPage=10&order[createdAt]=desc

# Liste des magasins
GET /api/admin/stores

# Liste des livreurs
GET /api/admin/users?type=delivers
```

---

## Avantages de cette Architecture

1. **Séparation des préoccupations** : Chaque endpoint a une responsabilité claire
2. **Performance** : Chargement à la demande plutôt que tout en une seule fois
3. **Flexibilité** : Possibilité de filtrer, trier et paginer indépendamment
4. **Réutilisabilité** : Les mêmes endpoints peuvent être utilisés dans d'autres contextes
5. **Évolutivité** : Plus facile d'ajouter de nouveaux filtres ou paramètres

---

## Migration Frontend

Si vous avez déjà du code frontend qui utilise l'ancienne API, voici comment migrer :

### Ancienne approche

```javascript
// Une seule requête pour tout
const response = await fetch('/api/dashboard');
const { counters, financials, map, lists } = await response.json();

// Utilisation
const stores = map.stores;
const deliverers = map.deliverers.items;
const recentOrders = lists.recentOrders;
```

### Nouvelle approche

```javascript
// Requêtes séparées selon les besoins
const dashboardResponse = await fetch('/api/dashboard');
const { counters, financials } = await dashboardResponse.json();

// Charger les commandes récentes seulement si nécessaire
const ordersResponse = await fetch('/api/admin/orders?itemsPerPage=10&order[createdAt]=desc');
const ordersData = await ordersResponse.json();
const recentOrders = ordersData['member'];

// Charger les magasins seulement si nécessaire
const storesResponse = await fetch('/api/admin/stores');
const storesData = await storesResponse.json();
const stores = storesData['member'];

// Charger les livreurs seulement si nécessaire
const deliverersResponse = await fetch('/api/admin/users?type=delivers');
const deliverersData = await deliverersResponse.json();
const deliverers = deliverersData['member'];
```

### Avec async/await parallèle

```javascript
// Charger plusieurs ressources en parallèle
const [dashboard, orders, stores, deliverers] = await Promise.all([
  fetch('/api/dashboard').then(r => r.json()),
  fetch('/api/admin/orders?itemsPerPage=10&order[createdAt]=desc').then(r => r.json()),
  fetch('/api/admin/stores').then(r => r.json()),
  fetch('/api/admin/users?type=delivers').then(r => r.json())
]);

const data = {
  counters: dashboard.counters,
  financials: dashboard.financials,
  recentOrders: orders['member'],
  stores: stores['member'],
  deliverers: deliverers['member']
};
```

---

## Support

Pour toute question ou problème concernant ces endpoints, veuillez consulter la documentation complète de l'API ou contacter l'équipe de développement.

