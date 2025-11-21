# Documentation Elasticsearch - Joy Pharma

Cette documentation explique l'utilisation d'Elasticsearch dans le projet Joy Pharma pour la recherche de produits.

## Table des matières

1. [Vue d'ensemble](#vue-densemble)
2. [Configuration](#configuration)
3. [Architecture](#architecture)
4. [Indexation des produits](#indexation-des-produits)
5. [Recherche de produits](#recherche-de-produits)
6. [Commandes disponibles](#commandes-disponibles)
7. [API Endpoints](#api-endpoints)
8. [Structure des données](#structure-des-données)
9. [Dépannage](#dépannage)

## Vue d'ensemble

Elasticsearch est utilisé dans ce projet pour fournir une recherche rapide et performante des produits avec les fonctionnalités suivantes :

- **Recherche full-text** : Recherche dans le nom, code, description des produits
- **Filtrage avancé** : Filtrage par catégorie, marque, fabricant, statut actif
- **Recherche floue** : Support de la recherche avec tolérance aux fautes de frappe
- **Tri par pertinence** : Résultats triés par score de pertinence
- **Pagination** : Support de la pagination pour les résultats de recherche
- **Indexation automatique** : Synchronisation automatique lors de la création/modification/suppression de produits

## Configuration

### Variables d'environnement

Les variables d'environnement suivantes peuvent être configurées :

```bash
# URL du serveur Elasticsearch (optionnel, par défaut: http://elasticsearch:9200)
ELASTICSEARCH_HOST=http://elasticsearch:9200

# Préfixe des index Elasticsearch (optionnel, par défaut: joy_pharma)
ELASTICSEARCH_INDEX_PREFIX=joy_pharma
```

### Configuration Lando/Docker

Dans `.lando.yml`, Elasticsearch est configuré comme suit :

```yaml
elasticsearch:
  type: elasticsearch
  version: 8.11.0
  portforward: 9200
  config:
    server:
      xpack.security.enabled: false
      xpack.security.enrollment.enabled: false
      discovery.type: single-node
      bootstrap.memory_lock: false
      "action.auto_create_index": true
    jvm.options:
      - "-Xms512m"
      - "-Xmx512m"
```

### Configuration des services

La configuration des services se trouve dans `config/services.yaml` :

```yaml
parameters:
  elasticsearch.hosts: []
  elasticsearch.index_prefix: 'joy_pharma'

services:
  App\Service\ElasticsearchService:
    arguments:
      $hosts: []
      $indexPrefix: 'joy_pharma'
      $logger: '@logger'
```

## Architecture

### Services principaux

#### 1. ElasticsearchService (`src/Service/ElasticsearchService.php`)

Service de base pour interagir avec Elasticsearch. Fournit les méthodes suivantes :

- `getClient()` : Retourne le client Elasticsearch
- `getIndexName(string $index)` : Génère le nom complet de l'index avec préfixe
- `indexExists(string $index)` : Vérifie si un index existe
- `createIndex(string $index, array $mapping)` : Crée un index avec son mapping
- `indexDocument(string $index, string $id, array $document)` : Indexe un document
- `updateDocument(string $index, string $id, array $document)` : Met à jour un document
- `deleteDocument(string $index, string $id)` : Supprime un document
- `search(string $index, array $query)` : Effectue une recherche
- `bulk(array $operations)` : Effectue des opérations en lot

#### 2. ProductElasticsearchService (`src/Service/ProductElasticsearchService.php`)

Service spécialisé pour la gestion des produits dans Elasticsearch :

- `initializeIndex()` : Initialise l'index des produits avec le mapping approprié
- `indexProduct(Product $product)` : Indexe un produit
- `updateProduct(Product $product)` : Met à jour un produit indexé
- `deleteProduct(int $productId)` : Supprime un produit de l'index
- `searchProducts(string $query, array $filters, int $page, int $limit)` : Recherche des produits
- `reindexAll()` : Réindexe tous les produits

#### 3. ProductElasticsearchSubscriber (`src/EventSubscriber/ProductElasticsearchSubscriber.php`)

Écouteur d'événements Doctrine qui synchronise automatiquement Elasticsearch lors des modifications de produits :

- `postPersist()` : Indexe un nouveau produit après sa création
- `postUpdate()` : Met à jour l'index après modification d'un produit
- `preRemove()` : Supprime le produit de l'index avant sa suppression

#### 4. ProductElasticsearchProvider (`src/State/Product/ProductElasticsearchProvider.php`)

Provider ApiPlatform qui gère l'endpoint de recherche `/api/products/search` :

- Extrait les paramètres de recherche depuis la requête
- Appelle `ProductElasticsearchService::searchProducts()`
- Retourne les résultats formatés pour ApiPlatform

## Indexation des produits

### Indexation automatique

L'indexation se fait automatiquement grâce à `ProductElasticsearchSubscriber` qui écoute les événements Doctrine :

- **Création** : Un produit est automatiquement indexé après sa création
- **Modification** : L'index est mis à jour automatiquement lors de la modification
- **Suppression** : Le produit est supprimé de l'index avant sa suppression

### Indexation manuelle

Pour réindexer tous les produits manuellement, utilisez la commande :

```bash
php bin/console app:reindex-products
```

Cette commande :
1. Initialise l'index s'il n'existe pas
2. Réindexe tous les produits de la base de données
3. Utilise l'API bulk pour des performances optimales

### Structure de l'index

L'index est nommé : `{prefix}_products` (par défaut : `joy_pharma_products`)

## Recherche de produits

### Endpoint API

**URL** : `GET /api/products/search`

**Authentification** : Optionnelle (endpoint public, mais recommandé pour de meilleurs résultats)

### Paramètres de requête

| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| `q` ou `query` | string | Non | Requête de recherche pour le nom, code ou description du produit |
| `category` | integer | Non | Filtrer par ID de catégorie |
| `brand` | integer | Non | Filtrer par ID de marque |
| `manufacturer` | integer | Non | Filtrer par ID de fabricant |
| `isActive` | boolean | Non | Filtrer par statut actif (true/false) |
| `page` | integer | Non | Numéro de page pour la pagination (défaut: 1) |
| `perPage` ou `itemsPerPage` | integer | Non | Nombre d'éléments par page (défaut: 10, max: 50) |

### Exemples de requêtes

#### Recherche simple par nom

```bash
curl -X GET "http://localhost/api/products/search?q=paracetamol" \
  -H "Authorization: Bearer {JWT_TOKEN}"
```

#### Recherche avec filtres

```bash
curl -X GET "http://localhost/api/products/search?q=aspirin&category=3&brand=5&page=1&perPage=20" \
  -H "Authorization: Bearer {JWT_TOKEN}"
```

#### Recherche tous les produits d'une catégorie

```bash
curl -X GET "http://localhost/api/products/search?category=3&page=1&perPage=10" \
  -H "Authorization: Bearer {JWT_TOKEN}"
```

#### Recherche sans requête (tous les produits)

```bash
curl -X GET "http://localhost/api/products/search?page=1&perPage=20" \
  -H "Authorization: Bearer {JWT_TOKEN}"
```

### Réponse

**Format** : JSON

**Structure** : Tableau d'objets Product avec les groupes de sérialisation suivants :
- `id:read`
- `product:read`
- `image:read`
- `media_object:read`

**Exemple de réponse** :

```json
[
  {
    "id": 45,
    "name": "Paracetamol 500mg",
    "code": "PAR-500",
    "description": "Analgésique et antipyrétique",
    "isActive": true,
    "unitPrice": 2.50,
    "totalPrice": 25.00,
    "quantity": 10,
    "stock": 150,
    "images": [...],
    "category": [...],
    "brand": {...},
    "manufacturer": {...},
    "form": {...},
    "currency": {...}
  }
]
```

### Comportement de recherche

- **Recherche insensible à la casse** : La recherche est insensible à la casse
- **Recherche floue** : Support de la recherche avec tolérance aux fautes de frappe (fuzziness: AUTO)
- **Champs recherchés** : 
  - `name` (poids: 3)
  - `code` (poids: 2)
  - `description` (poids: 1)
  - `brand.name` (poids: 1)
  - `manufacturer.name` (poids: 1)
  - `form.name` (poids: 1)
  - `categories.name` (poids: 1)
- **Tri** : Résultats triés par score de pertinence, puis par date de création (décroissant)
- **Requête vide** : Retourne tous les produits (avec pagination)

## Commandes disponibles

### Réindexer tous les produits

```bash
php bin/console app:reindex-products
```

Cette commande :
- Initialise l'index Elasticsearch s'il n'existe pas
- Réindexe tous les produits de la base de données
- Utilise l'API bulk pour des performances optimales
- Affiche un message de succès ou d'erreur

**Quand l'utiliser** :
- Après la première installation
- Après des modifications importantes du mapping
- Si l'index est corrompu ou incomplet
- Après une migration de données importante

## Structure des données

### Mapping de l'index

Le mapping définit la structure des documents indexés :

```json
{
  "properties": {
    "id": { "type": "integer" },
    "name": {
      "type": "text",
      "analyzer": "standard",
      "fields": {
        "keyword": { "type": "keyword" }
      }
    },
    "code": {
      "type": "text",
      "analyzer": "standard",
      "fields": {
        "keyword": { "type": "keyword" }
      }
    },
    "description": {
      "type": "text",
      "analyzer": "standard"
    },
    "brand": {
      "type": "nested",
      "properties": {
        "id": { "type": "integer" },
        "name": { "type": "text", "analyzer": "standard" }
      }
    },
    "manufacturer": {
      "type": "nested",
      "properties": {
        "id": { "type": "integer" },
        "name": { "type": "text", "analyzer": "standard" }
      }
    },
    "form": {
      "type": "nested",
      "properties": {
        "id": { "type": "integer" },
        "name": { "type": "text", "analyzer": "standard" }
      }
    },
    "categories": {
      "type": "nested",
      "properties": {
        "id": { "type": "integer" },
        "name": { "type": "text", "analyzer": "standard" }
      }
    },
    "isActive": { "type": "boolean" },
    "unitPrice": { "type": "float" },
    "totalPrice": { "type": "float" },
    "quantity": { "type": "integer" },
    "stock": { "type": "integer" },
    "currency": {
      "type": "nested",
      "properties": {
        "id": { "type": "integer" },
        "label": { "type": "keyword" }
      }
    },
    "createdAt": { "type": "date" },
    "updatedAt": { "type": "date" }
  }
}
```

### Types de champs

- **text** : Pour la recherche full-text avec analyse
- **keyword** : Pour la recherche exacte et le tri
- **nested** : Pour les objets imbriqués (brand, manufacturer, categories, etc.)
- **integer/float** : Pour les valeurs numériques
- **boolean** : Pour les valeurs booléennes
- **date** : Pour les dates

## Dépannage

### Vérifier la connexion Elasticsearch

```bash
# Vérifier si Elasticsearch est accessible
curl http://localhost:9200

# Vérifier les index
curl http://localhost:9200/_cat/indices?v

# Vérifier l'index des produits
curl http://localhost:9200/joy_pharma_products
```

### Problèmes courants

#### 1. L'index n'existe pas

**Symptôme** : Erreur lors de la recherche ou de l'indexation

**Solution** :
```bash
php bin/console app:reindex-products
```

#### 2. Les produits ne sont pas indexés automatiquement

**Vérifications** :
- Vérifier que `ProductElasticsearchSubscriber` est bien enregistré
- Vérifier les logs pour des erreurs d'indexation
- Vérifier la connexion à Elasticsearch

#### 3. Les résultats de recherche sont vides

**Vérifications** :
- Vérifier que l'index contient des données : `curl http://localhost:9200/joy_pharma_products/_count`
- Vérifier que les produits sont bien indexés
- Vérifier les paramètres de recherche

#### 4. Erreur de connexion

**Vérifications** :
- Vérifier que Elasticsearch est démarré
- Vérifier la variable d'environnement `ELASTICSEARCH_HOST`
- Vérifier les logs d'erreur

### Logs

Les erreurs d'indexation sont loggées dans les logs Symfony. Pour les consulter :

```bash
# Logs généraux
tail -f var/log/dev.log

# Logs de production
tail -f var/log/prod.log
```

### Réinitialisation complète

Si vous devez réinitialiser complètement l'index :

```bash
# Supprimer l'index (attention : supprime toutes les données)
curl -X DELETE http://localhost:9200/joy_pharma_products

# Recréer et réindexer
php bin/console app:reindex-products
```

## Bonnes pratiques

1. **Réindexation régulière** : Planifiez une réindexation complète périodiquement (par exemple, une fois par semaine)

2. **Gestion des erreurs** : Les erreurs d'indexation ne doivent pas bloquer l'application. Les erreurs sont loggées mais n'interrompent pas le flux normal.

3. **Performance** : Utilisez l'API bulk pour indexer plusieurs produits à la fois lors des réindexations.

4. **Monitoring** : Surveillez la taille de l'index et les performances de recherche.

5. **Backup** : Sauvegardez régulièrement l'index Elasticsearch en production.

## Références

- [Documentation Elasticsearch](https://www.elastic.co/guide/en/elasticsearch/reference/current/index.html)
- [Client PHP Elasticsearch](https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/index.html)
- [ApiPlatform Documentation](https://api-platform.com/docs/)

