# Quick Start - API Suggestions de Recherche

## 🚀 Démarrage rapide en 2 minutes

### 1. Endpoint principal

```
GET /api/products/search/suggestions?q={query}
```

### 2. Exemples rapides

#### cURL
```bash
curl "https://api.joypharma.com/api/products/search/suggestions?q=doli&limit=5"
```

#### JavaScript
```javascript
const response = await fetch('/api/products/search/suggestions?q=doli');
const { suggestions } = await response.json();
console.log(suggestions); // ["DOLIPRANE 1000MG", "DOLIPRANE 500MG", ...]
```

#### React
```jsx
function SearchBar() {
  const [query, setQuery] = useState('');
  const [suggestions, setSuggestions] = useState([]);

  useEffect(() => {
    if (query.length >= 1) {
      fetch(`/api/products/search/suggestions?q=${query}`)
        .then(r => r.json())
        .then(data => setSuggestions(data.suggestions));
    }
  }, [query]);

  return (
    <>
      <input value={query} onChange={e => setQuery(e.target.value)} />
      <ul>
        {suggestions.map((s, i) => <li key={i}>{s}</li>)}
      </ul>
    </>
  );
}
```

## 📊 Structure de réponse

### Simple
```json
{
  "suggestions": ["Produit 1", "Produit 2", ...],
  "query": "doli",
  "count": 5
}
```

### Détaillée (avec `/detailed`)
```json
{
  "suggestions": [
    {
      "id": 123,
      "name": "DOLIPRANE 1000MG",
      "unitPrice": 5.50,
      "stock": 150,
      "image": {
        "contentUrl": "/media/products/doliprane.jpg"
      }
    }
  ],
  "count": 1
}
```

## 🎯 Paramètres

| Param | Type | Défaut | Description |
|-------|------|--------|-------------|
| `q` | string | - | **Requis** - Requête de recherche |
| `limit` | int | 10 | Nombre de résultats (max: 20) |
| `metadata` | bool | false | Inclure métadonnées perf |

## ✨ Fonctionnalités KNN

L'API utilise plusieurs techniques pour trouver les produits les plus similaires :

- ✅ **Autocomplétion** : Tape "doli" → trouve "Doliprane"
- ✅ **Fautes de frappe** : Tape "dolipran" → trouve "Doliprane"
- ✅ **Recherche partielle** : Tape "acetamol" → trouve "Paracétamol"
- ✅ **N-gram similarity** : Recherche par segments de caractères
- ✅ **Scoring intelligent** : Les meilleurs résultats en premier

## 🔥 Tips

### 1. Debouncing (recommandé)
```javascript
let timeoutId;
input.addEventListener('input', (e) => {
  clearTimeout(timeoutId);
  timeoutId = setTimeout(() => {
    // Recherche ici
  }, 300);
});
```

### 2. Cache local
```javascript
const cache = new Map();

async function search(query) {
  if (cache.has(query)) return cache.get(query);
  
  const data = await fetch(`/api/products/search/suggestions?q=${query}`)
    .then(r => r.json());
  
  cache.set(query, data);
  return data;
}
```

### 3. Loader pendant la recherche
```javascript
setLoading(true);
const data = await search(query);
setLoading(false);
```

## 📦 Deux endpoints disponibles

### `/suggestions` - Simple et rapide
- Retourne uniquement les titres
- Ultra rapide (5-20ms)
- Parfait pour l'autocomplétion

### `/suggestions/detailed` - Complet
- Retourne les produits complets
- Avec images, prix, stock, etc.
- Parfait pour affichage visuel

## 🛠️ Setup

### 1. Vérifier Elasticsearch
```bash
curl http://localhost:9200/_cluster/health
```

### 2. Réindexer les produits
```bash
php bin/console app:reindex-products
```

### 3. Tester l'API
```bash
curl "http://localhost/api/products/search/suggestions?q=test"
```

## 📚 Documentation complète

Voir [api-search-suggestions.md](./api-search-suggestions.md) pour :
- Détails techniques KNN
- Exemples avancés
- Configuration Elasticsearch
- Benchmarks performance
- Troubleshooting

## 💡 Exemples réels

### E-commerce Search Bar
```html
<input type="search" id="search" placeholder="Rechercher...">
<div id="suggestions"></div>

<script>
let timeout;
document.getElementById('search').addEventListener('input', (e) => {
  clearTimeout(timeout);
  timeout = setTimeout(async () => {
    const query = e.target.value;
    if (query.length < 1) return;
    
    const response = await fetch(`/api/products/search/suggestions?q=${query}&limit=8`);
    const data = await response.json();
    
    document.getElementById('suggestions').innerHTML = data.suggestions
      .map(s => `<div class="suggestion">${s}</div>`)
      .join('');
  }, 300);
});
</script>
```

### Mobile App (React Native)
```javascript
import { useState, useEffect } from 'react';
import { TextInput, FlatList, Text } from 'react-native';

function SearchScreen() {
  const [query, setQuery] = useState('');
  const [suggestions, setSuggestions] = useState([]);

  useEffect(() => {
    if (query.length >= 1) {
      fetch(`https://api.joypharma.com/api/products/search/suggestions?q=${query}`)
        .then(r => r.json())
        .then(data => setSuggestions(data.suggestions));
    }
  }, [query]);

  return (
    <>
      <TextInput
        value={query}
        onChangeText={setQuery}
        placeholder="Rechercher un produit..."
      />
      <FlatList
        data={suggestions}
        renderItem={({ item }) => <Text>{item}</Text>}
      />
    </>
  );
}
```

## 🎨 Interface utilisateur similaire à l'image

Pour créer une interface comme celle montrée dans l'image :

```css
.search-container {
  position: relative;
}

.search-input {
  width: 100%;
  padding: 12px 40px 12px 16px;
  border: 1px solid #ddd;
  border-radius: 8px;
  font-size: 16px;
}

.suggestions-list {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: white;
  border: 1px solid #ddd;
  border-radius: 8px;
  margin-top: 4px;
  box-shadow: 0 4px 6px rgba(0,0,0,0.1);
  max-height: 300px;
  overflow-y: auto;
}

.suggestion-item {
  padding: 12px 16px;
  cursor: pointer;
  border-bottom: 1px solid #f0f0f0;
  display: flex;
  align-items: center;
  gap: 12px;
}

.suggestion-item:hover {
  background: #f5f5f5;
}

.suggestion-icon {
  color: #999;
  font-size: 18px;
}
```

```html
<div class="search-container">
  <input 
    type="text" 
    class="search-input" 
    placeholder="Rechercher..."
    id="searchInput"
  >
  <div class="suggestions-list" id="suggestionsList" style="display: none;">
    <!-- Les suggestions apparaissent ici -->
  </div>
</div>

<script>
const searchInput = document.getElementById('searchInput');
const suggestionsList = document.getElementById('suggestionsList');
let timeout;

searchInput.addEventListener('input', (e) => {
  clearTimeout(timeout);
  const query = e.target.value;
  
  if (query.length < 1) {
    suggestionsList.style.display = 'none';
    return;
  }
  
  timeout = setTimeout(async () => {
    const response = await fetch(`/api/products/search/suggestions?q=${query}&limit=10`);
    const data = await response.json();
    
    if (data.count > 0) {
      suggestionsList.innerHTML = data.suggestions
        .map(s => `
          <div class="suggestion-item">
            <span class="suggestion-icon">🔍</span>
            <span>${s}</span>
          </div>
        `)
        .join('');
      suggestionsList.style.display = 'block';
    } else {
      suggestionsList.style.display = 'none';
    }
  }, 300);
});

// Cacher les suggestions quand on clique ailleurs
document.addEventListener('click', (e) => {
  if (!e.target.closest('.search-container')) {
    suggestionsList.style.display = 'none';
  }
});
</script>
```

Voilà ! Vous avez maintenant une barre de recherche avec suggestions KNN similaire à l'image. 🎉

