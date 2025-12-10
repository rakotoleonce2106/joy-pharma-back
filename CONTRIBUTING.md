# 🤝 Guide de Contribution

Merci de votre intérêt pour contribuer à Joy Pharma Backend ! Ce guide vous aidera à démarrer.

## 📋 Table des Matières

- [Code de Conduite](#code-de-conduite)
- [Comment Contribuer](#comment-contribuer)
- [Configuration de l'Environnement](#configuration-de-lenvironnement)
- [Standards de Code](#standards-de-code)
- [Processus de Pull Request](#processus-de-pull-request)
- [Signaler des Bugs](#signaler-des-bugs)
- [Proposer des Fonctionnalités](#proposer-des-fonctionnalités)

## Code de Conduite

En participant à ce projet, vous acceptez de respecter notre code de conduite :
- Soyez respectueux et inclusif
- Acceptez les critiques constructives
- Concentrez-vous sur ce qui est le mieux pour la communauté
- Faites preuve d'empathie envers les autres membres

## Comment Contribuer

### Types de Contributions

Nous acceptons plusieurs types de contributions :

- 🐛 **Bug fixes** : Corrections de bugs
- ✨ **Features** : Nouvelles fonctionnalités
- 📝 **Documentation** : Améliorations de la documentation
- 🎨 **Style** : Améliorations du code sans changement de fonctionnalité
- ♻️ **Refactoring** : Restructuration du code
- ⚡ **Performance** : Optimisations
- ✅ **Tests** : Ajout ou correction de tests

## Configuration de l'Environnement

### Prérequis

- Docker Desktop 20.10+
- Git
- Un éditeur de code (VSCode, PHPStorm, etc.)

### Installation

1. **Forker le repository**

2. **Cloner votre fork**
```bash
git clone https://github.com/VOTRE_USERNAME/joy-pharma-back.git
cd joy-pharma-back
```

3. **Ajouter le repository upstream**
```bash
git remote add upstream https://github.com/votre-org/joy-pharma-back.git
```

4. **Démarrer l'environnement Docker**
```bash
# Méthode 1 : Script automatique
./start.sh

# Méthode 2 : Makefile
make start

# Méthode 3 : Docker Compose
docker compose up -d
docker compose exec php bin/console doctrine:database:create
docker compose exec php bin/console doctrine:migrations:migrate --no-interaction
```

5. **Vérifier l'installation**
```bash
# Ouvrir https://localhost dans votre navigateur
# L'API doit être accessible
```

## Standards de Code

### PHP

Nous suivons les standards [PSR-12](https://www.php-fig.org/psr/psr-12/) pour PHP.

#### Conventions de Nommage

```php
// Classes : PascalCase
class UserController {}

// Méthodes et variables : camelCase
public function getUserById($userId) {}

// Constants : SCREAMING_SNAKE_CASE
const MAX_RETRY_COUNT = 3;

// Namespace : correspond à la structure de dossiers
namespace App\Controller\Api;
```

#### Code Style

```php
<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class UserService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function createUser(string $email, string $password): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setPassword($password);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }
}
```

### Documentation

```php
/**
 * Crée un nouvel utilisateur.
 *
 * @param string $email    L'email de l'utilisateur
 * @param string $password Le mot de passe (sera hashé)
 *
 * @return User L'utilisateur créé
 *
 * @throws \InvalidArgumentException Si l'email est invalide
 */
public function createUser(string $email, string $password): User
{
    // ...
}
```

### Commits

Nous utilisons la convention [Conventional Commits](https://www.conventionalcommits.org/).

#### Format

```
<type>(<scope>): <description>

[optional body]

[optional footer]
```

#### Types

- `feat`: Nouvelle fonctionnalité
- `fix`: Correction de bug
- `docs`: Documentation uniquement
- `style`: Formatage, point-virgule manquant, etc.
- `refactor`: Refactoring du code
- `perf`: Amélioration de performance
- `test`: Ajout de tests
- `chore`: Maintenance, configuration
- `ci`: Configuration CI/CD

#### Exemples

```bash
# Nouvelle fonctionnalité
git commit -m "feat(auth): add password reset functionality"

# Correction de bug
git commit -m "fix(order): resolve duplicate order creation"

# Documentation
git commit -m "docs(readme): update installation instructions"

# Refactoring
git commit -m "refactor(service): simplify user service methods"

# Performance
git commit -m "perf(database): add index on email column"
```

### Tests

Tous les changements de code doivent inclure des tests.

```php
// tests/Service/UserServiceTest.php
namespace App\Tests\Service;

use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class UserServiceTest extends KernelTestCase
{
    private UserService $userService;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->userService = static::getContainer()->get(UserService::class);
    }

    public function testCreateUser(): void
    {
        $user = $this->userService->createUser(
            'test@example.com',
            'password123'
        );

        $this->assertNotNull($user->getId());
        $this->assertEquals('test@example.com', $user->getEmail());
    }
}
```

Exécuter les tests :
```bash
make tests
# ou
docker compose exec php bin/phpunit
```

## Processus de Pull Request

### 1. Créer une Branche

```bash
# Se mettre à jour avec upstream
git checkout main
git pull upstream main

# Créer une branche de fonctionnalité
git checkout -b feat/ma-nouvelle-fonctionnalite

# ou pour un bug fix
git checkout -b fix/correction-du-bug
```

### 2. Développer

- Écrivez du code propre et testé
- Ajoutez des tests pour vos changements
- Mettez à jour la documentation si nécessaire
- Suivez les standards de code

### 3. Commiter

```bash
# Ajouter les fichiers
git add .

# Commiter avec un message conventionnel
git commit -m "feat(orders): add order cancellation feature"
```

### 4. Pousser

```bash
# Pousser vers votre fork
git push origin feat/ma-nouvelle-fonctionnalite
```

### 5. Créer la Pull Request

1. Allez sur GitHub
2. Cliquez sur "New Pull Request"
3. Sélectionnez votre branche
4. Remplissez le template de PR :

```markdown
## Description
Brève description des changements

## Type de changement
- [ ] Bug fix
- [ ] Nouvelle fonctionnalité
- [ ] Breaking change
- [ ] Documentation

## Checklist
- [ ] Tests ajoutés/mis à jour
- [ ] Documentation mise à jour
- [ ] Code review effectué
- [ ] Tests passent localement
- [ ] Commits suivent les conventions

## Captures d'écran (si applicable)

## Notes additionnelles
```

### 6. Review

- Attendez la review d'un mainteneur
- Répondez aux commentaires
- Effectuez les changements demandés
- Poussez les modifications

### 7. Merge

Une fois approuvée, votre PR sera mergée par un mainteneur.

## Signaler des Bugs

### Avant de Signaler

- Vérifiez que le bug n'a pas déjà été signalé
- Collectez des informations sur le bug
- Reproduisez le bug de manière constante

### Comment Signaler

Créez une issue avec le template suivant :

```markdown
## Description du Bug
Description claire et concise du bug

## Pour Reproduire
Étapes pour reproduire le comportement :
1. Aller à '...'
2. Cliquer sur '...'
3. Scroller jusqu'à '...'
4. Voir l'erreur

## Comportement Attendu
Description du comportement attendu

## Captures d'écran
Si applicable, ajoutez des captures d'écran

## Environnement
- OS: [e.g. macOS 12.0]
- Docker: [e.g. 20.10.12]
- Navigateur: [e.g. Chrome 98]

## Logs
```
Collez les logs pertinents ici
```

## Informations Additionnelles
Tout contexte additionnel
```

## Proposer des Fonctionnalités

### Avant de Proposer

- Vérifiez que la fonctionnalité n'est pas déjà proposée
- Réfléchissez à la pertinence de la fonctionnalité
- Préparez des exemples d'utilisation

### Comment Proposer

Créez une issue avec le template suivant :

```markdown
## Problème à Résoudre
Description du problème que cette fonctionnalité résoudrait

## Solution Proposée
Description claire de comment vous voulez que cela fonctionne

## Alternatives Considérées
Description des solutions alternatives envisagées

## Exemples d'Utilisation
```php
// Exemple de code montrant l'utilisation
$service->nouvelleMethode();
```

## Informations Additionnelles
Contexte additionnel, captures d'écran, etc.
```

## Structure du Projet

```
src/
├── ApiResource/      # Définitions API Platform
├── Controller/       # Contrôleurs
│   └── Api/         # Contrôleurs API
├── Dto/             # Data Transfer Objects
├── Entity/          # Entités Doctrine
├── EventSubscriber/ # Event Subscribers Symfony
├── Exception/       # Exceptions personnalisées
├── Repository/      # Repositories Doctrine
├── Security/        # Services de sécurité
├── Serializer/      # Normalizers/Denormalizers
├── Service/         # Services métier
└── State/           # Providers/Processors API Platform
```

## Ressources Utiles

### Documentation
- [Symfony Documentation](https://symfony.com/doc/current/index.html)
- [API Platform Documentation](https://api-platform.com/docs/)
- [Doctrine Documentation](https://www.doctrine-project.org/projects/doctrine-orm/en/current/index.html)

### Outils
- [PHPStan](https://phpstan.org/) - Analyse statique
- [PHP CS Fixer](https://github.com/FriendsOfPHP/PHP-CS-Fixer) - Formatage de code
- [PHPUnit](https://phpunit.de/) - Tests unitaires

## Questions ?

Si vous avez des questions :
- Ouvrez une issue avec le tag `question`
- Contactez les mainteneurs
- Consultez la documentation dans `docs/`

## Remerciements

Merci de contribuer à Joy Pharma ! Chaque contribution, petite ou grande, est appréciée. 🙏

---

**Happy Coding! 🚀**

