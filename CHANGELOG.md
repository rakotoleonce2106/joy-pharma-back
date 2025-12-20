# Changelog

Tous les changements notables de ce projet seront documentés dans ce fichier.

Le format est basé sur [Keep a Changelog](https://keepachangelog.com/fr/1.0.0/),
et ce projet adhère au [Semantic Versioning](https://semver.org/lang/fr/).

## [Non publié]

### Ajouté
- 🐳 Dockerisation complète avec FrankenPHP
- 📦 Configuration Docker Compose pour dev et prod
- 🚀 Mode Worker FrankenPHP pour performances optimales
- 🔒 HTTPS automatique avec Caddy
- 🔍 Support Elasticsearch intégré
- 📖 Documentation complète Docker
- 🛠️ Makefile pour commandes simplifiées
- 🎯 Script de démarrage rapide (`start.sh`)
- 🏥 Health checks pour tous les services
- 🔐 Configuration JWT sécurisée
- 📊 Support PostgreSQL 16
- ⚡ HTTP/2 et HTTP/3 natifs
- 🐛 Configuration XDebug pour développement
- 📝 Guides de démarrage rapide et déploiement
- 🤖 CI/CD avec GitHub Actions

### Modifié
- ⬆️ Mise à jour vers Symfony 7.2
- ⬆️ Mise à jour vers API Platform 4.1
- ⬆️ Mise à jour vers PHP 8.3
- 📝 README complètement revu et amélioré

### Configuration Docker
- `Dockerfile` : Build multi-stage optimisé
- `compose.yaml` : Configuration base (PHP, PostgreSQL, Elasticsearch)
- `compose.override.yaml` : Surcharges développement
- `compose.prod.yaml` : Surcharges production
- `frankenphp/` : Configuration Caddy et FrankenPHP
- `.dockerignore` : Exclusions de build
- `.editorconfig` : Configuration éditeur
- `.gitattributes` : Attributs Git

### Documentation
- `DOCKER.md` : Guide Docker principal
- `docs/docker.md` : Documentation Docker détaillée
- `docs/quickstart.md` : Guide de démarrage rapide
- `docs/production.md` : Guide de déploiement production
- `Makefile` : Commandes simplifiées
- `start.sh` : Script de démarrage interactif

## [1.0.0] - YYYY-MM-DD

### Ajouté
- Version initiale de l'API Joy Pharma
- Authentication JWT
- Gestion des utilisateurs
- Gestion des produits
- Gestion des commandes
- Gestion des pharmacies
- Système de paiement Mvola
- Système de livraison
- Notifications en temps réel

[Non publié]: https://github.com/votre-org/joy-pharma-back/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/votre-org/joy-pharma-back/releases/tag/v1.0.0

