#!/bin/bash
# ~/joy-pharma-back/deploy.sh

set -e

echo "🚀 Déploiement joy-pharma-back..."

cd ~/joy-pharma-back

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

# Supprimer le réseau default s'il existe (pour éviter les conflits)
if docker network ls | grep -q "joy-pharma-back_default"; then
  echo "→ Suppression de l'ancien réseau default..."
  docker network rm joy-pharma-back_default 2>/dev/null || true
fi

# Vérifier que le fichier .env existe
if [ ! -f ".env" ]; then
  echo "❌ Le fichier .env n'existe pas"
  exit 1
fi

# Pull la nouvelle image
echo "→ Pull de l'image Docker..."
docker compose -f compose.yaml -f compose.prod.yaml --env-file .env pull

# Redémarrer le service
echo "→ Démarrage du service..."
docker compose -f compose.yaml -f compose.prod.yaml --env-file .env up -d --force-recreate

# Attendre que le conteneur soit prêt
echo "→ Attente du démarrage du conteneur..."
sleep 5

MAX_WAIT=30
WAIT_COUNT=0
until docker compose -f compose.yaml -f compose.prod.yaml --env-file .env exec -T php php -v > /dev/null 2>&1 || [ $WAIT_COUNT -eq $MAX_WAIT ]; do
  WAIT_COUNT=$((WAIT_COUNT + 1))
  echo "⏳ Attente du conteneur PHP... ($WAIT_COUNT/$MAX_WAIT)"
  sleep 2
done

if [ $WAIT_COUNT -eq $MAX_WAIT ]; then
  echo "❌ Le conteneur PHP n'est pas prêt après $MAX_WAIT tentatives"
  docker compose -f compose.yaml -f compose.prod.yaml --env-file .env logs php
  exit 1
fi
echo "✓ Conteneur PHP prêt"

# Vérifier la connexion à la base de données
echo "→ Vérification de la connexion à la base de données..."
if ! docker compose -f compose.yaml -f compose.prod.yaml --env-file .env exec -T php php bin/console dbal:run-sql "SELECT 1" > /dev/null 2>&1; then
  echo "⚠ Connexion à la base de données échouée, mais continuation du déploiement..."
else
  echo "✓ Connexion à la base de données vérifiée"
fi

# Exécuter les migrations Symfony
echo "→ Exécution des migrations..."
if docker compose -f compose.yaml -f compose.prod.yaml --env-file .env exec -T php php bin/console doctrine:migrations:migrate --no-interaction; then
  echo "✓ Migrations exécutées avec succès"
else
  echo "⚠ Échec des migrations (peut être normal si déjà à jour)"
fi

# Nettoyer le cache
echo "→ Nettoyage du cache..."
docker compose -f compose.yaml -f compose.prod.yaml --env-file .env exec -T php php bin/console cache:clear
echo "✓ Cache nettoyé"

echo "✅ Déploiement terminé!"

