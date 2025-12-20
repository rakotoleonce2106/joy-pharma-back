# 📚 Documentation Joy Pharma Backend

Bienvenue dans la documentation du backend Joy Pharma ! Ce répertoire contient toutes les ressources nécessaires pour comprendre, développer et maintenir l'API.

## 📖 Table des matières

### 🔐 Authentification & Sécurité

| Document | Description | Niveau |
|----------|-------------|--------|
| [**Refresh Token**](./REFRESH_TOKEN.md) | Guide complet sur .l'authentification JWT et les refresh tokens | ⭐⭐⭐ |
| [**CORS Configuration**](./CORS_CONFIGURATION.md) | Documentation détaillée sur la configuration CORS | ⭐⭐⭐ |
| [**CORS Quick Start**](./CORS_QUICK_START.md) | Guide rapide pour résoudre les problèmes CORS | ⭐ |

### 🚀 Démarrage rapide

#### Authentification JWT

```bash
# Connexion
POST /api/auth
{
  "email": "user@example.com",
  "password": "password123"
}

# Réponse
{
  "token": "eyJ...",
  "refresh_token": "abc123..."
}

# Utilisation
GET /api/orders
Authorization: Bearer eyJ...

# Rafraîchir le token
POST /api/token/refresh
{
  "refresh_token": "abc123..."
}
```

#### Test CORS

```bash
# Tester la configuration CORS
./scripts/test-cors.sh https://api.joy-pharma.com
```

## 🛠️ Configuration

### Prérequis

- PHP 8.1+
- Symfony 6.4+
- PostgreSQL
- Composer
- Docker (optionnel)

### Installation

```bash
# 1. Cloner le projet
git clone https://github.com/your-org/joy-pharma-back.git
cd joy-pharma-back

# 2. Installer les dépendances
composer install

# 3. Configurer les variables d'environnement
cp .env .env.local
# Éditer .env.local avec vos configurations

# 4. Générer les clés JWT
php bin/console lexik:jwt:generate-keypair

# 5. Créer la base de données
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

# 6. Lancer le serveur
symfony server:start
# Ou avec Docker
docker-compose up -d
```

### Variables d'environnement importantes

```env
# Base de données
DATABASE_URL="postgresql://user:password@127.0.0.1:5432/joy_pharma?serverVersion=15&charset=utf8"

# JWT
JWT_PASSPHRASE=votre_passphrase_securisee

# CORS (géré automatiquement)
# Voir config/packages/nelmio_cors.yaml

# App
APP_ENV=dev
APP_SECRET=your_secret_here
```

## 🧪 Tests

### Test manuel avec cURL

```bash
# Connexion
curl -X POST 'http://localhost:8000/api/auth' \
  -H 'Content-Type: application/json' \
  -d '{
    "email": "user@example.com",
    "password": "password123"
  }'

# Requête authentifiée
curl -X GET 'http://localhost:8000/api/orders' \
  -H 'Authorization: Bearer YOUR_TOKEN'

# Test CORS
curl -X OPTIONS 'http://localhost:8000/api/products' \
  -H 'Origin: http://localhost:3000' \
  -H 'Access-Control-Request-Method: POST' \
  -v
```

### Script de test CORS

```bash
# Tester en local
./scripts/test-cors.sh http://localhost:8000

# Tester en production
./scripts/test-cors.sh https://api.joy-pharma.com
```

## 📡 Endpoints principaux

### Authentification

| Méthode | Endpoint | Description | Auth |
|---------|----------|-------------|------|
| `POST` | `/api/auth` | Connexion | ❌ |
| `POST` | `/api/register` | Inscription | ❌ |
| `POST` | `/api/token/refresh` | Rafraîchir le token | ❌ |
| `POST` | `/api/password/forgot` | Mot de passe oublié | ❌ |
| `POST` | `/api/password/reset` | Réinitialiser le mot de passe | ❌ |

### Produits

| Méthode | Endpoint | Description | Auth |
|---------|----------|-------------|------|
| `GET` | `/api/products` | Liste des produits | ❌ |
| `GET` | `/api/products/{id}` | Détails d'un produit | ❌ |
| `POST` | `/api/products` | Créer un produit | ✅ Admin |
| `PUT` | `/api/products/{id}` | Modifier un produit | ✅ Admin |
| `DELETE` | `/api/products/{id}` | Supprimer un produit | ✅ Admin |

### Commandes

| Méthode | Endpoint | Description | Auth |
|---------|----------|-------------|------|
| `GET` | `/api/orders` | Liste des commandes | ✅ |
| `POST` | `/api/orders` | Créer une commande | ✅ |
| `GET` | `/api/orders/{id}` | Détails d'une commande | ✅ |
| `PUT` | `/api/orders/{id}` | Modifier une commande | ✅ |

### Utilisateurs

| Méthode | Endpoint | Description | Auth |
|---------|----------|-------------|------|
| `GET` | `/api/users/me` | Profil de l'utilisateur connecté | ✅ |
| `PUT` | `/api/users/me` | Modifier son profil | ✅ |
| `GET` | `/api/users` | Liste des utilisateurs | ✅ Admin |

## 🏗️ Architecture

### Structure du projet

```
joy-pharma-back/
├── config/
│   ├── packages/          # Configuration des bundles
│   │   ├── security.yaml  # Configuration de sécurité
│   │   ├── nelmio_cors.yaml  # Configuration CORS
│   │   └── lexik_jwt_authentication.yaml  # Configuration JWT
│   └── routes/            # Routes
├── docs/                  # 📚 Documentation (vous êtes ici)
├── migrations/            # Migrations de base de données
├── public/                # Point d'entrée public
├── scripts/               # Scripts utilitaires
│   └── test-cors.sh      # Script de test CORS
├── src/
│   ├── Controller/        # Contrôleurs
│   ├── Entity/            # Entités Doctrine
│   │   ├── User.php      # Utilisateur
│   │   ├── RefreshToken.php  # Refresh token
│   │   ├── Product.php   # Produit
│   │   └── Order.php     # Commande
│   ├── EventSubscriber/   # Event subscribers
│   │   ├── CorsErrorSubscriber.php  # CORS sur erreurs
│   │   └── ApiExceptionSubscriber.php  # Gestion des exceptions
│   ├── Repository/        # Repositories
│   ├── Security/          # Classes de sécurité
│   ├── Service/           # Services métier
│   └── State/             # Processors API Platform
└── vendor/                # Dépendances

```

### Bundles utilisés

- **API Platform** - Framework API REST
- **LexikJWTAuthenticationBundle** - Authentification JWT
- **GesdinetJWTRefreshTokenBundle** - Refresh tokens
- **NelmioCorsBundle** - Gestion CORS
- **Doctrine ORM** - ORM base de données
- **VichUploaderBundle** - Upload de fichiers

## 🔧 Commandes utiles

### Développement

```bash
# Lancer le serveur de développement
symfony server:start

# Créer une entité
php bin/console make:entity

# Créer une migration
php bin/console make:migration

# Exécuter les migrations
php bin/console doctrine:migrations:migrate

# Vider le cache
php bin/console cache:clear

# Lister les routes
php bin/console debug:router

# Lister les event subscribers
php bin/console debug:event-dispatcher
```

### Production

```bash
# Installer les dépendances de production
composer install --no-dev --optimize-autoloader

# Vider le cache de production
php bin/console cache:clear --env=prod

# Exécuter les migrations
php bin/console doctrine:migrations:migrate --no-interaction

# Nettoyer les refresh tokens expirés
php bin/console gesdinet:jwt:clear
```

### Docker

```bash
# Lancer les containers
docker-compose up -d

# Arrêter les containers
docker-compose down

# Voir les logs
docker-compose logs -f

# Exécuter une commande dans le container
docker-compose exec php bin/console cache:clear
```

## 🐛 Débogage

### Vérifier la configuration

```bash
# Configuration complète
php bin/console debug:config

# Configuration d'un bundle spécifique
php bin/console debug:config nelmio_cors
php bin/console debug:config lexik_jwt_authentication

# Configuration de la sécurité
php bin/console debug:config security
```

### Logs

```bash
# Logs en temps réel
tail -f var/log/dev.log

# Logs d'erreur uniquement
tail -f var/log/dev.log | grep ERROR

# Logs JWT/Auth
tail -f var/log/dev.log | grep -i "jwt\|auth"

# Logs CORS
tail -f var/log/dev.log | grep -i "cors"
```

## 📊 Monitoring

### Healthcheck

```bash
# Vérifier que l'API répond
curl http://localhost:8000/api

# Vérifier la base de données
php bin/console doctrine:query:sql "SELECT 1"
```

## 🚀 Déploiement

### Avec Docker

```bash
# Build l'image
docker build -t joy-pharma-back .

# Lancer le container
docker run -d -p 8000:8000 joy-pharma-back
```

### Manuel

```bash
# 1. Pull les dernières modifications
git pull origin main

# 2. Installer les dépendances
composer install --no-dev

# 3. Exécuter les migrations
php bin/console doctrine:migrations:migrate --no-interaction

# 4. Vider le cache
php bin/console cache:clear --env=prod

# 5. Redémarrer le serveur
sudo systemctl restart php-fpm
```

## 🔒 Sécurité

### Checklist de sécurité

- ✅ HTTPS activé en production
- ✅ JWT avec clés RSA
- ✅ Refresh tokens avec rotation
- ✅ CORS configuré avec liste blanche
- ✅ Rate limiting (à configurer si nécessaire)
- ✅ Validation des entrées
- ✅ Headers de sécurité (à configurer si nécessaire)

### Configuration recommandée pour la production

1. **HTTPS uniquement**
   ```yaml
   # config/packages/framework.yaml
   framework:
       router:
           strict_requirements: true
           canonical_url: 'https://api.joy-pharma.com'
   ```

2. **Headers de sécurité** (à ajouter dans Nginx/Apache ou Symfony)
   ```
   Strict-Transport-Security: max-age=31536000
   X-Content-Type-Options: nosniff
   X-Frame-Options: DENY
   X-XSS-Protection: 1; mode=block
   ```

3. **Rate limiting** (recommandé avec API Platform)

## 📞 Support

### Documentation officielle

- [Symfony](https://symfony.com/doc/current/index.html)
- [API Platform](https://api-platform.com/docs/)
- [Doctrine](https://www.doctrine-project.org/projects/doctrine-orm/en/latest/index.html)

### Problèmes courants

| Problème | Solution | Documentation |
|----------|----------|---------------|
| Erreur CORS | Voir [CORS Configuration](./CORS_CONFIGURATION.md) | ⭐⭐⭐ |
| Token expiré | Utiliser le refresh token | [Refresh Token](./REFRESH_TOKEN.md) |
| 401 Unauthorized | Vérifier le token JWT | [Refresh Token](./REFRESH_TOKEN.md) |
| Migrations échouent | Vérifier la connexion DB | - |

## 🤝 Contribution

### Git Workflow

```bash
# 1. Créer une branche
git checkout -b feature/ma-fonctionnalite

# 2. Développer et commiter
git add .
git commit -m "feat: Description de la fonctionnalité"

# 3. Pousser
git push origin feature/ma-fonctionnalite

# 4. Créer une Pull Request sur GitHub
```

### Conventions

- Commits : [Conventional Commits](https://www.conventionalcommits.org/)
- Code : PSR-12
- Documentation : Markdown

## 📄 Licence

Ce projet est sous licence [LICENSE](../LICENSE).

---

**Version :** 1.0.0  
**Dernière mise à jour :** Décembre 2024  
**Équipe :** Joy Pharma

Pour toute question, contactez l'équipe de développement.

