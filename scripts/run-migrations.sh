#!/bin/bash
# Script pour exécuter les migrations sur le serveur de production
# Usage: ./scripts/run-migrations.sh

set -e

echo "============================================"
echo "Exécution des migrations de base de données"
echo "============================================"

# Vérifier que nous sommes dans le bon répertoire
if [ ! -f "compose.yaml" ] || [ ! -f "compose.prod.yaml" ]; then
    echo "✗ Erreur: Les fichiers compose.yaml et compose.prod.yaml doivent être présents"
    echo "→ Assurez-vous d'être dans le répertoire joypharma/"
    exit 1
fi

# Vérifier que le fichier .env existe
if [ ! -f ".env" ]; then
    echo "✗ Erreur: Le fichier .env n'existe pas"
    echo "→ Le fichier .env doit être généré depuis Infisical"
    exit 1
fi

# Vérifier que le conteneur PHP est en cours d'exécution
PHP_CONTAINER=$(docker compose -f compose.yaml -f compose.prod.yaml --env-file .env ps -q php 2>/dev/null || echo "")
if [ -z "$PHP_CONTAINER" ]; then
    echo "✗ Erreur: Le conteneur PHP n'est pas en cours d'exécution"
    echo "→ Démarrez d'abord les conteneurs avec: docker compose -f compose.yaml -f compose.prod.yaml --env-file .env up -d"
    exit 1
fi

echo "✓ Conteneur PHP trouvé: $PHP_CONTAINER"

# Vérifier la connexion à la base de données
echo "→ Vérification de la connexion à la base de données..."
if docker compose -f compose.yaml -f compose.prod.yaml --env-file .env exec -T php bin/console dbal:run-sql "SELECT 1" > /dev/null 2>&1; then
    echo "✓ Connexion à la base de données réussie"
else
    echo "✗ Erreur: Impossible de se connecter à la base de données"
    echo "→ Vérifiez que la base de données est en cours d'exécution et que DATABASE_URL est correcte"
    exit 1
fi

# Afficher le statut actuel des migrations
echo ""
echo "→ Statut actuel des migrations:"
docker compose -f compose.yaml -f compose.prod.yaml --env-file .env exec -T php bin/console doctrine:migrations:status || true

# Lister les migrations en attente
echo ""
echo "→ Migrations en attente:"
docker compose -f compose.yaml -f compose.prod.yaml --env-file .env exec -T php bin/console doctrine:migrations:list || true

# Exécuter les migrations
echo ""
echo "→ Exécution des migrations..."
if docker compose -f compose.yaml -f compose.prod.yaml --env-file .env exec -T php bin/console doctrine:migrations:migrate --no-interaction --all; then
    echo ""
    echo "============================================"
    echo "✓ Migrations exécutées avec succès!"
    echo "============================================"
    
    # Afficher le nouveau statut
    echo ""
    echo "→ Nouveau statut des migrations:"
    docker compose -f compose.yaml -f compose.prod.yaml --env-file .env exec -T php bin/console doctrine:migrations:status || true
else
    echo ""
    echo "============================================"
    echo "✗ Erreur lors de l'exécution des migrations"
    echo "============================================"
    echo ""
    echo "→ Vérifiez les logs pour plus de détails:"
    echo "  docker compose -f compose.yaml -f compose.prod.yaml --env-file .env logs php"
    exit 1
fi

