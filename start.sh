#!/bin/bash

# Script de démarrage rapide pour Joy Pharma Backend
# Usage: ./start.sh [dev|prod]

set -e

ENVIRONMENT=${1:-dev}
BLUE='\033[0;34m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${BLUE}"
echo "╔═══════════════════════════════════════╗"
echo "║   Joy Pharma Backend - Docker Setup  ║"
echo "╚═══════════════════════════════════════╝"
echo -e "${NC}"

# Vérifier que Docker est installé
if ! command -v docker &> /dev/null; then
    echo -e "${RED}❌ Docker n'est pas installé${NC}"
    echo "Installez Docker depuis: https://docs.docker.com/get-docker/"
    exit 1
fi

# Vérifier que Docker Compose est installé
if ! docker compose version &> /dev/null; then
    echo -e "${RED}❌ Docker Compose n'est pas installé${NC}"
    echo "Installez Docker Compose V2"
    exit 1
fi

echo -e "${GREEN}✅ Docker et Docker Compose sont installés${NC}"

# Vérifier le fichier .env
if [ ! -f .env ] && [ ! -f .env.example ]; then
    echo -e "${RED}❌ Fichier .env non trouvé${NC}"
    exit 1
fi

if [ ! -f .env ]; then
    echo -e "${YELLOW}⚠️  Copie de .env.example vers .env${NC}"
    cp .env.example .env
    echo -e "${GREEN}✅ Fichier .env créé${NC}"
    echo -e "${YELLOW}⚠️  N'oubliez pas de modifier les valeurs dans .env${NC}"
fi

# Fonction de démarrage en développement
start_dev() {
    echo -e "${BLUE}🚀 Démarrage en mode développement...${NC}"
    
    # Build des images
    echo -e "${BLUE}📦 Construction des images Docker...${NC}"
    docker compose build --pull
    
    # Démarrer les conteneurs
    echo -e "${BLUE}🐳 Démarrage des conteneurs...${NC}"
    docker compose up -d
    
    # Attendre que les services soient prêts
    echo -e "${BLUE}⏳ Attente du démarrage des services...${NC}"
    sleep 10
    
    # Créer la base de données si elle n'existe pas
    echo -e "${BLUE}🗄️  Initialisation de la base de données...${NC}"
    docker compose exec -T php bin/console doctrine:database:create --if-not-exists || true
    
    # Exécuter les migrations
    echo -e "${BLUE}📊 Exécution des migrations...${NC}"
    docker compose exec -T php bin/console doctrine:migrations:migrate --no-interaction
    
    # Générer les clés JWT si elles n'existent pas
    if [ ! -f config/jwt/private.pem ]; then
        echo -e "${BLUE}🔑 Génération des clés JWT...${NC}"
        docker compose exec -T php bin/console lexik:jwt:generate-keypair --overwrite || true
    fi
    
    echo -e "${GREEN}"
    echo "╔═══════════════════════════════════════════════════════╗"
    echo "║          ✅ Application démarrée avec succès !         ║"
    echo "╚═══════════════════════════════════════════════════════╝"
    echo -e "${NC}"
    echo -e "${GREEN}🌐 Application:${NC}      https://localhost"
    echo -e "${GREEN}📖 Documentation API:${NC} https://localhost/docs"
    echo -e "${GREEN}🔍 Elasticsearch:${NC}     http://localhost:9200"
    echo -e "${GREEN}🗄️  PostgreSQL:${NC}       localhost:5432"
    echo ""
    echo -e "${YELLOW}⚠️  Acceptez le certificat SSL auto-signé dans votre navigateur${NC}"
    echo ""
    echo -e "${BLUE}📝 Commandes utiles:${NC}"
    echo "  - Voir les logs:        docker compose logs -f"
    echo "  - Arrêter:              docker compose down"
    echo "  - Shell PHP:            docker compose exec php sh"
    echo "  - Console Symfony:      docker compose exec php bin/console"
    echo "  - Créer un admin:       docker compose exec php bin/console app:create-admin-user"
    echo ""
}

# Fonction de démarrage en production
start_prod() {
    echo -e "${BLUE}🚀 Démarrage en mode production...${NC}"
    
    # Vérifier les variables critiques
    if ! grep -q "APP_SECRET=" .env || grep -q "APP_SECRET=!ChangeMe!" .env; then
        echo -e "${RED}❌ APP_SECRET doit être défini dans .env${NC}"
        exit 1
    fi
    
    # Build des images de production
    echo -e "${BLUE}📦 Construction des images Docker (production)...${NC}"
    docker compose -f compose.yaml -f compose.prod.yaml build --no-cache --pull
    
    # Démarrer les conteneurs
    echo -e "${BLUE}🐳 Démarrage des conteneurs...${NC}"
    docker compose -f compose.yaml -f compose.prod.yaml up -d
    
    # Attendre que les services soient prêts
    echo -e "${BLUE}⏳ Attente du démarrage des services...${NC}"
    sleep 15
    
    # Créer la base de données
    echo -e "${BLUE}🗄️  Initialisation de la base de données...${NC}"
    docker compose -f compose.yaml -f compose.prod.yaml exec -T php bin/console doctrine:database:create --if-not-exists || true
    
    # Exécuter les migrations
    echo -e "${BLUE}📊 Exécution des migrations...${NC}"
    docker compose -f compose.yaml -f compose.prod.yaml exec -T php bin/console doctrine:migrations:migrate --no-interaction
    
    # Générer les clés JWT
    echo -e "${BLUE}🔑 Génération des clés JWT...${NC}"
    docker compose -f compose.yaml -f compose.prod.yaml exec -T php bin/console lexik:jwt:generate-keypair --overwrite || true
    
    # Vider et réchauffer le cache
    echo -e "${BLUE}🔥 Optimisation du cache...${NC}"
    docker compose -f compose.yaml -f compose.prod.yaml exec -T php bin/console cache:clear --env=prod
    docker compose -f compose.yaml -f compose.prod.yaml exec -T php bin/console cache:warmup --env=prod
    
    echo -e "${GREEN}"
    echo "╔═══════════════════════════════════════════════════════╗"
    echo "║    ✅ Application démarrée en production avec succès ! ║"
    echo "╚═══════════════════════════════════════════════════════╝"
    echo -e "${NC}"
    echo -e "${GREEN}🌐 Application:${NC} Accessible selon SERVER_NAME dans .env"
    echo ""
}

# Menu de sélection si aucun argument
if [ -z "$1" ]; then
    echo -e "${YELLOW}Choisissez l'environnement:${NC}"
    echo "1) Développement (dev)"
    echo "2) Production (prod)"
    read -p "Votre choix [1-2]: " choice
    
    case $choice in
        1)
            ENVIRONMENT="dev"
            ;;
        2)
            ENVIRONMENT="prod"
            ;;
        *)
            echo -e "${RED}❌ Choix invalide${NC}"
            exit 1
            ;;
    esac
fi

# Démarrage selon l'environnement
case $ENVIRONMENT in
    dev|development)
        start_dev
        ;;
    prod|production)
        start_prod
        ;;
    *)
        echo -e "${RED}❌ Environnement invalide. Utilisez 'dev' ou 'prod'${NC}"
        exit 1
        ;;
esac

echo -e "${GREEN}🎉 Terminé !${NC}"

