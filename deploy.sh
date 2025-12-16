#!/bin/bash
# /home/ubuntu/joy-pharma-back/deploy.sh

set -e

echo "🚀 Déploiement joy-pharma-back..."

cd /home/ubuntu/joy-pharma-back

# Pull la nouvelle image
docker compose pull

# Redémarrer le service
docker compose up -d --force-recreate

# Exécuter les migrations Symfony (optionnel)
docker compose exec -T php php bin/console doctrine:migrations:migrate --no-interaction

# Nettoyer le cache
docker compose exec -T php php bin/console cache:clear

echo "✅ Déploiement terminé!"

