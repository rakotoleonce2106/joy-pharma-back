# Configuration Dotenv Vault

Ce projet utilise [dotenv-vault](https://dotenv.org) pour gérer de manière sécurisée les variables d'environnement chiffrées.

## Installation

1. Installez le CLI dotenv-vault globalement (requis pour générer le fichier .env.vault):
```bash
npm install -g dotenv-vault
```

2. Le package PHP `dotenv-php/dotenv-vault` est déjà ajouté au `composer.json`. Si ce package n'est pas encore disponible sur Packagist, le script `load-dotenv-vault.php` fonctionnera toujours en mode dégradé (utilisera les fichiers .env standards).

3. Installez les dépendances:
```bash
composer install
```

## Configuration initiale

1. Créez votre fichier `.env` à la racine du projet avec vos variables d'environnement.

2. Poussez vos variables vers dotenv.org:
```bash
dotenv-vault push
```

3. Configurez vos environnements (development, ci, production):
```bash
dotenv-vault open development
dotenv-vault open ci
dotenv-vault open production
```

4. Générez le fichier `.env.vault` chiffré:
```bash
dotenv-vault build
```

## Configuration GitHub Actions

1. Obtenez votre `DOTENV_KEY` pour l'environnement CI:
```bash
dotenv-vault keys ci
```

2. Ajoutez `DOTENV_KEY` comme secret dans GitHub:
   - Allez dans Settings > Secrets and variables > Actions
   - Ajoutez un nouveau secret nommé `DOTENV_KEY`
   - Collez la valeur obtenue (commence par `dotenv://`)

3. Le workflow de déploiement utilisera automatiquement cette clé pour déchiffrer les variables d'environnement.

**Note importante**: `DOTENV_KEY` doit être défini comme variable d'environnement au **runtime** du conteneur Docker (pas au moment du build). Assurez-vous de définir `DOTENV_KEY` dans votre configuration de déploiement (docker-compose, Kubernetes, etc.).

## Utilisation locale

En développement local, si `DOTENV_KEY` n'est pas défini, le système utilisera automatiquement les fichiers `.env` standards de Symfony.

## Structure du fichier .env.vault

Le fichier `.env.vault` contient des versions chiffrées de vos variables pour chaque environnement:

```
DOTENV_VAULT_DEVELOPMENT="..."
DOTENV_VAULT_CI="..."
DOTENV_VAULT_PRODUCTION="..."
```

Ce fichier peut être commité en toute sécurité dans votre dépôt Git car il est chiffré.

## Documentation

Pour plus d'informations, consultez:
- [Documentation dotenv.org](https://dotenv.org)
- [Documentation PHP](https://dotenv.org/docs/languages/php)
- [Documentation GitHub Actions](https://dotenv.org/docs/ci-cds/github-actions)

