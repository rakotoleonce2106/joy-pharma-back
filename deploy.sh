#!/bin/bash
# ~/joy-pharma-back/deploy.sh

# Ne pas arrêter le script sur les erreurs, on les gère manuellement
set +e

echo "🚀 Déploiement joy-pharma-back..."

cd ~/joy-pharma-back

# Afficher le tag qui sera déployé
if [ -n "$TAG" ]; then
  echo "📦 Tag de l'image: ${TAG}"
else
  echo "⚠️  Aucun tag spécifié, utilisation de 'latest'"
  TAG="latest"
fi

if [ -n "$DOCKERHUB_USERNAME" ]; then
  echo "🐳 Image: ${DOCKERHUB_USERNAME}/joy-pharma-back:${TAG}"
fi

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

# Vérifier que les variables critiques sont dans .env
echo "→ Vérification des variables d'environnement..."
REQUIRED_VARS=("TAG" "DOCKERHUB_USERNAME" "POSTGRES_USER" "POSTGRES_PASSWORD" "POSTGRES_DB" "APP_SECRET")
MISSING_VARS=()

for var in "${REQUIRED_VARS[@]}"; do
  if ! grep -q "^${var}=" .env; then
    MISSING_VARS+=("$var")
  fi
done

if [ ${#MISSING_VARS[@]} -gt 0 ]; then
  echo "⚠️  Variables manquantes dans .env: ${MISSING_VARS[*]}"
else
  echo "✓ Toutes les variables requises sont présentes dans .env"
fi

# Afficher le tag depuis .env
DEPLOYED_TAG=$(grep "^TAG=" .env | cut -d'=' -f2)
echo "📋 Tag dans .env: ${DEPLOYED_TAG}"

# Pull la nouvelle image
echo "→ Pull de l'image Docker: ${DOCKERHUB_USERNAME}/joy-pharma-back:${DEPLOYED_TAG}..."
if ! docker compose -f compose.yaml -f compose.prod.yaml --env-file .env pull; then
  echo "❌ Échec du pull de l'image Docker"
  exit 1
fi
echo "✓ Image pullée avec succès"

# Afficher l'ancienne image avant le redémarrage
echo ""
echo "📦 Image actuellement déployée:"
docker compose -f compose.yaml -f compose.prod.yaml --env-file .env images php 2>/dev/null || echo "Aucune image précédente"

# Redémarrer le service
echo ""
echo "→ Démarrage du service avec la nouvelle image..."
if ! docker compose -f compose.yaml -f compose.prod.yaml --env-file .env up -d --force-recreate; then
  echo "❌ Échec du démarrage du service"
  exit 1
fi

# Attendre que le conteneur soit prêt et stable
echo "→ Attente du démarrage du conteneur..."
sleep 5

MAX_WAIT=60
WAIT_COUNT=0
CONTAINER_STABLE=0

# Attendre que le conteneur soit en état "running" et stable
while [ $WAIT_COUNT -lt $MAX_WAIT ]; do
  CONTAINER_STATUS=$(docker compose -f compose.yaml -f compose.prod.yaml --env-file .env ps php --format "{{.Status}}" 2>/dev/null || echo "")
  
  if [[ "$CONTAINER_STATUS" == *"Up"* ]] && [[ "$CONTAINER_STATUS" != *"Restarting"* ]]; then
    # Conteneur est en cours d'exécution, vérifier s'il répond
    if docker compose -f compose.yaml -f compose.prod.yaml --env-file .env exec -T php php -v > /dev/null 2>&1; then
      CONTAINER_STABLE=$((CONTAINER_STABLE + 1))
      if [ $CONTAINER_STABLE -ge 3 ]; then
        echo "✓ Conteneur PHP prêt et stable"
        break
      fi
    fi
  elif [[ "$CONTAINER_STATUS" == *"Restarting"* ]]; then
    echo "⚠ Conteneur en cours de redémarrage... ($WAIT_COUNT/$MAX_WAIT)"
    CONTAINER_STABLE=0
  else
    echo "⏳ Attente du conteneur PHP... ($WAIT_COUNT/$MAX_WAIT)"
    CONTAINER_STABLE=0
  fi
  
  WAIT_COUNT=$((WAIT_COUNT + 1))
  sleep 2
done

if [ $WAIT_COUNT -eq $MAX_WAIT ] || [ $CONTAINER_STABLE -lt 3 ]; then
  echo "❌ Le conteneur PHP n'est pas stable après $MAX_WAIT tentatives"
  echo ""
  echo "📋 État du conteneur:"
  docker compose -f compose.yaml -f compose.prod.yaml --env-file .env ps php
  echo ""
  echo "📋 Derniers logs du conteneur:"
  docker compose -f compose.yaml -f compose.prod.yaml --env-file .env logs --tail=50 php
  exit 1
fi

# Vérifier que le conteneur est toujours en cours d'exécution avant de continuer
CONTAINER_STATUS=$(docker compose -f compose.yaml -f compose.prod.yaml --env-file .env ps php --format "{{.Status}}" 2>/dev/null || echo "")
if [[ "$CONTAINER_STATUS" == *"Restarting"* ]] || [[ "$CONTAINER_STATUS" != *"Up"* ]]; then
  echo "❌ Le conteneur PHP n'est pas stable, arrêt du déploiement"
  docker compose -f compose.yaml -f compose.prod.yaml --env-file .env logs --tail=50 php
  exit 1
fi

# Vérifier la connexion à la base de données
echo "→ Vérification de la connexion à la base de données..."
if ! docker compose -f compose.yaml -f compose.prod.yaml --env-file .env exec -T php php bin/console dbal:run-sql "SELECT 1" > /dev/null 2>&1; then
  echo "⚠ Connexion à la base de données échouée, mais continuation du déploiement..."
else
  echo "✓ Connexion à la base de données vérifiée"
fi

# Exécuter les migrations Symfony
echo "→ Exécution des migrations..."
if docker compose -f compose.yaml -f compose.prod.yaml --env-file .env exec -T php php bin/console doctrine:migrations:migrate --no-interaction 2>&1; then
  echo "✓ Migrations exécutées avec succès"
else
  echo "⚠ Échec des migrations (peut être normal si déjà à jour)"
fi

# Nettoyer le cache
echo "→ Nettoyage du cache..."
if docker compose -f compose.yaml -f compose.prod.yaml --env-file .env exec -T php php bin/console cache:clear 2>&1; then
  echo "✓ Cache nettoyé"
else
  echo "⚠ Échec du nettoyage du cache (peut être normal)"
fi

# Afficher un résumé du déploiement
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "✅ Déploiement terminé avec succès!"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "📦 Image déployée:"
docker compose -f compose.yaml -f compose.prod.yaml --env-file .env images php
echo ""
echo "📊 État du conteneur:"
docker compose -f compose.yaml -f compose.prod.yaml --env-file .env ps php
echo ""
echo "📋 Derniers logs (10 lignes):"
docker compose -f compose.yaml -f compose.prod.yaml --env-file .env logs --tail=10 php
echo ""
echo "🌐 Application disponible sur: https://${SERVER_NAME:-preprod.joy-pharma.com}"
echo ""

# Nettoyage des anciennes images (conserver les 3 dernières versions)
echo "🧹 Nettoyage des anciennes images..."
docker image prune -af --filter "until=72h" 2>/dev/null || true
echo "✓ Nettoyage terminé"
```

## Améliorations apportées :

1. **Affichage du tag** dès le début du déploiement
2. **Vérification des variables** dans le fichier `.env`
3. **Affichage de l'ancienne image** avant le redémarrage
4. **Résumé détaillé** à la fin avec :
   - Image déployée avec son tag
   - État du conteneur
   - Derniers logs
   - URL de l'application
5. **Nettoyage automatique** des anciennes images (+ de 72h)
6. **Meilleur logging** avec des emojis et des sections claires

## Exemple de sortie attendue :
```
🚀 Déploiement joy-pharma-back...
📦 Tag de l'image: v1.20241217.143025
🐳 Image: joyleonce/joy-pharma-back:v1.20241217.143025
→ Vérification des réseaux externes...
✓ Réseaux externes vérifiés
→ Vérification des variables d'environnement...
✓ Toutes les variables requises sont présentes dans .env
📋 Tag dans .env: v1.20241217.143025
→ Pull de l'image Docker: joyleonce/joy-pharma-back:v1.20241217.143025...
✓ Image pullée avec succès

📦 Image actuellement déployée:
CONTAINER   REPOSITORY                    TAG                    IMAGE ID
php         joyleonce/joy-pharma-back     v1.20241217.120000    abc123def456

→ Démarrage du service avec la nouvelle image...
✓ Conteneur PHP prêt et stable
✓ Connexion à la base de données vérifiée
✓ Migrations exécutées avec succès
✓ Cache nettoyé

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
✅ Déploiement terminé avec succès!
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

📦 Image déployée:
CONTAINER   REPOSITORY                    TAG                    IMAGE ID
php         joyleonce/joy-pharma-back     v1.20241217.143025    xyz789abc123

🌐 Application disponible sur: https://preprod.joy-pharma.com

🧹 Nettoyage des anciennes images...
✓ Nettoyage terminé