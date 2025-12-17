# API - Suggestions de Recherche avec KNN Similarity

## 📋 Aperçu

Cette API fournit des suggestions de recherche intelligentes pour les produits en utilisant des techniques de recherche KNN (K-Nearest Neighbors) similarity via Elasticsearch. Le système utilise plusieurs stratégies combinées pour trouver les résultats les plus pertinents :

- **N-gram et Edge N-gram** : Pour la similarité de caractères
- **Match phrase prefix** : Pour l'autocomplétion en temps réel
- **Fuzzy matching** : Pour tolérer les fautes de frappe
- **Scoring pondéré** : Pour prioriser les meilleurs résultats

## 🔗 Endpoints

### 1. Suggestions Simples (Titres uniquement)

```
GET /api/products/search/suggestions
```

Retourne une liste de titres de produits correspondant à la requête.

#### Paramètres Query

| Paramètre | Type | Requis | Défaut | Description |
|-----------|------|--------|--------|-------------|
| `q` | string | ✅ Oui | - | Requête de recherche (minimum 1 caractère) |
| `limit` | integer | ❌ Non | 10 | Nombre maximum de suggestions (max: 20) |
| `metadata` | boolean | ❌ Non | false | Inclure les métadonnées de performance |

#### Exemple de requête

```bash
GET /api/products/search/suggestions?q=doli&limit=5&metadata=true
```

#### Réponse (200 OK)

```json
{
  "suggestions": [
    "DOLIPRANE 1000MG COMPRIMÉ",
    "DOLIPRANE 500MG COMPRIMÉ",
    "DOLIPRANE 100MG SUPPOSITOIRE",
    "DOLIRHUME PARACETAMOL",
    "DOLKO PARACETAMOL 500MG"
  ],
  "query": "doli",
  "count": 5,
  "metadata": {
    "search_type": "knn_similarity",
    "elapsed_time_ms": 12.45,
    "limit": 5,
    "query_length": 4
  }
}
```

### 2. Suggestions Détaillées (Produits complets)

```
GET /api/products/search/suggestions/detailed
```

Retourne des suggestions avec toutes les informations produit (prix, stock, images, etc.).

#### Paramètres Query

| Paramètre | Type | Requis | Défaut | Description |
|-----------|------|--------|--------|-------------|
| `q` | string | ✅ Oui | - | Requête de recherche |
| `limit` | integer | ❌ Non | 5 | Nombre maximum de suggestions (max: 10) |

#### Exemple de requête

```bash
GET /api/products/search/suggestions/detailed?q=paracetamol&limit=3
```

#### Réponse (200 OK)

```json
{
  "suggestions": [
    {
      "id": 123,
      "name": "DOLIPRANE PARACETAMOL 1000MG",
      "code": "MED001234",
      "description": "Antalgique et antipyrétique à base de paracétamol",
      "unitPrice": 5.50,
      "totalPrice": 5.50,
      "stock": 150,
      "isActive": true,
      "image": {
        "id": 45,
        "contentUrl": "/media/products/doliprane_1000.jpg"
      },
      "brand": {
        "id": 5,
        "name": "Sanofi"
      },
      "manufacturer": {
        "id": 12,
        "name": "Sanofi-Aventis"
      },
      "form": {
        "id": 3,
        "label": "Comprimé"
      },
      "category": [
        {
          "id": 10,
          "name": "Médicaments",
          "slug": "medicaments"
        }
      ],
      "currency": {
        "id": 1,
        "label": "MGA"
      }
    },
    {
      "id": 456,
      "name": "EFFERALGAN PARACETAMOL 500MG",
      "code": "MED005678",
      "description": "Comprimés effervescents de paracétamol",
      "unitPrice": 3.80,
      "totalPrice": 3.80,
      "stock": 200,
      "isActive": true,
      "image": {
        "id": 67,
        "contentUrl": "/media/products/efferalgan_500.jpg"
      },
      "brand": {
        "id": 8,
        "name": "UPSA"
      },
      "manufacturer": {
        "id": 15,
        "name": "Bristol-Myers Squibb"
      },
      "form": {
        "id": 5,
        "label": "Comprimé effervescent"
      },
      "category": [
        {
          "id": 10,
          "name": "Médicaments",
          "slug": "medicaments"
        }
      ],
      "currency": {
        "id": 1,
        "label": "MGA"
      }
    }
  ],
  "query": "paracetamol",
  "count": 2,
  "metadata": {
    "search_type": "detailed_knn_similarity",
    "elapsed_time_ms": 18.32,
    "limit": 3
  }
}
```

## 🔍 Comment fonctionne la recherche KNN Similarity ?

### Stratégies de recherche combinées

L'API utilise **8 stratégies différentes** combinées pour trouver les produits les plus similaires :

#### 1. **Match Phrase Prefix** (Boost: 5.0)
- Meilleur pour l'autocomplétion
- Trouve "Doliprane" quand vous tapez "Doli"

```json
{
  "match_phrase_prefix": {
    "name": {
      "query": "doli",
      "boost": 5.0
    }
  }
}
```

#### 2. **Edge N-gram** (Boost: 4.0)
- Excellent pour la recherche "as-you-type"
- Analyse les préfixes de 2 à 10 caractères
- Similaire au KNN en trouvant les termes qui commencent pareil

#### 3. **N-gram** (Boost: 3.0)
- Trouve des similarités de sous-chaînes
- Permet de trouver "paracétamol" même si on tape "acetamol"
- Analyse par segments de 2-3 caractères

#### 4. **Fuzzy Match** (Boost: 3.5)
- Tolère les fautes de frappe
- Distance de Levenshtein automatique
- Trouve "Doliprane" même si vous tapez "Dolipran" ou "Doliprane"

#### 5. **Prefix Match** (Boost: 4.5)
- Matching exact au début du mot
- Très rapide et précis

#### 6. **Flexible Match** (Boost: 2.0)
- Matching avec opérateur OR
- Casting plus large pour trouver des variations

#### 7-8. **Code Product Search**
- Recherche sur les codes produits
- Utile pour les références médicales

### Scoring et Pertinence

Chaque stratégie a un **boost** (pondération) qui influence le score final :
- Plus le boost est élevé, plus la stratégie est prioritaire
- Elasticsearch calcule un score composite
- Les résultats sont triés par score décroissant

### Exemple de scoring

Pour la requête `"doli"` :

| Produit | Score | Raison |
|---------|-------|--------|
| **DOLIPRANE 1000MG** | 45.8 | Prefix exact + phrase match parfait |
| **DOLKO 500MG** | 32.1 | Prefix partiel + n-gram match |
| **PANADOL** | 8.3 | N-gram match faible |

## 📊 Cas d'usage

### 1. Barre de recherche avec autocomplétion

**Scénario** : Afficher des suggestions pendant que l'utilisateur tape

```javascript
// Debounce pour éviter trop de requêtes
const searchInput = document.getElementById('search');
let timeoutId;

searchInput.addEventListener('input', (e) => {
  clearTimeout(timeoutId);
  
  timeoutId = setTimeout(async () => {
    const query = e.target.value;
    
    if (query.length >= 1) {
      const response = await fetch(
        `/api/products/search/suggestions?q=${encodeURIComponent(query)}&limit=10`
      );
      const data = await response.json();
      
      // Afficher les suggestions
      displaySuggestions(data.suggestions);
    }
  }, 300); // Attendre 300ms après la dernière frappe
});
```

### 2. Recherche avec correction de fautes

**Scénario** : L'utilisateur fait une faute de frappe

```bash
# Requête avec faute : "dolipran" au lieu de "doliprane"
GET /api/products/search/suggestions?q=dolipran

# Résultat : Le système trouve quand même "DOLIPRANE" grâce au fuzzy matching
```

### 3. Recherche partielle

**Scénario** : L'utilisateur ne connaît qu'une partie du nom

```bash
# Requête partielle : "acetamol" (partie de "paracétamol")
GET /api/products/search/suggestions?q=acetamol

# Résultat : Trouve tous les produits contenant "paracétamol" grâce au n-gram
```

### 4. Suggestions enrichies pour affichage visuel

**Scénario** : Afficher des cartes produit dans les suggestions

```javascript
async function getProductSuggestions(query) {
  const response = await fetch(
    `/api/products/search/suggestions/detailed?q=${encodeURIComponent(query)}&limit=5`
  );
  const data = await response.json();
  
  // Afficher des cartes produit complètes avec images, prix, etc.
  data.suggestions.forEach(product => {
    console.log(`${product.name} - ${product.unitPrice} ${product.currency.label}`);
    console.log(`Stock: ${product.stock}`);
    console.log(`Image: ${product.image?.contentUrl}`);
  });
}
```

## 🔧 Exemples d'utilisation

### cURL - Suggestions simples

```bash
curl -X GET "https://api.joypharma.com/api/products/search/suggestions?q=doli&limit=5" \
  -H "Accept: application/json"
```

### cURL - Suggestions détaillées

```bash
curl -X GET "https://api.joypharma.com/api/products/search/suggestions/detailed?q=paracetamol&limit=3" \
  -H "Accept: application/json"
```

### JavaScript (Fetch API)

```javascript
// Suggestions simples
async function getSimpleSuggestions(query) {
  const response = await fetch(
    `/api/products/search/suggestions?q=${encodeURIComponent(query)}&limit=10&metadata=true`
  );
  
  if (!response.ok) {
    throw new Error('Erreur de recherche');
  }
  
  const data = await response.json();
  console.log(`Trouvé ${data.count} suggestions en ${data.metadata.elapsed_time_ms}ms`);
  
  return data.suggestions;
}

// Suggestions détaillées
async function getDetailedSuggestions(query) {
  const response = await fetch(
    `/api/products/search/suggestions/detailed?q=${encodeURIComponent(query)}&limit=5`
  );
  
  const data = await response.json();
  return data.suggestions; // Retourne les objets produit complets
}

// Utilisation
const suggestions = await getSimpleSuggestions('doliprane');
suggestions.forEach(title => console.log(title));
```

### JavaScript (Axios)

```javascript
import axios from 'axios';

// Classe de service pour les suggestions
class SuggestionService {
  constructor(baseURL) {
    this.client = axios.create({ baseURL });
  }

  async getSimpleSuggestions(query, limit = 10) {
    try {
      const { data } = await this.client.get('/api/products/search/suggestions', {
        params: { q: query, limit, metadata: true }
      });
      return data;
    } catch (error) {
      console.error('Erreur de recherche:', error.message);
      return { suggestions: [], count: 0 };
    }
  }

  async getDetailedSuggestions(query, limit = 5) {
    try {
      const { data } = await this.client.get('/api/products/search/suggestions/detailed', {
        params: { q: query, limit }
      });
      return data;
    } catch (error) {
      console.error('Erreur de recherche:', error.message);
      return { suggestions: [], count: 0 };
    }
  }
}

// Utilisation
const service = new SuggestionService('https://api.joypharma.com');
const result = await service.getSimpleSuggestions('doli', 5);
console.log(result);
```

### React Hook personnalisé

```javascript
import { useState, useEffect } from 'react';
import axios from 'axios';

function useProductSuggestions(query, limit = 10) {
  const [suggestions, setSuggestions] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  useEffect(() => {
    if (!query || query.length < 1) {
      setSuggestions([]);
      return;
    }

    const timeoutId = setTimeout(async () => {
      setLoading(true);
      setError(null);

      try {
        const { data } = await axios.get('/api/products/search/suggestions', {
          params: { q: query, limit }
        });
        setSuggestions(data.suggestions);
      } catch (err) {
        setError(err.message);
        setSuggestions([]);
      } finally {
        setLoading(false);
      }
    }, 300); // Debounce 300ms

    return () => clearTimeout(timeoutId);
  }, [query, limit]);

  return { suggestions, loading, error };
}

// Utilisation dans un composant
function SearchBar() {
  const [query, setQuery] = useState('');
  const { suggestions, loading } = useProductSuggestions(query);

  return (
    <div>
      <input
        value={query}
        onChange={(e) => setQuery(e.target.value)}
        placeholder="Rechercher un produit..."
      />
      {loading && <div>Chargement...</div>}
      <ul>
        {suggestions.map((title, index) => (
          <li key={index}>{title}</li>
        ))}
      </ul>
    </div>
  );
}
```

### Python (Requests)

```python
import requests
from typing import List, Dict, Optional

class ProductSuggestionAPI:
    def __init__(self, base_url: str = "https://api.joypharma.com"):
        self.base_url = base_url
        
    def get_simple_suggestions(
        self, 
        query: str, 
        limit: int = 10, 
        metadata: bool = False
    ) -> Dict:
        """Obtenir des suggestions simples (titres uniquement)"""
        url = f"{self.base_url}/api/products/search/suggestions"
        params = {
            "q": query,
            "limit": limit,
            "metadata": str(metadata).lower()
        }
        
        response = requests.get(url, params=params)
        response.raise_for_status()
        return response.json()
    
    def get_detailed_suggestions(
        self, 
        query: str, 
        limit: int = 5
    ) -> Dict:
        """Obtenir des suggestions détaillées (produits complets)"""
        url = f"{self.base_url}/api/products/search/suggestions/detailed"
        params = {"q": query, "limit": limit}
        
        response = requests.get(url, params=params)
        response.raise_for_status()
        return response.json()

# Utilisation
api = ProductSuggestionAPI()

# Suggestions simples
result = api.get_simple_suggestions("doli", limit=5, metadata=True)
print(f"Trouvé {result['count']} suggestions")
for title in result['suggestions']:
    print(f"- {title}")

# Suggestions détaillées
result = api.get_detailed_suggestions("paracetamol", limit=3)
for product in result['suggestions']:
    print(f"{product['name']} - {product['unitPrice']} {product['currency']['label']}")
```

### PHP

```php
<?php

class ProductSuggestionAPI {
    private string $baseUrl;
    
    public function __construct(string $baseUrl = "https://api.joypharma.com") {
        $this->baseUrl = $baseUrl;
    }
    
    public function getSimpleSuggestions(
        string $query, 
        int $limit = 10, 
        bool $metadata = false
    ): array {
        $url = $this->baseUrl . '/api/products/search/suggestions?' . http_build_query([
            'q' => $query,
            'limit' => $limit,
            'metadata' => $metadata ? 'true' : 'false'
        ]);
        
        $response = file_get_contents($url);
        return json_decode($response, true);
    }
    
    public function getDetailedSuggestions(string $query, int $limit = 5): array {
        $url = $this->baseUrl . '/api/products/search/suggestions/detailed?' . http_build_query([
            'q' => $query,
            'limit' => $limit
        ]);
        
        $response = file_get_contents($url);
        return json_decode($response, true);
    }
}

// Utilisation
$api = new ProductSuggestionAPI();

// Suggestions simples
$result = $api->getSimpleSuggestions("doli", 5, true);
echo "Trouvé {$result['count']} suggestions\n";
foreach ($result['suggestions'] as $title) {
    echo "- $title\n";
}

// Suggestions détaillées
$result = $api->getDetailedSuggestions("paracetamol", 3);
foreach ($result['suggestions'] as $product) {
    echo "{$product['name']} - {$product['unitPrice']} {$product['currency']['label']}\n";
}
?>
```

## ⚡ Performance

### Benchmarks typiques

| Nombre de produits | Temps de réponse (simple) | Temps de réponse (détaillé) |
|---------------------|----------------------------|------------------------------|
| 1,000 | 5-10ms | 10-15ms |
| 10,000 | 10-20ms | 15-25ms |
| 100,000 | 15-30ms | 20-40ms |
| 1,000,000 | 20-50ms | 30-60ms |

### Optimisations recommandées

1. **Debouncing côté client** : Attendre 300-500ms après la dernière frappe
2. **Caching** : Mettre en cache les résultats fréquents
3. **Limite raisonnable** : Ne pas dépasser 10-20 suggestions
4. **CDN** : Utiliser un CDN pour les images de produits

## 🔄 Migration et Réindexation

### Réindexer les produits avec les nouveaux analyseurs

```bash
# Via la console Symfony
php bin/console app:reindex-products

# Cela va :
# 1. Créer l'index avec les nouveaux mapping et analyseurs
# 2. Indexer tous les produits actifs
# 3. Optimiser l'index pour les recherches
```

### Vérifier l'index Elasticsearch

```bash
# Vérifier que l'index existe
curl -X GET "http://localhost:9200/joy_pharma_products"

# Vérifier le mapping
curl -X GET "http://localhost:9200/joy_pharma_products/_mapping"

# Vérifier les analyseurs
curl -X GET "http://localhost:9200/joy_pharma_products/_settings"
```

## ❌ Gestion des erreurs

### Requête vide

```json
{
  "suggestions": [],
  "query": "",
  "count": 0,
  "metadata": {
    "search_type": "empty",
    "elapsed_time_ms": 0
  }
}
```

### Elasticsearch indisponible

Si Elasticsearch est inaccessible, l'API retourne gracieusement :

```json
{
  "suggestions": [],
  "query": "doliprane",
  "count": 0
}
```

Un message d'erreur est loggé côté serveur pour investigation.

## 🚀 Évolution future : Vraie recherche KNN avec vecteurs

### Support des embeddings vectoriels

Pour une recherche KNN encore plus avancée, l'index supporte (en commentaire) un champ vectoriel :

```php
'name_vector' => [
    'type' => 'dense_vector',
    'dims' => 384,
    'index' => true,
    'similarity' => 'cosine'
]
```

### Workflow avec embeddings

1. **Générer des embeddings** pour chaque produit (ex: avec BERT, Sentence Transformers)
2. **Stocker les vecteurs** dans Elasticsearch
3. **Recherche KNN** avec la requête vectorisée

```json
{
  "knn": {
    "field": "name_vector",
    "query_vector": [0.45, 0.23, ...],
    "k": 10,
    "num_candidates": 100
  }
}
```

Cette approche offrirait une recherche sémantique encore plus puissante.

## 📝 Notes importantes

1. **Elasticsearch requis** : L'API nécessite qu'Elasticsearch soit accessible
2. **Index initialisé** : Les produits doivent être indexés (commande `app:reindex-products`)
3. **Produits actifs uniquement** : Seuls les produits avec `isActive = true` sont inclus
4. **Performance** : Les n-grams peuvent augmenter la taille de l'index (~30-50%)
5. **Langue** : Les analyseurs sont configurés pour le français et l'anglais

## 🔍 Débogage

### Vérifier les logs

```bash
# Logs Symfony
tail -f var/log/dev.log | grep -i "elasticsearch"

# Logs Elasticsearch
docker logs elasticsearch -f
```

### Tester manuellement Elasticsearch

```bash
# Test de recherche directe
curl -X POST "http://localhost:9200/joy_pharma_products/_search" \
  -H 'Content-Type: application/json' \
  -d '{
    "query": {
      "match": {
        "name": "doliprane"
      }
    }
  }'
```

### Problèmes courants

| Problème | Solution |
|----------|----------|
| Aucun résultat | Vérifier que l'index est peuplé avec `app:reindex-products` |
| Elasticsearch timeout | Augmenter le timeout dans la config |
| Résultats non pertinents | Ajuster les boost dans `searchTitleSuggestions()` |
| Index trop gros | Réduire les n-gram (min_gram/max_gram) |

## 📚 Ressources supplémentaires

- [Documentation Elasticsearch N-gram](https://www.elastic.co/guide/en/elasticsearch/reference/current/analysis-ngram-tokenizer.html)
- [Documentation Elasticsearch KNN Search](https://www.elastic.co/guide/en/elasticsearch/reference/current/knn-search.html)
- [Best Practices pour l'autocomplétion](https://www.elastic.co/guide/en/elasticsearch/reference/current/search-suggesters.html)

