# Documentation des Fonctionnalités Admin - Joy-Pharma

## Table des matières

1. [Vue d'ensemble](#vue-densemble)
2. [Dashboard](#dashboard)
3. [Gestion des Utilisateurs](#gestion-des-utilisateurs)
4. [Gestion des Produits](#gestion-des-produits)
5. [Gestion des Catégories](#gestion-des-catégories)
6. [Gestion des Marques](#gestion-des-marques)
7. [Gestion des Fabricants](#gestion-des-fabricants)
8. [Gestion des Formes](#gestion-des-formes)
9. [Gestion des Unités](#gestion-des-unités)
10. [Gestion des Magasins](#gestion-des-magasins)
11. [Gestion des Commandes](#gestion-des-commandes)
12. [Gestion des Promotions](#gestion-des-promotions)
13. [Interface Propriétaire de Magasin](#interface-propriétaire-de-magasin)

---

## Vue d'ensemble

L'interface d'administration de Joy-Pharma permet de gérer tous les aspects de la plateforme pharmaceutique. Elle est accessible uniquement aux utilisateurs ayant le rôle `ROLE_ADMIN`.

### Accès
- **URL de base:** `/admin`
- **Authentification:** Requise (rôle `ROLE_ADMIN`)
- **Navigation:** Menu latéral avec toutes les sections disponibles

---

## Dashboard

### Vue d'ensemble
Le tableau de bord fournit une vue complète de l'état de la plateforme avec des statistiques en temps réel.

**Route:** `/admin` (ou `/admin/`)

### Fonctionnalités

#### Statistiques générales
- **Total des commandes:** Nombre total de commandes dans le système
- **Commandes en attente:** Commandes avec le statut `Pending`
- **Commandes complétées:** Commandes avec le statut `Delivered`
- **Total des utilisateurs:** Nombre total d'utilisateurs
- **Total des produits:** Nombre total de produits
- **Total des magasins:** Nombre total de magasins
- **Total des livreurs:** Nombre total d'utilisateurs avec le rôle `ROLE_DELIVER`
- **Livreurs en ligne:** Livreurs ayant mis à jour leur localisation dans les 15 dernières minutes

#### Statistiques financières
- **Revenu total:** Somme des montants des commandes livrées
- **Revenu du mois dernier:** Revenu généré au cours du mois précédent
- **Commandes d'aujourd'hui:** Nombre de commandes créées aujourd'hui
- **Revenu d'aujourd'hui:** Revenu généré aujourd'hui

#### Visualisations
- **Carte interactive:** Affiche:
  - Les magasins avec leurs emplacements
  - Les livreurs en ligne avec leur position actuelle
  - Les commandes actives (Pending, Confirmed, Processing, Shipped) avec leurs adresses de livraison

#### API
- **Endpoint:** `GET /api/admin/dashboard`
- **Sécurité:** JWT + `ROLE_ADMIN`
- **Réponse:**
  ```json
  {
    "counters": {
      "orders": { "total": 1200, "pending": 45, "completed": 980 },
      "users": { "total": 5600, "deliverers": { "total": 120, "online": 18 } },
      "inventory": { "products": 320, "stores": 45 }
    },
    "financials": {
      "totalRevenue": 1520000.0,
      "lastMonthRevenue": 82000.0,
      "todayOrders": 34,
      "todayRevenue": 175000.0
    },
    "map": {
      "stores": [{ "id": 1, "name": "Pharmacie Centrale", "location": { "latitude": -18.9, "longitude": 47.5, "address": "..." } }],
      "deliverers": { "count": 18, "items": [{ "id": 99, "fullName": "Toky Randria", "email": "toky@joy.pharma", "location": { "latitude": -18.8, "longitude": 47.4, "updatedAt": "2025-01-01T09:10:00+00:00" } }] },
      "orders": [{ "id": 450, "reference": "ORD-2025-001", "status": "pending", "totalAmount": 45000, "location": { "latitude": -18.7, "longitude": 47.3 } }]
    },
    "lists": {
      "recentOrders": [{ "id": 450, "reference": "ORD-2025-001", "status": "pending", "createdAt": "2025-01-01T08:45:00+00:00", "customer": { "id": 10, "fullName": "Tahina" } }],
      "availableOrders": [{ "...": "..." }]
    }
  }
  ```

#### Commandes récentes
- Liste des 10 dernières commandes créées
- Affichage des commandes disponibles pour les pharmacies (commandes en attente sans livreur assigné)

---

## Gestion des Utilisateurs

### Vue d'ensemble
Gestion complète des utilisateurs du système avec filtrage par type.

**Route principale:** `/admin/user`

### Fonctionnalités

#### Liste des utilisateurs
- **Tous les utilisateurs:** `/admin/user`
  - Affichage de tous les utilisateurs dans un tableau interactif (DataTable)
  - Colonnes: ID, Email, Prénom, Nom, Rôles, Statut actif, Actions

- **Livreurs:** `/admin/user/delivers`
  - Filtre automatique pour afficher uniquement les utilisateurs avec le rôle `ROLE_DELIVER`
  - Affichage des informations de livraison associées

- **Propriétaires de magasins:** `/admin/user/stores`
  - Filtre automatique pour afficher uniquement les utilisateurs avec le rôle `ROLE_STORE`

- **Clients:** `/admin/user/customers`
  - Filtre automatique pour afficher les clients (utilisateurs sans `ROLE_ADMIN` ou `ROLE_DELIVER`)

#### Création d'utilisateur
**Route:** `/admin/user/new`

**Champs du formulaire:**
- Email (requis)
- Prénom (requis)
- Nom (requis)
- Rôles (sélection multiple)
- Statut actif (checkbox)
- Mot de passe (requis pour nouveau compte)

**Fonctionnalités:**
- Validation des données
- Hachage automatique du mot de passe
- Attribution des rôles

#### Modification d'utilisateur
**Route:** `/admin/user/{id}/edit`

**Fonctionnalités:**
- Modification de toutes les informations utilisateur
- Changement des rôles
- Activation/désactivation du compte
- Modification du mot de passe (optionnel)

#### Actions sur les utilisateurs
- **Activer/Désactiver:** `/admin/user/{id}/toggle-active` (POST)
  - Bascule le statut actif/inactif d'un utilisateur
  - Notification toast de confirmation

- **Supprimer:** `/admin/user/{id}/delete` (POST)
  - Suppression définitive d'un utilisateur
  - Confirmation requise avant suppression

---

## Gestion des Produits

### Vue d'ensemble
Gestion complète du catalogue de produits pharmaceutiques.

**Route principale:** `/admin/product`

### Fonctionnalités

#### Liste des produits
**Route:** `/admin/product`

- Tableau interactif avec pagination, tri et recherche
- Colonnes: ID, Nom, Code, Catégorie, Marque, Prix, Stock, Statut, Actions
- Filtres par catégorie, marque, statut

#### Création de produit
**Route:** `/admin/product/new`

**Champs du formulaire:**
- **Informations de base:**
  - Nom (requis)
  - Code produit (requis, unique)
  - Description
  - Catégories (sélection multiple)
  - Forme (Form)
  - Marque (Brand)
  - Fabricant (Manufacturer)
  - Unité (Unit)

- **Prix et stock:**
  - Prix unitaire
  - Prix total
  - Quantité
  - Stock disponible
  - Devise

- **Médias:**
  - Images (upload multiple)
  - Variantes (JSON)

- **Statut:**
  - Actif/Inactif

**Fonctionnalités:**
- Upload de plusieurs images
- Gestion des variantes de produit
- Validation automatique des données

#### Modification de produit
**Route:** `/admin/product/{id}/edit`

**Fonctionnalités:**
- Modification de toutes les informations
- Ajout/suppression d'images
- Mise à jour du stock
- Modification des prix

#### Import en masse
**Route:** `/admin/product/upload-json`

**Fonctionnalités:**
- Import de produits depuis un fichier JSON
- Format JSON attendu: tableau d'objets produit
- Validation et création en masse
- Rapport d'erreurs pour les produits invalides

**Exemple de format JSON:**
```json
[
  {
    "name": "Paracétamol 500mg",
    "code": "PARA-500",
    "description": "Antalgique et antipyrétique",
    "unitPrice": 500,
    "stock": 100
  }
]
```

#### Actions sur les produits
- **Supprimer:** `/admin/product/{id}/delete` (POST)
  - Suppression d'un produit
  - Vérification des dépendances avant suppression

- **Suppression en masse:** `/admin/product/batch-delete` (POST)
  - Suppression de plusieurs produits sélectionnés
  - Rapport de succès/échec pour chaque produit

---

## Gestion des Catégories

### Vue d'ensemble
Gestion de la hiérarchie des catégories de produits.

**Route principale:** `/admin/category`

### Fonctionnalités

#### Liste des catégories
**Route:** `/admin/category`

- Tableau interactif avec toutes les catégories
- Affichage de la hiérarchie parent-enfant
- Colonnes: ID, Nom, Description, Couleur, Image, SVG, Actions

#### Création de catégorie
**Route:** `/admin/category/new`

**Champs du formulaire:**
- Nom (requis)
- Description
- Catégorie parente (optionnel, pour créer une sous-catégorie)
- Couleur (sélecteur de couleur)
- Image (upload)
- SVG (upload)

**Fonctionnalités:**
- Création de catégories hiérarchiques
- Upload d'image et d'icône SVG
- Attribution d'une couleur pour l'interface

#### Modification de catégorie
**Route:** `/admin/category/{id}/edit`

**Fonctionnalités:**
- Modification de toutes les propriétés
- Changement de la catégorie parente
- Remplacement de l'image/SVG

#### Actions sur les catégories
- **Supprimer:** `/admin/category/{id}/delete` (POST)
  - Suppression d'une catégorie
  - Vérification des produits associés

- **Suppression en masse:** `/admin/category/batch-delete` (POST)
  - Suppression de plusieurs catégories sélectionnées

---

## Gestion des Marques

### Vue d'ensemble
Gestion des marques de produits pharmaceutiques.

**Route principale:** `/admin/brand`

### Fonctionnalités

#### Liste des marques
**Route:** `/admin/brand`

- Tableau interactif avec toutes les marques
- Colonnes: ID, Nom, Image, Actions

#### Création de marque
**Route:** `/admin/brand/new`

**Champs du formulaire:**
- Nom (requis)
- Image (upload, optionnel)

**Fonctionnalités:**
- Upload du logo de la marque
- Validation de l'unicité du nom

#### Modification de marque
**Route:** `/admin/brand/{id}/edit`

**Fonctionnalités:**
- Modification du nom
- Remplacement de l'image
- Option de suppression d'image

#### Actions sur les marques
- **Supprimer:** `/admin/brand/{id}/delete` (POST)
  - Suppression d'une marque
  - Vérification des produits associés

- **Suppression en masse:** `/admin/brand/batch-delete` (POST)
  - Suppression de plusieurs marques sélectionnées

---

## Gestion des Fabricants

### Vue d'ensemble
Gestion des fabricants de produits pharmaceutiques.

**Route principale:** `/admin/manufacturer`

### Fonctionnalités

#### Liste des fabricants
**Route:** `/admin/manufacturer`

- Tableau interactif avec tous les fabricants
- Colonnes: ID, Nom, Description, Image, Actions

#### Création de fabricant
**Route:** `/admin/manufacturer/new`

**Champs du formulaire:**
- Nom (requis)
- Description (optionnel)
- Image (upload, optionnel)

**Fonctionnalités:**
- Upload du logo du fabricant
- Validation de l'unicité du nom

#### Modification de fabricant
**Route:** `/admin/manufacturer/{id}/edit`

**Fonctionnalités:**
- Modification du nom et de la description
- Remplacement de l'image
- Option de suppression d'image

#### Actions sur les fabricants
- **Supprimer:** `/admin/manufacturer/{id}/delete` (POST)
  - Suppression d'un fabricant
  - Vérification des produits associés

- **Suppression en masse:** `/admin/manufacturer/batch-delete` (POST)
  - Suppression de plusieurs fabricants sélectionnés

---

## Gestion des Formes

### Vue d'ensemble
Gestion des formes pharmaceutiques (comprimé, sirop, injection, etc.).

**Route principale:** `/admin/form`

### Fonctionnalités

#### Liste des formes
**Route:** `/admin/form`

- Tableau interactif avec toutes les formes
- Colonnes: ID, Nom, Actions

#### Création de forme
**Route:** `/admin/form/new`

**Champs du formulaire:**
- Nom (requis)

**Fonctionnalités:**
- Création simple d'une nouvelle forme
- Validation de l'unicité

#### Modification de forme
**Route:** `/admin/form/{id}/edit`

**Fonctionnalités:**
- Modification du nom

#### Actions sur les formes
- **Supprimer:** `/admin/form/{id}/delete` (POST)
  - Suppression d'une forme
  - Vérification des produits associés

- **Suppression en masse:** `/admin/form/batch-delete` (POST)
  - Suppression de plusieurs formes sélectionnées

---

## Gestion des Unités

### Vue d'ensemble
Gestion des unités de mesure (boîte, flacon, comprimé, etc.).

**Route principale:** `/admin/unit`

### Fonctionnalités

#### Liste des unités
**Route:** `/admin/unit`

- Tableau interactif avec toutes les unités
- Colonnes: ID, Nom, Actions

#### Création d'unité
**Route:** `/admin/unit/new`

**Champs du formulaire:**
- Nom (requis)

**Fonctionnalités:**
- Création simple d'une nouvelle unité
- Validation de l'unicité

#### Modification d'unité
**Route:** `/admin/unit/{id}/edit`

**Fonctionnalités:**
- Modification du nom

#### Actions sur les unités
- **Supprimer:** `/admin/unit/{id}/delete` (POST)
  - Suppression d'une unité
  - Vérification des produits associés

- **Suppression en masse:** `/admin/unit/batch-delete` (POST)
  - Suppression de plusieurs unités sélectionnées

---

## Gestion des Magasins

### Vue d'ensemble
Gestion complète des pharmacies et magasins partenaires.

**Route principale:** `/admin/store`

### Fonctionnalités

#### Liste des magasins
**Route:** `/admin/store`

- Tableau interactif avec tous les magasins
- Colonnes: ID, Nom, Propriétaire, Adresse, Statut, Actions

#### Création de magasin
**Route:** `/admin/store/new`

**Champs du formulaire:**
- **Informations de base:**
  - Nom (requis)
  - Email du propriétaire (requis)
  - Description

- **Localisation:**
  - Adresse (avec carte interactive)
  - Latitude/Longitude (auto-remplies depuis la carte)
  - Sélection sur carte Google Maps

- **Contact:**
  - Téléphone
  - Email
  - Autres informations de contact

**Fonctionnalités:**
- Création automatique d'un compte utilisateur pour le propriétaire
  - Email: celui fourni dans le formulaire
  - Mot de passe par défaut: `!Joy2025Pharam!`
  - Rôle: `ROLE_STORE`
- Initialisation automatique des paramètres d'ouverture (horaires par défaut)
- Carte interactive pour sélectionner l'emplacement

#### Modification de magasin
**Route:** `/admin/store/{id}/edit`

**Fonctionnalités:**
- Modification de toutes les informations
- Gestion des produits du magasin (voir section ci-dessous)
- Gestion des paramètres d'ouverture (voir section ci-dessous)

#### Gestion des produits du magasin
**Route:** `/admin/store/{id}/edit` (section produits)

**Fonctionnalités:**
- **Liste des produits:** Tableau interactif des produits disponibles dans le magasin
- **Ajouter un produit:** `/admin/store/{id}/product/add`
  - Sélection d'un produit du catalogue
  - Définition du prix spécifique au magasin
  - Définition du stock disponible
- **Modifier un produit:** `/admin/store/{storeId}/product/{id}/edit`
  - Modification du prix
  - Mise à jour du stock
- **Supprimer un produit:** `/admin/store/{storeId}/product/{id}/delete` (POST)
  - Retrait d'un produit du magasin

#### Gestion des paramètres d'ouverture
**Route:** `/admin/store/{id}/edit` (section paramètres)

**Mise à jour:** `/admin/store/{id}/setting/update` (POST)

**Fonctionnalités:**
- Configuration des horaires d'ouverture pour chaque jour de la semaine
- Pour chaque jour:
  - Heure d'ouverture
  - Heure de fermeture
  - Statut fermé (checkbox)
- Initialisation automatique des horaires par défaut:
  - Lundi-Samedi: 09:00 - 18:00
  - Dimanche: Fermé

#### Actions sur les magasins
- **Supprimer:** `/admin/store/{id}/delete` (POST)
  - Suppression d'un magasin
  - Vérification des commandes associées

- **Suppression en masse:** `/admin/store/batch-delete` (POST)
  - Suppression de plusieurs magasins sélectionnés

---

## Gestion des Commandes

### Vue d'ensemble
Gestion complète du cycle de vie des commandes.

**Route principale:** `/admin/order`

### Fonctionnalités

#### Liste des commandes
**Route:** `/admin/order`

- Tableau interactif avec toutes les commandes
- Colonnes: ID, Référence, Client, Statut, Montant total, Date, Actions
- Filtres par statut, date, client

#### Création de commande
**Route:** `/admin/order/new`

**Champs du formulaire:**
- **Informations de base:**
  - Référence (requis, unique)
  - Client (requis, sélection)
  - Téléphone (requis)
  - Priorité (requis): Urgent, Standard, Planifié
  - Statut (requis): Pending, Confirmed, Processing, Shipped, Delivered, Cancelled

- **Localisation de livraison:**
  - Adresse (avec carte interactive)
  - Latitude/Longitude (auto-remplies)
  - Sélection sur carte Google Maps

- **Articles de commande:**
  - Produit (requis)
  - Quantité (requis, minimum 1)
  - Magasin (optionnel, pour assigner à un magasin spécifique)
  - Possibilité d'ajouter plusieurs articles

- **Informations supplémentaires:**
  - Date de livraison prévue
  - Personne de livraison (sélection parmi les livreurs)
  - Notes

**Fonctionnalités:**
- Calcul automatique du montant total
- Validation des champs requis
- Vérification de la disponibilité des produits
- Gestion des articles multiples

#### Visualisation de commande
**Route:** `/admin/order/{id}`

**Fonctionnalités:**
- Affichage détaillé de toutes les informations
- Liste des articles avec quantités et prix
- Informations de livraison
- Historique des statuts

#### Modification de commande
**Route:** `/admin/order/{id}/edit`

**Fonctionnalités:**
- Modification de toutes les informations
- Ajout/suppression d'articles
- Changement de statut
- Modification de la localisation de livraison
- Assignation d'un livreur

#### Actions sur les commandes
- **Supprimer:** `/admin/order/{id}/delete` (POST)
  - Suppression d'une commande
  - Confirmation requise

- **Suppression en masse:** `/admin/order/batch-delete` (POST)
  - Suppression de plusieurs commandes sélectionnées

### Statuts de commande
- **Pending:** En attente de confirmation
- **Confirmed:** Confirmée
- **Processing:** En cours de traitement
- **Shipped:** Expédiée
- **Delivered:** Livrée
- **Cancelled:** Annulée

### Priorités de commande
- **Urgent:** Priorité haute
- **Standard:** Priorité normale
- **Planned:** Planifiée

---

## Gestion des Promotions

### Vue d'ensemble
Gestion des codes promotionnels et réductions.

**Route principale:** `/admin/promotion`

### Fonctionnalités

#### Liste des promotions
**Route:** `/admin/promotion`

- Tableau interactif avec toutes les promotions
- Colonnes: ID, Code, Type, Valeur, Date début, Date fin, Statut, Actions

#### Création de promotion
**Route:** `/admin/promotion/new`

**Champs du formulaire:**
- Code (requis, unique)
- Type de réduction: Pourcentage ou Montant fixe
- Valeur (requis)
- Date de début (requis)
- Date de fin (requis)
- Description
- Conditions d'utilisation

**Fonctionnalités:**
- Validation de l'unicité du code
- Validation des dates (début < fin)
- Calcul automatique de la réduction

#### Modification de promotion
**Route:** `/admin/promotion/{id}/edit`

**Fonctionnalités:**
- Modification de toutes les propriétés
- Vérification de l'unicité du code (excluant la promotion actuelle)

#### Actions sur les promotions
- **Supprimer:** `/admin/promotion/{id}/delete` (POST)
  - Suppression d'une promotion
  - **Protection:** Impossible de supprimer une promotion utilisée dans des commandes
  - Message d'avertissement si la promotion est utilisée

---

## Interface Propriétaire de Magasin

### Vue d'ensemble
Interface dédiée aux propriétaires de magasins pour gérer leurs commandes.

**Accès:** Requiert le rôle `ROLE_STORE`

### Fonctionnalités

#### Dashboard du magasin
**Route:** `/admin/store/dashboard`

**Fonctionnalités:**
- Vue d'ensemble du magasin
- **Articles de commande en attente:**
  - Liste des articles de commande assignés au magasin avec statut `PENDING`
  - Affichage des informations de commande parente
  - Informations produit
- **Paramètres d'ouverture:**
  - Affichage des horaires d'ouverture configurés
  - Statut actuel (ouvert/fermé selon les horaires)

#### Liste des commandes
**Route:** `/admin/store/orders`

**Fonctionnalités:**
- Liste complète de tous les articles de commande assignés au magasin
- Informations détaillées:
  - Commande parente
  - Client
  - Produit
  - Quantité
  - Statut
  - Date de création
- Tri par date de création (plus récent en premier)

### Restrictions
- Accès uniquement aux commandes de son propre magasin
- Impossible de modifier les informations du magasin (réservé à l'admin)
- Impossible de créer de nouvelles commandes (réservé à l'admin)

---

## Fonctionnalités communes

### DataTables
Toutes les listes utilisent des DataTables interactives avec:
- **Pagination:** Navigation par pages
- **Tri:** Tri par colonne (clic sur l'en-tête)
- **Recherche:** Recherche globale dans toutes les colonnes
- **Filtres:** Filtres spécifiques selon la section
- **Sélection multiple:** Pour les actions en masse
- **Export:** Possibilité d'exporter les données (selon configuration)

### Notifications Toast
Toutes les actions affichent des notifications toast:
- **Succès:** Action réussie (vert)
- **Erreur:** Erreur lors de l'action (rouge)
- **Avertissement:** Action partielle ou avec restrictions (orange)
- **Information:** Informations générales (bleu)

### Validation
- Validation côté client (HTML5)
- Validation côté serveur (Symfony)
- Messages d'erreur clairs et contextuels
- Prévention des doublons (codes, emails, etc.)

### Sécurité
- Authentification requise pour toutes les routes admin
- Vérification des rôles (`ROLE_ADMIN`)
- Protection CSRF sur tous les formulaires
- Validation des permissions avant suppression

### Interface utilisateur
- Design responsive (mobile, tablette, desktop)
- Navigation latérale avec icônes
- Breadcrumbs pour la navigation
- Modales pour les actions rapides
- Cartes interactives pour la sélection de localisation

---

## Routes API Admin

### Résumé des routes principales

| Route | Méthode | Description |
|-------|---------|-------------|
| `/admin` | GET | Dashboard |
| `/admin/user` | GET | Liste des utilisateurs |
| `/admin/user/new` | GET/POST | Créer un utilisateur |
| `/admin/user/{id}/edit` | GET/POST | Modifier un utilisateur |
| `/admin/user/{id}/toggle-active` | POST | Activer/Désactiver |
| `/admin/user/{id}/delete` | POST | Supprimer un utilisateur |
| `/admin/product` | GET | Liste des produits |
| `/admin/product/new` | GET/POST | Créer un produit |
| `/admin/product/{id}/edit` | GET/POST | Modifier un produit |
| `/admin/product/{id}/delete` | POST | Supprimer un produit |
| `/admin/product/upload-json` | GET/POST | Import JSON |
| `/admin/category` | GET | Liste des catégories |
| `/admin/category/new` | GET/POST | Créer une catégorie |
| `/admin/category/{id}/edit` | GET/POST | Modifier une catégorie |
| `/admin/category/{id}/delete` | POST | Supprimer une catégorie |
| `/admin/brand` | GET | Liste des marques |
| `/admin/brand/new` | GET/POST | Créer une marque |
| `/admin/brand/{id}/edit` | GET/POST | Modifier une marque |
| `/admin/brand/{id}/delete` | POST | Supprimer une marque |
| `/admin/manufacturer` | GET | Liste des fabricants |
| `/admin/manufacturer/new` | GET/POST | Créer un fabricant |
| `/admin/manufacturer/{id}/edit` | GET/POST | Modifier un fabricant |
| `/admin/manufacturer/{id}/delete` | POST | Supprimer un fabricant |
| `/admin/form` | GET | Liste des formes |
| `/admin/form/new` | GET/POST | Créer une forme |
| `/admin/form/{id}/edit` | GET/POST | Modifier une forme |
| `/admin/form/{id}/delete` | POST | Supprimer une forme |
| `/admin/unit` | GET | Liste des unités |
| `/admin/unit/new` | GET/POST | Créer une unité |
| `/admin/unit/{id}/edit` | GET/POST | Modifier une unité |
| `/admin/unit/{id}/delete` | POST | Supprimer une unité |
| `/admin/store` | GET | Liste des magasins |
| `/admin/store/new` | GET/POST | Créer un magasin |
| `/admin/store/{id}/edit` | GET/POST | Modifier un magasin |
| `/admin/store/{id}/delete` | POST | Supprimer un magasin |
| `/admin/store/{id}/setting/update` | POST | Mettre à jour les paramètres |
| `/admin/store/{id}/product/add` | GET/POST | Ajouter un produit |
| `/admin/store/{storeId}/product/{id}/edit` | GET/POST | Modifier un produit de magasin |
| `/admin/store/{storeId}/product/{id}/delete` | POST | Supprimer un produit de magasin |
| `/admin/order` | GET | Liste des commandes |
| `/admin/order/new` | GET/POST | Créer une commande |
| `/admin/order/{id}` | GET | Voir une commande |
| `/admin/order/{id}/edit` | GET/POST | Modifier une commande |
| `/admin/order/{id}/delete` | POST | Supprimer une commande |
| `/admin/promotion` | GET | Liste des promotions |
| `/admin/promotion/new` | GET/POST | Créer une promotion |
| `/admin/promotion/{id}/edit` | GET/POST | Modifier une promotion |
| `/admin/promotion/{id}/delete` | POST | Supprimer une promotion |

---

## Notes importantes

### Permissions
- Toutes les routes admin nécessitent le rôle `ROLE_ADMIN`
- L'interface propriétaire de magasin nécessite le rôle `ROLE_STORE`
- Les utilisateurs ne peuvent accéder qu'à leurs propres données (pour les propriétaires de magasins)

### Suppressions
- Les suppressions vérifient toujours les dépendances
- Certaines entités ne peuvent pas être supprimées si elles sont utilisées (ex: promotions dans des commandes)
- Les suppressions en masse peuvent partiellement échouer (certains éléments protégés)

### Données par défaut
- Mot de passe par défaut pour les nouveaux propriétaires de magasins: `!Joy2025Pharam!`
- Horaires par défaut des magasins: 09:00-18:00 (Lundi-Samedi), Fermé (Dimanche)
- Date de livraison par défaut pour les nouvelles commandes: Aujourd'hui

### Performance
- Les DataTables utilisent la pagination pour optimiser les performances
- Les requêtes utilisent le lazy loading pour les relations
- Les cartes interactives chargent un maximum de 50 commandes actives

---

---

## API Admin

Toutes les fonctionnalités admin sont également disponibles via une API REST complète utilisant API Platform. L'API suit les meilleures pratiques avec des State Processors et Providers pour une séparation claire des responsabilités.

### Authentification API

Tous les endpoints admin nécessitent:
- **Authentification JWT:** Token Bearer dans le header `Authorization`
- **Rôle requis:** `ROLE_ADMIN`

```
Authorization: Bearer {JWT_TOKEN}
```

### Base URL

```
/api/admin/{resource}
```

### Format de réponse

Toutes les réponses sont au format JSON avec les groupes de sérialisation appropriés.

#### Réponse de succès
```json
{
  "id": 1,
  "name": "Example",
  // ... autres champs selon la ressource
}
```

#### Réponse d'erreur
```json
{
  "type": "https://tools.ietf.org/html/rfc2616#section-10",
  "title": "An error occurred",
  "detail": "Specific error message",
  "violations": [
    {
      "propertyPath": "fieldName",
      "message": "Error message"
    }
  ]
}
```

### Endpoints API par ressource

#### Utilisateurs (`/admin/users`)

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/api/admin/users` | Liste tous les utilisateurs (filtre: `?type=all|delivers|stores|customers`) |
| GET | `/api/admin/users/{id}` | Récupère un utilisateur par ID |
| POST | `/api/admin/users` | Crée un nouvel utilisateur |
| PUT | `/api/admin/users/{id}` | Met à jour un utilisateur |
| DELETE | `/api/admin/users/{id}` | Supprime un utilisateur |
| POST | `/api/admin/users/{id}/toggle-active` | Active/désactive un utilisateur |

**Body pour POST/PUT (UserInput):**
```json
{
  "email": "user@example.com",
  "firstName": "John",
  "lastName": "Doe",
  "password": "password123",
  "roles": ["ROLE_USER"],
  "active": true
}
```

#### Produits (`/admin/products`)

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/api/admin/products` | Liste tous les produits |
| GET | `/api/admin/products/{id}` | Récupère un produit par ID |
| POST | `/api/admin/products` | Crée un nouveau produit |
| PUT | `/api/admin/products/{id}` | Met à jour un produit |
| DELETE | `/api/admin/products/{id}` | Supprime un produit |
| POST | `/api/admin/products/batch-delete` | Suppression en masse |

**Body pour POST/PUT (ProductInput):**
```json
{
  "name": "Paracétamol 500mg",
  "code": "PARA-500",
  "description": "Antalgique et antipyrétique",
  "categories": [1, 2],
  "form": 1,
  "brand": 1,
  "manufacturer": 1,
  "unit": 1,
  "unitPrice": 500.0,
  "totalPrice": 500.0,
  "quantity": 1.0,
  "stock": 100,
  "currency": "MGA",
  "isActive": true,
  "variants": []
}
```

#### Catégories (`/admin/categories`)

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/api/admin/categories` | Liste toutes les catégories |
| GET | `/api/admin/categories/{id}` | Récupère une catégorie par ID |
| POST | `/api/admin/categories` | Crée une nouvelle catégorie |
| PUT | `/api/admin/categories/{id}` | Met à jour une catégorie |
| DELETE | `/api/admin/categories/{id}` | Supprime une catégorie |
| POST | `/api/admin/categories/batch-delete` | Suppression en masse |

**Body pour POST/PUT (CategoryInput):**
```json
{
  "name": "Antalgiques",
  "description": "Médicaments contre la douleur",
  "parent": null,
  "color": "#FF5733"
}
```

#### Marques (`/admin/brands`)

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/api/admin/brands` | Liste toutes les marques |
| GET | `/api/admin/brands/{id}` | Récupère une marque par ID |
| POST | `/api/admin/brands` | Crée une nouvelle marque |
| PUT | `/api/admin/brands/{id}` | Met à jour une marque |
| DELETE | `/api/admin/brands/{id}` | Supprime une marque |
| POST | `/api/admin/brands/batch-delete` | Suppression en masse |

**Body pour POST/PUT (BrandInput):**
```json
{
  "name": "Doliprane"
}
```

#### Fabricants (`/admin/manufacturers`)

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/api/admin/manufacturers` | Liste tous les fabricants |
| GET | `/api/admin/manufacturers/{id}` | Récupère un fabricant par ID |
| POST | `/api/admin/manufacturers` | Crée un nouveau fabricant |
| PUT | `/api/admin/manufacturers/{id}` | Met à jour un fabricant |
| DELETE | `/api/admin/manufacturers/{id}` | Supprime un fabricant |
| POST | `/api/admin/manufacturers/batch-delete` | Suppression en masse |

**Body pour POST/PUT (ManufacturerInput):**
```json
{
  "name": "Sanofi",
  "description": "Laboratoire pharmaceutique"
}
```

#### Formes (`/admin/forms`)

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/api/admin/forms` | Liste toutes les formes |
| GET | `/api/admin/forms/{id}` | Récupère une forme par ID |
| POST | `/api/admin/forms` | Crée une nouvelle forme |
| PUT | `/api/admin/forms/{id}` | Met à jour une forme |
| DELETE | `/api/admin/forms/{id}` | Supprime une forme |
| POST | `/api/admin/forms/batch-delete` | Suppression en masse |

**Body pour POST/PUT (FormInput):**
```json
{
  "name": "Comprimé"
}
```

#### Unités (`/admin/units`)

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/api/admin/units` | Liste toutes les unités |
| GET | `/api/admin/units/{id}` | Récupère une unité par ID |
| POST | `/api/admin/units` | Crée une nouvelle unité |
| PUT | `/api/admin/units/{id}` | Met à jour une unité |
| DELETE | `/api/admin/units/{id}` | Supprime une unité |
| POST | `/api/admin/units/batch-delete` | Suppression en masse |

**Body pour POST/PUT (UnitInput):**
```json
{
  "name": "Boîte"
}
```

#### Magasins (`/admin/stores`)

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/api/admin/stores` | Liste tous les magasins |
| GET | `/api/admin/stores/{id}` | Récupère un magasin par ID |
| POST | `/api/admin/stores` | Crée un nouveau magasin |
| PUT | `/api/admin/stores/{id}` | Met à jour un magasin |
| DELETE | `/api/admin/stores/{id}` | Supprime un magasin |
| POST | `/api/admin/stores/batch-delete` | Suppression en masse |

**Body pour POST/PUT (StoreInput):**
```json
{
  "name": "Pharmacie Centrale",
  "ownerEmail": "owner@pharmacy.com",
  "description": "Pharmacie principale",
  "address": "123 Rue Principale",
  "latitude": -18.8792,
  "longitude": 47.5079,
  "phone": "+261340000000",
  "email": "contact@pharmacy.com"
}
```

#### Commandes (`/admin/orders`)

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/api/admin/orders` | Liste toutes les commandes |
| GET | `/api/admin/orders/{id}` | Récupère une commande par ID |
| POST | `/api/admin/orders` | Crée une nouvelle commande |
| PUT | `/api/admin/orders/{id}` | Met à jour une commande |
| DELETE | `/api/admin/orders/{id}` | Supprime une commande |
| POST | `/api/admin/orders/batch-delete` | Suppression en masse |

**Body pour POST/PUT (OrderInput):**
```json
{
  "reference": "ORD-2025-001",
  "customer": 1,
  "phone": "+261341234567",
  "priority": "urgent",
  "status": "pending",
  "address": "123 Rue de la Paix",
  "latitude": -18.8792,
  "longitude": 47.5079,
  "scheduledDate": "2025-01-15T10:00:00Z",
  "deliveryPerson": 2,
  "notes": "Livraison urgente",
  "items": [
    {
      "product": 1,
      "quantity": 2,
      "store": 1
    }
  ]
}
```

#### Promotions (`/admin/promotions`)

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/api/admin/promotions` | Liste toutes les promotions |
| GET | `/api/admin/promotions/{id}` | Récupère une promotion par ID |
| POST | `/api/admin/promotions` | Crée une nouvelle promotion |
| PUT | `/api/admin/promotions/{id}` | Met à jour une promotion |
| DELETE | `/api/admin/promotions/{id}` | Supprime une promotion |

**Body pour POST/PUT (PromotionInput):**
```json
{
  "code": "PROMO2025",
  "type": "percentage",
  "value": 10.0,
  "startDate": "2025-01-01T00:00:00Z",
  "endDate": "2025-12-31T23:59:59Z",
  "description": "Promotion de 10%",
  "minimumOrderAmount": 10000.0
}
```

### Suppression en masse

Pour supprimer plusieurs éléments en une seule requête:

**Endpoint:** `POST /api/admin/{resource}/batch-delete`

**Body (BatchDeleteInput):**
```json
{
  "ids": [1, 2, 3, 4, 5]
}
```

**Réponse:**
```json
{
  "success": true,
  "success_count": 4,
  "failure_count": 1,
  "message": "4 item(s) deleted successfully. 1 item(s) could not be deleted."
}
```

### Pagination

Les endpoints de liste supportent la pagination standard d'API Platform:

- `?page=1` - Numéro de page
- `?itemsPerPage=10` - Nombre d'éléments par page

**Réponse paginée:**
```json
{
  "hydra:member": [...],
  "hydra:totalItems": 100,
  "hydra:view": {
    "hydra:first": "/api/admin/products?page=1",
    "hydra:last": "/api/admin/products?page=10",
    "hydra:next": "/api/admin/products?page=2"
  }
}
```

### Filtres et recherche

Les endpoints de liste supportent les filtres standards d'API Platform:

- `?name=value` - Filtre par nom
- `?status=active` - Filtre par statut
- `?order[createdAt]=desc` - Tri

### Codes de statut HTTP

| Code | Signification |
|------|---------------|
| 200 | Succès (GET, PUT) |
| 201 | Créé (POST) |
| 204 | Pas de contenu (DELETE) |
| 400 | Requête invalide |
| 401 | Non authentifié |
| 403 | Accès refusé (pas ROLE_ADMIN) |
| 404 | Ressource non trouvée |
| 422 | Erreur de validation |

### Architecture API

L'API utilise une architecture moderne avec:

- **State Processors:** Gèrent la logique métier pour les opérations CRUD
- **State Providers:** Gèrent la récupération et le filtrage des données
- **DTOs (Data Transfer Objects):** Définissent les structures d'entrée
- **Services:** Encapsulent la logique métier réutilisable
- **Repositories:** Gèrent l'accès aux données

Cette architecture assure:
- Séparation claire des responsabilités
- Réutilisabilité du code
- Facilité de test
- Maintenabilité

### Documentation interactive

La documentation complète de l'API est disponible via:
- **ReDoc:** `/api/docs`
- **OpenAPI Spec:** `/api/docs.json`

---

## Support

Pour toute question ou problème concernant l'interface d'administration ou l'API, veuillez contacter l'équipe technique.

**Version du document:** 2.0  
**Dernière mise à jour:** 2025

