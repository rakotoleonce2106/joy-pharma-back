# 🎉 Bienvenue dans Joy Pharma Backend !

```
╔═══════════════════════════════════════════════════════════╗
║                                                           ║
║    🏥  J O Y   P H A R M A   B A C K E N D   A P I      ║
║                                                           ║
║         Powered by Symfony 7.2 + FrankenPHP              ║
║                                                           ║
╚═══════════════════════════════════════════════════════════╝
```

## 🚀 Démarrage en 30 Secondes

```bash
# Étape 1 : Lancer l'installation
./start.sh

# Étape 2 : Créer un utilisateur admin
make admin-create

# Étape 3 : Ouvrir l'application
open https://localhost
```

**C'est tout !** ✨

## 📖 Documentation Rapide

### 🎯 Guides Essentiels

| Guide | Description | Lien |
|-------|-------------|------|
| 🚀 **Démarrage Rapide** | Installation en 3 étapes | [quickstart.md](docs/quickstart.md) |
| 📥 **Installation** | Guide détaillé d'installation | [INSTALLATION.md](INSTALLATION.md) |
| 🐳 **Docker** | Tout sur Docker et les conteneurs | [DOCKER.md](DOCKER.md) |
| 🚢 **Production** | Déploiement en production | [docs/production.md](docs/production.md) |
| 🤝 **Contribution** | Comment contribuer | [CONTRIBUTING.md](CONTRIBUTING.md) |

### 🔗 Accès Rapides

| Service | URL | Description |
|---------|-----|-------------|
| 🌐 **Application** | https://localhost | Page d'accueil API |
| 📖 **API Docs** | https://localhost/docs | Documentation OpenAPI/Swagger |
| 🔍 **Elasticsearch** | http://localhost:9200 | Moteur de recherche |
| 🗄️ **PostgreSQL** | localhost:5432 | Base de données |

## 🛠️ Commandes Magiques

### Avec Makefile (Recommandé)

```bash
make help          # 📋 Voir toutes les commandes
make up            # ▶️  Démarrer l'application
make down          # ⏹️  Arrêter l'application
make logs          # 📝 Voir les logs
make shell         # 🐚 Shell dans le conteneur
make tests         # ✅ Exécuter les tests
make cache-clear   # 🧹 Vider le cache
make db-migrate    # 📊 Exécuter les migrations
make admin-create  # 👤 Créer un admin
```

### Avec Docker Compose

```bash
# Démarrer
docker compose up -d

# Arrêter
docker compose down

# Logs
docker compose logs -f

# Console Symfony
docker compose exec php bin/console
```

## 🎨 Configuration IDE

### PHPStorm

1. **PHP Interpreter** : Docker → `php` service
2. **XDebug** : Server = `localhost`, Port = `9003`
3. **Database** : PostgreSQL @ `localhost:5432`

### VSCode

1. **Extensions** : PHP Intelephense, Docker, PHP Debug
2. **XDebug** : Voir `.vscode/launch.json` dans [INSTALLATION.md](INSTALLATION.md)

## 🔥 Fonctionnalités Principales

### Stack Technique

- ⚡ **FrankenPHP** - Serveur ultra-rapide (15x plus rapide)
- 🚀 **Symfony 7.2** - Framework PHP moderne
- 📦 **API Platform 4** - API REST/GraphQL
- 🗄️ **PostgreSQL 16** - Base de données
- 🔍 **Elasticsearch 8** - Recherche puissante
- 🔒 **HTTPS automatique** - Let's Encrypt
- 🔄 **Mercure** - Temps réel
- 🐛 **XDebug** - Débogage intégré

### Performances

- ⚡ **Mode Worker** : Application pré-chargée en mémoire
- 🚀 **HTTP/3** : Protocole QUIC pour vitesse max
- 💨 **Early Hints** : Optimisation automatique
- 🗜️ **Compression** : Zstandard + Brotli + Gzip
- 📦 **OPcache** : Cache PHP optimisé

### Sécurité

- 🔒 **HTTPS par défaut** : Certificats automatiques
- 🔑 **JWT Authentication** : Tokens sécurisés
- 🛡️ **CORS configuré** : Protection cross-origin
- ✅ **Validation stricte** : Données validées
- 🔐 **Secrets externalisés** : Configuration sécurisée

## 📊 Architecture

```
┌─────────────────────────────────────────────────┐
│                  Client (HTTPS)                 │
└────────────────────┬────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────┐
│         Caddy (Serveur Web + HTTPS)            │
└────────────────────┬────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────┐
│    FrankenPHP (Serveur Application PHP)        │
│                                                 │
│  ┌───────────────────────────────────────────┐ │
│  │       Symfony 7.2 + API Platform          │ │
│  │                                           │ │
│  │  ┌─────────────┐      ┌───────────────┐ │ │
│  │  │ Controllers │      │   Services    │ │ │
│  │  └─────────────┘      └───────────────┘ │ │
│  │                                           │ │
│  │  ┌─────────────┐      ┌───────────────┐ │ │
│  │  │  Entities   │      │ Repositories  │ │ │
│  │  └─────────────┘      └───────────────┘ │ │
│  └───────────────────────────────────────────┘ │
└────────────┬────────────────────┬───────────────┘
             │                    │
             ▼                    ▼
    ┌─────────────────┐  ┌─────────────────┐
    │   PostgreSQL    │  │  Elasticsearch  │
    │   (Database)    │  │    (Search)     │
    └─────────────────┘  └─────────────────┘
```

## 🎯 Premiers Pas

### 1. Installation ✅

```bash
./start.sh
```

### 2. Créer un Admin 👤

```bash
make admin-create
# Suivez les instructions
```

### 3. Tester l'API 🧪

```bash
# Tester l'endpoint
curl -k https://localhost/api

# Obtenir un token JWT
curl -k -X POST https://localhost/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"votre_password"}'

# Utiliser l'API
curl -k https://localhost/api/users \
  -H "Authorization: Bearer VOTRE_TOKEN"
```

### 4. Développer 💻

```bash
# Modifier le code dans src/
# Les changements sont automatiquement pris en compte (hot-reload)

# Vider le cache si nécessaire
make cache-clear

# Voir les logs
make logs
```

## 🐛 Déboguer avec XDebug

### Activer XDebug

```bash
# Arrêter les conteneurs
make down

# Redémarrer avec XDebug
XDEBUG_MODE=debug make up
```

### Configurer l'IDE

**PHPStorm** :
- Server : `localhost:443`
- IDE Key : `PHPSTORM`
- Path mapping : `/app` → votre dossier local

**VSCode** :
- Port : `9003`
- Path mapping : `/app` → `${workspaceFolder}`

## 📈 Prochaines Étapes

### Niveau Débutant

1. ✅ Suivre le [Guide de Démarrage Rapide](docs/quickstart.md)
2. ✅ Explorer la [Documentation API](https://localhost/docs)
3. ✅ Tester les endpoints avec Postman/Insomnia
4. ✅ Créer votre premier utilisateur

### Niveau Intermédiaire

1. 🔄 Lire la [Documentation Docker](DOCKER.md)
2. 🔄 Configurer votre IDE
3. 🔄 Utiliser XDebug pour déboguer
4. 🔄 Écrire vos premiers tests

### Niveau Avancé

1. 🔄 Optimiser les performances
2. 🔄 Déployer en production ([Guide](docs/production.md))
3. 🔄 Contribuer au projet ([Guide](CONTRIBUTING.md))
4. 🔄 Mettre en place le CI/CD

## 🎓 Ressources d'Apprentissage

### Documentation Officielle

- [Symfony](https://symfony.com/doc/current/index.html)
- [API Platform](https://api-platform.com/docs/)
- [FrankenPHP](https://frankenphp.dev/)
- [Docker](https://docs.docker.com/)
- [PostgreSQL](https://www.postgresql.org/docs/)
- [Elasticsearch](https://www.elastic.co/guide/index.html)

### Tutoriels Vidéo

- [Symfony Casts](https://symfonycasts.com/)
- [API Platform Tutorials](https://api-platform.com/docs/distribution/)

## 🆘 Besoin d'Aide ?

### Documentation

| Problème | Solution |
|----------|----------|
| 🤔 Installation | Voir [INSTALLATION.md](INSTALLATION.md) |
| 🐳 Docker | Voir [DOCKER.md](DOCKER.md) |
| 🚀 Démarrage | Voir [docs/quickstart.md](docs/quickstart.md) |
| 🚢 Production | Voir [docs/production.md](docs/production.md) |
| 🐛 Bugs | Voir section Troubleshooting |

### Support Communautaire

- 🐛 **Bug** : [GitHub Issues](https://github.com/votre-org/joy-pharma-back/issues)
- 💬 **Question** : [GitHub Discussions](https://github.com/votre-org/joy-pharma-back/discussions)
- 📧 **Email** : support@joypharma.com

## ✅ Checklist de Vérification

Tout fonctionne si :

- [ ] `docker compose ps` montre tous les services "healthy"
- [ ] https://localhost affiche la page d'accueil
- [ ] https://localhost/docs montre la documentation API
- [ ] `make logs` ne montre pas d'erreurs critiques
- [ ] PostgreSQL répond sur localhost:5432
- [ ] Elasticsearch répond sur http://localhost:9200
- [ ] Vous pouvez créer un utilisateur admin
- [ ] Vous pouvez vous connecter à l'API

## 🎉 Félicitations !

Vous êtes maintenant prêt à développer avec **Joy Pharma Backend** !

```
    🎊  Tout est configuré et prêt à l'emploi !  🎊
    
         ┌─────────────────────────────┐
         │   🚀  Happy Coding !  🚀    │
         └─────────────────────────────┘
```

### Ce que vous avez maintenant :

- ✨ Application Symfony 7.2 dockerisée
- ⚡ FrankenPHP avec mode Worker ultra-rapide
- 🔒 HTTPS automatique
- 📦 PostgreSQL + Elasticsearch
- 🐛 XDebug pour le débogage
- 📖 Documentation complète (1500+ lignes)
- 🛠️ Makefile avec 25+ commandes
- 🚀 Script de démarrage automatique
- 🔄 Hot-reload du code
- ✅ Prêt pour la production

### Commencez maintenant :

```bash
# Démarrer
./start.sh

# Créer un admin
make admin-create

# Ouvrir l'API
open https://localhost/docs

# Commencer à coder !
code src/
```

---

## 📚 Index de la Documentation

- 📥 [INSTALLATION.md](INSTALLATION.md) - Installation détaillée
- 🐳 [DOCKER.md](DOCKER.md) - Guide Docker
- 🚀 [docs/quickstart.md](docs/quickstart.md) - Démarrage rapide
- 🐳 [docs/docker.md](docs/docker.md) - Docker avancé
- 🚢 [docs/production.md](docs/production.md) - Déploiement production
- 🤝 [CONTRIBUTING.md](CONTRIBUTING.md) - Guide de contribution
- 📝 [CHANGELOG.md](CHANGELOG.md) - Journal des changements
- 📋 [DOCKER_SETUP_SUMMARY.md](DOCKER_SETUP_SUMMARY.md) - Résumé de la config

---

**Développé avec ❤️ pour Joy Pharma**  
**Basé sur [symfony-docker](https://github.com/dunglas/symfony-docker) par [Kévin Dunglas](https://dunglas.dev)**

🚀 **Bonne chance et bon développement !** 🚀

