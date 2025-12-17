# 🔗 Comment les URLs MediaObject fonctionnent avec Traefik

## 🎯 Question

> "Comment l'URL dans MediaObject peut avoir accès aux images dans /joy-pharma-data/ ?"

## 📊 Flow complet

```
┌──────────────────────────────────────────────────────────────┐
│  1. Client demande un produit                                 │
│     GET https://api.joypharma.com/api/products/123           │
└────────────────────────┬─────────────────────────────────────┘
                         │
                         ↓
┌──────────────────────────────────────────────────────────────┐
│  2. Traefik route vers le container PHP                       │
│     (basé sur Host: api.joypharma.com)                       │
└────────────────────────┬─────────────────────────────────────┘
                         │
                         ↓
┌──────────────────────────────────────────────────────────────┐
│  3. Symfony/API Platform traite la requête                    │
│     - Récupère le produit depuis PostgreSQL                   │
│     - Product a une relation avec MediaObject                 │
│     - MediaObject.getContentUrl() retourne: "/media/abc.jpg" │
└────────────────────────┬─────────────────────────────────────┘
                         │
                         ↓
┌──────────────────────────────────────────────────────────────┐
│  4. JSON retourné au client                                   │
│     {                                                          │
│       "id": 123,                                              │
│       "name": "DOLIPRANE",                                    │
│       "image": {                                              │
│         "contentUrl": "/media/abc.jpg"  ← URL relative       │
│       }                                                        │
│     }                                                          │
└────────────────────────┬─────────────────────────────────────┘
                         │
                         ↓
┌──────────────────────────────────────────────────────────────┐
│  5. Client veut afficher l'image                              │
│     <img src="https://api.joypharma.com/media/abc.jpg" />    │
│     (URL complète construite côté client)                     │
└────────────────────────┬─────────────────────────────────────┘
                         │
                         ↓
┌──────────────────────────────────────────────────────────────┐
│  6. Traefik reçoit la demande d'image                         │
│     GET https://api.joypharma.com/media/abc.jpg              │
└────────────────────────┬─────────────────────────────────────┘
                         │
                         ↓
┌──────────────────────────────────────────────────────────────┐
│  7. Traefik route vers le container PHP                       │
└────────────────────────┬─────────────────────────────────────┘
                         │
                         ↓
┌──────────────────────────────────────────────────────────────┐
│  8. FrankenPHP sert le fichier statique                       │
│     Chemin demandé : /media/abc.jpg                          │
│     Fichier servi depuis : /app/public/media/abc.jpg         │
│     (monté vers /joy-pharma-data/media/abc.jpg)              │
└────────────────────────┬─────────────────────────────────────┘
                         │
                         ↓
┌──────────────────────────────────────────────────────────────┐
│  9. Client reçoit l'image ✅                                  │
└──────────────────────────────────────────────────────────────┘
```

---

## 🔍 Détails techniques

### 1. MediaObject retourne une URL relative

**Code : `src/Entity/MediaObject.php`**

```php
public function getContentUrl(): ?string
{
    if ($this->filePath) {
        // $this->filePath = "abc123-uuid.jpg"
        // Retourne : "/media/abc123-uuid.jpg"
        return '/media/' . $this->filePath;
    }
    return null;
}
```

**Résultat dans l'API** :

```json
{
  "image": {
    "contentUrl": "/media/abc123-uuid.jpg"
  }
}
```

### 2. Client construit l'URL complète

**Frontend (React/Vue/etc.)** :

```javascript
// L'API retourne : "/media/abc123-uuid.jpg"
const imageUrl = product.image.contentUrl;

// Le client construit l'URL complète
const fullUrl = `https://api.joypharma.com${imageUrl}`;
// Résultat : "https://api.joypharma.com/media/abc123-uuid.jpg"

// Ou dans une balise <img>
<img src={`https://api.joypharma.com${imageUrl}`} />
```

### 3. Traefik route la requête

**Configuration Traefik (dans `compose.prod.yaml`)** :

```yaml
labels:
  # Toutes les requêtes vers api.joypharma.com vont au container PHP
  - "traefik.http.routers.joy-pharma-backend.rule=Host(`api.joypharma.com`)"
  - "traefik.http.routers.joy-pharma-backend.entrypoints=websecure"
  - "traefik.http.services.joy-pharma-backend.loadbalancer.server.port=80"
```

**Ce que Traefik fait** :

```
Request: GET https://api.joypharma.com/media/abc.jpg
                        ↓
Traefik vérifie le Host: api.joypharma.com
                        ↓
Route vers container PHP (port 80)
                        ↓
Container reçoit: GET /media/abc.jpg
```

### 4. FrankenPHP sert le fichier

**FrankenPHP** (serveur web intégré) :

```
Request: GET /media/abc.jpg
            ↓
FrankenPHP cherche : /app/public/media/abc.jpg
            ↓
Volume Docker monté : /joy-pharma-data/media/abc.jpg
            ↓
Fichier trouvé ✅
            ↓
Retourne l'image avec headers appropriés
```

---

## 📁 Mapping des chemins

| Type | Chemin MediaObject | URL | Chemin Container | Chemin Serveur |
|------|-------------------|-----|------------------|----------------|
| **VichUploader** | `filePath = "abc.jpg"` | `/media/abc.jpg` | `/app/public/media/abc.jpg` | `/joy-pharma-data/media/abc.jpg` |
| **Images statiques** | N/A | `/images/products/doli.jpg` | `/app/public/images/products/doli.jpg` | `/joy-pharma-data/images/products/doli.jpg` |
| **Images profile** | N/A | `/images/profile/user-1.jpg` | `/app/public/images/profile/user-1.jpg` | `/joy-pharma-data/images/profile/user-1.jpg` |

---

## 🔧 Configuration VichUploader

**Fichier : `config/packages/vich_uploader.yaml`**

```yaml
vich_uploader:
    db_driver: orm
    mappings:
        media_object:
            uri_prefix: /media                                    # 👈 Préfixe URL
            upload_destination: '%kernel.project_dir%/public/media'  # 👈 Dossier physique
            namer: Vich\UploaderBundle\Naming\SmartUniqueNamer
```

**Explication** :

- `uri_prefix: /media` → URLs commencent par `/media/`
- `upload_destination: public/media` → Fichiers sauvegardés dans `/app/public/media/`
- Volume Docker → `/app/public/media/` est monté vers `/joy-pharma-data/media/`

---

## 📱 Exemple complet

### API Request

```bash
curl https://api.joypharma.com/api/products/123
```

### Response

```json
{
  "id": 123,
  "name": "DOLIPRANE 1000MG",
  "image": {
    "id": 45,
    "contentUrl": "/media/6789abcd-1234-5678-90ab-cdef12345678.jpg"
  }
}
```

### Frontend (React)

```jsx
function ProductCard({ product }) {
  const API_BASE = "https://api.joypharma.com";
  
  return (
    <div>
      <h3>{product.name}</h3>
      {product.image && (
        <img 
          src={`${API_BASE}${product.image.contentUrl}`}
          alt={product.name}
        />
      )}
    </div>
  );
}

// L'image sera chargée depuis :
// https://api.joypharma.com/media/6789abcd-1234-5678-90ab-cdef12345678.jpg
```

### Image Request

```bash
curl -I https://api.joypharma.com/media/6789abcd-1234-5678-90ab-cdef12345678.jpg
```

### Response Headers

```
HTTP/2 200 
content-type: image/jpeg
content-length: 45678
last-modified: Mon, 17 Dec 2024 10:30:00 GMT
cache-control: public, max-age=31536000
access-control-allow-origin: *
```

---

## 🎨 Différence entre `/media/` et `/images/`

### `/media/` - Uploads dynamiques (VichUploader)

- **Usage** : Fichiers uploadés par les utilisateurs via l'API
- **Gestion** : VichUploader (bundle Symfony)
- **Nommage** : UUID aléatoire (`abc123-uuid.jpg`)
- **Base de données** : Référencé dans la table `media_object`
- **Exemple** : Photo de profil, document uploadé

```
POST /api/media_objects
Content-Type: multipart/form-data

file: [image.jpg]

→ Sauvegardé dans : /joy-pharma-data/media/6789abcd-uuid.jpg
→ URL retournée : /media/6789abcd-uuid.jpg
```

### `/images/` - Images statiques

- **Usage** : Images pré-existantes (produits, logos, etc.)
- **Gestion** : Copiées manuellement via rsync/scp
- **Nommage** : Nom de fichier conservé (`doliprane.jpg`)
- **Base de données** : Non référencé (ou juste le nom dans `product.image_path`)
- **Exemple** : Images produits, placeholder

```
Fichier existant : /joy-pharma-data/images/products/doliprane.jpg
URL accessible : https://api.joypharma.com/images/products/doliprane.jpg
```

---

## ✅ Vérification que tout fonctionne

### Test 1 : API retourne bien les URLs

```bash
curl https://api.joypharma.com/api/products/1 | jq '.image.contentUrl'
```

Résultat attendu :

```json
"/media/abc123.jpg"
```

### Test 2 : Image accessible via URL

```bash
curl -I https://api.joypharma.com/media/abc123.jpg
```

Résultat attendu :

```
HTTP/2 200
content-type: image/jpeg
```

### Test 3 : Volume Docker monté

```bash
ssh user@your-server
cd ~/joy-pharma-back
docker compose exec php ls -lh /app/public/media/ | head
```

Vous devez voir vos fichiers.

### Test 4 : Permissions correctes

```bash
docker compose exec php stat /app/public/media/
```

Résultat attendu :

```
Uid: (   82/www-data)   Gid: (   82/www-data)
```

---

## 🐛 Problèmes courants

### ❌ Image 404 - Fichier non trouvé

**Cause** : Le fichier n'existe pas dans `/joy-pharma-data/media/`

```bash
# Vérifier sur le serveur
sudo ls -la /joy-pharma-data/media/abc123.jpg
```

**Solution** : Le fichier doit être uploadé via l'API ou copié manuellement

### ❌ Image 403 - Permission denied

**Cause** : Mauvaises permissions

```bash
# Corriger
sudo chown -R 82:82 /joy-pharma-data/
sudo chmod -R 755 /joy-pharma-data/
```

### ❌ CORS bloque l'image

**Cause** : Frontend sur un autre domaine

**Solution** : Déjà configuré dans `compose.prod.yaml` :

```yaml
labels:
  - "traefik.http.middlewares.joy-pharma-backend-cors.headers.accesscontrolalloworigin=*"
```

### ❌ Volume non monté

**Cause** : Configuration manquante dans `compose.prod.yaml`

**Solution** : Vérifier que ces lignes existent :

```yaml
volumes:
  - /joy-pharma-data/media:/app/public/media:rw
```

---

## 📝 Récapitulatif

### Comment ça marche en 3 étapes

1. **MediaObject retourne** : `/media/abc.jpg` (URL relative)
2. **Client construit** : `https://api.joypharma.com/media/abc.jpg` (URL complète)
3. **Traefik + FrankenPHP servent** : Le fichier depuis `/joy-pharma-data/media/abc.jpg`

### Magie des volumes Docker

```yaml
volumes:
  - /joy-pharma-data/media:/app/public/media:rw
```

Cette ligne fait le lien entre :
- **Serveur** : `/joy-pharma-data/media/` (persistant)
- **Container** : `/app/public/media/` (visible par FrankenPHP)
- **URL** : `https://api.joypharma.com/media/` (accessible publiquement)

---

## 🎉 Conclusion

**Vos images sont accessibles parce que** :

1. ✅ **MediaObject** retourne les bonnes URLs (`/media/...`)
2. ✅ **Traefik** route les requêtes vers le container PHP
3. ✅ **FrankenPHP** sert les fichiers statiques depuis `/app/public/`
4. ✅ **Volume Docker** monte `/joy-pharma-data/` dans le container
5. ✅ **Permissions** correctes (UID 82)

**Aucun serveur Nginx séparé n'est nécessaire !** FrankenPHP gère tout. 🚀

