# Dépannage Elasticsearch

## Problème : "No alive nodes. All the 1 nodes seem to be down."

Cette erreur indique que l'application ne peut pas se connecter à Elasticsearch. Voici plusieurs solutions pour résoudre ce problème.

## Solutions

### Solution 1 : Ajouter Elasticsearch au Docker Compose (Recommandé)

Si vous utilisez Docker Compose, ajoutez le service Elasticsearch à votre fichier `compose.yaml` ou `compose.prod.yaml` :

```yaml
services:
  php:
    # ... configuration existante ...
    environment:
      # ... autres variables ...
      ELASTICSEARCH_HOST: http://elasticsearch:9200
    networks:
      - traefik_network
      - database_network
      - elasticsearch_network  # Ajouter ce réseau

  elasticsearch:
    image: docker.elastic.co/elasticsearch/elasticsearch:8.11.0
    container_name: joy-pharma-elasticsearch
    restart: unless-stopped
    environment:
      - discovery.type=single-node
      - xpack.security.enabled=false
      - "ES_JAVA_OPTS=-Xms512m -Xmx512m"
    ports:
      - "9200:9200"
    volumes:
      - elasticsearch_data:/usr/share/elasticsearch/data
    networks:
      - elasticsearch_network
    healthcheck:
      test: ["CMD-SHELL", "curl -f http://localhost:9200/_cluster/health || exit 1"]
      interval: 30s
      timeout: 10s
      retries: 5

volumes:
  elasticsearch_data:

networks:
  # ... réseaux existants ...
  elasticsearch_network:
    external: false  # ou true si vous voulez un réseau externe
```

**Important** : Assurez-vous que le service PHP est sur le même réseau qu'Elasticsearch (`elasticsearch_network`).

### Solution 2 : Configuration d'une instance Elasticsearch externe

Si vous avez une instance Elasticsearch externe (sur un autre serveur ou service cloud), configurez la variable d'environnement :

```bash
# Dans votre fichier .env ou variables d'environnement Docker
ELASTICSEARCH_HOST=http://votre-serveur-elasticsearch:9200
```

**Exemples** :
- Instance locale : `ELASTICSEARCH_HOST=http://localhost:9200`
- Instance distante : `ELASTICSEARCH_HOST=http://192.168.1.100:9200`
- Service cloud (Elastic Cloud) : `ELASTICSEARCH_HOST=https://votre-cluster.es.region.cloud.es.io:9243`

### Solution 3 : Désactiver Elasticsearch temporairement

Si vous ne pouvez pas configurer Elasticsearch immédiatement, vous pouvez le désactiver :

```bash
# Dans votre fichier .env ou variables d'environnement Docker
ELASTICSEARCH_ENABLED=false
```

**Note** : Lorsque Elasticsearch est désactivé :
- Les recherches de produits retourneront des résultats vides
- Les suggestions de recherche ne fonctionneront pas
- L'application continuera de fonctionner normalement pour les autres fonctionnalités
- Les erreurs de connexion ne seront plus loggées

### Solution 4 : Vérifier la connectivité

Pour vérifier si Elasticsearch est accessible depuis le conteneur PHP :

```bash
# Depuis le conteneur PHP
docker exec -it joy-pharma-back-php curl http://elasticsearch:9200

# Ou depuis l'hôte
curl http://localhost:9200
```

**Ou utilisez la commande Symfony de vérification** :

```bash
# Depuis le conteneur PHP
docker exec -it joy-pharma-back-php bin/console app:check-elasticsearch

# Cette commande affichera :
# - L'état de connexion à Elasticsearch
# - Si l'index existe
# - Des suggestions de dépannage si nécessaire
```

## Vérification de la configuration

### Variables d'environnement disponibles

| Variable | Description | Défaut |
|----------|-------------|--------|
| `ELASTICSEARCH_HOST` | URL du serveur Elasticsearch | `http://elasticsearch:9200` |
| `ELASTICSEARCH_ENABLED` | Activer/désactiver Elasticsearch | `true` |
| `ELASTICSEARCH_INDEX_PREFIX` | Préfixe pour les index | `joy_pharma` |

### Vérifier l'état d'Elasticsearch dans l'application

Vous pouvez vérifier si Elasticsearch est disponible en utilisant la méthode `isAvailable()` du service :

```php
$elasticsearchService->isAvailable(); // Retourne true/false
$elasticsearchService->checkAvailability(); // Vérifie la connexion
```

## Logs et diagnostic

Les erreurs Elasticsearch sont maintenant loggées avec plus de détails :

- **Niveau ERROR** : Erreurs de connexion et opérations échouées
- **Niveau WARNING** : Elasticsearch indisponible mais application continue
- **Niveau DEBUG** : Opérations ignorées car Elasticsearch est désactivé

### Exemple de logs

```
[ERROR] Elasticsearch availability check failed
  hosts: ["http://elasticsearch:9200"]
  error: "No alive nodes. All the 1 nodes seem to be down."
```

## Réindexation après résolution

Une fois Elasticsearch configuré et accessible, réindexez les produits :

```bash
# Via la commande Symfony
docker exec -it joy-pharma-back-php bin/console app:reindex-products

# Ou via Makefile
make elasticsearch-reindex
```

## Support

Pour plus d'informations sur la configuration Elasticsearch, consultez :
- [Documentation API de recherche](./API_SEARCH.md)
- [Documentation Elasticsearch](https://www.elastic.co/guide/en/elasticsearch/reference/current/index.html)

