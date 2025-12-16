#!/bin/bash
# /home/ubuntu/joy-pharma-back/deploy.sh

set -e

echo "🚀 Déploiement joy-pharma-back..."

cd /home/ubuntu/joy-pharma-back

# Vérifier que les réseaux externes existent
echo "→ Vérification des réseaux externes..."
if ! docker network ls | grep -q "traefik_network"; then
  echo "❌ Le réseau traefik_network n'existe pas. Veuillez le créer d'abord."
  exit 1
fi

if ! docker network ls | grep -q "database_network"; then
  echo "❌ Le réseau database_network n'existe pas. Veuillez le créer d'abord."
  exit 1
fi
echo "✓ Réseaux externes vérifiés"

# Pull la nouvelle image
echo "→ Pull de l'image Docker..."
docker compose pull

# Redémarrer le service
echo "→ Démarrage du service..."
docker compose up -d --force-recreate

# Attendre que le conteneur soit prêt
echo "→ Attente du démarrage du conteneur..."
sleep 5

MAX_WAIT=30
WAIT_COUNT=0
until docker compose exec -T php php -v > /dev/null 2>&1 || [ $WAIT_COUNT -eq $MAX_WAIT ]; do
  WAIT_COUNT=$((WAIT_COUNT + 1))
  echo "⏳ Attente du conteneur PHP... ($WAIT_COUNT/$MAX_WAIT)"
  sleep 2
done

if [ $WAIT_COUNT -eq $MAX_WAIT ]; then
  echo "❌ Le conteneur PHP n'est pas prêt après $MAX_WAIT tentatives"
  docker compose logs php
  exit 1
fi
echo "✓ Conteneur PHP prêt"

# Vérifier la connexion à la base de données
echo "→ Vérification de la connexion à la base de données..."
if ! docker compose exec -T php php bin/console dbal:run-sql "SELECT 1" > /dev/null 2>&1; then
  echo "⚠ Connexion à la base de données échouée, mais continuation du déploiement..."
else
  echo "✓ Connexion à la base de données vérifiée"
fi

# Exécuter les migrations Symfony
echo "→ Exécution des migrations..."
if docker compose exec -T php php bin/console doctrine:migrations:migrate --no-interaction; then
  echo "✓ Migrations exécutées avec succès"
else
  echo "⚠ Échec des migrations (peut être normal si déjà à jour)"
fi

# Nettoyer le cache
echo "→ Nettoyage du cache..."
docker compose exec -T php php bin/console cache:clear
echo "✓ Cache nettoyé"

echo "✅ Déploiement terminé!"

