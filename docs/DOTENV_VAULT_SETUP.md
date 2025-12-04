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

1. Obtenez votre `DOTENV_KEY` pour l'environnement de production:
```bash
dotenv-vault keys production
```

2. Ajoutez `DOTENV_KEY` comme secret dans GitHub:
   - Allez dans Settings > Secrets and variables > Actions
   - Ajoutez un nouveau secret nommé `DOTENV_KEY`
   - Collez la valeur obtenue (commence par `dotenv://`)

3. Le workflow de déploiement (`.github/workflows/deploy.yml`) est déjà configuré pour passer `DOTENV_KEY` comme build argument au Docker build.

4. **GitHub Auto-build add-on** (optionnel mais recommandé):
   - Activez l'add-on "GitHub Auto-build" depuis votre compte dotenv.org
   - Autorisez GitHub et sélectionnez ce dépôt
   - Cela créera automatiquement une PR qui ajoute `.env.vault` à votre repo
   - Chaque fois que vous modifiez des variables via l'UI dotenv, une PR sera automatiquement créée pour mettre à jour `.env.vault`

**Note importante**: `DOTENV_KEY` est passé comme build argument dans le workflow GitHub Actions. Pour le runtime du conteneur Docker, assurez-vous également de définir `DOTENV_KEY` dans votre configuration de déploiement (docker-compose, Kubernetes, etc.) si vous avez besoin des variables d'environnement au runtime.

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

