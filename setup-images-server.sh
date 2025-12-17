#!/bin/bash
# Script pour configurer les images sur le serveur
# Usage: ./setup-images-server.sh user@your-server

set -e  # Arrêter en cas d'erreur

# Couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

if [ -z "$1" ]; then
    echo -e "${RED}❌ Erreur: Vous devez spécifier l'adresse du serveur${NC}"
    echo "Usage: ./setup-images-server.sh user@your-server"
    exit 1
fi

SERVER=$1

echo -e "${GREEN}🚀 Configuration des images sur le serveur${NC}"
echo "Serveur: $SERVER"
echo ""

# Étape 1: Créer la structure sur le serveur
echo -e "${YELLOW}📁 Étape 1: Création de la structure sur le serveur...${NC}"
ssh $SERVER bash << 'ENDSSH'
    # Vérifier si on a les droits sudo
    if ! sudo -n true 2>/dev/null; then
        echo "❌ Erreur: Vous devez avoir les droits sudo sur le serveur"
        exit 1
    fi
    
    # Créer les dossiers
    echo "Création de /joy-pharma-data/..."
    sudo mkdir -p /joy-pharma-data/images/products
    sudo mkdir -p /joy-pharma-data/images/profile
    sudo mkdir -p /joy-pharma-data/media
    sudo mkdir -p /joy-pharma-data/uploads
    
    # Permissions
    echo "Configuration des permissions..."
    sudo chown -R 82:82 /joy-pharma-data/
    sudo chmod -R 755 /joy-pharma-data/
    
    # Vérification
    echo ""
    echo "✅ Structure créée :"
    ls -la /joy-pharma-data/
    echo ""
    echo "Permissions :"
    ls -ld /joy-pharma-data/
ENDSSH

if [ $? -ne 0 ]; then
    echo -e "${RED}❌ Erreur lors de la création de la structure${NC}"
    exit 1
fi

echo -e "${GREEN}✅ Structure créée avec succès${NC}"
echo ""

# Étape 2: Créer l'archive des images
echo -e "${YELLOW}📦 Étape 2: Création de l'archive des images...${NC}"
if [ ! -d "public/images" ]; then
    echo -e "${RED}❌ Erreur: Le dossier public/images n'existe pas${NC}"
    exit 1
fi

tar -czf /tmp/images-joy-pharma.tar.gz -C public images/
IMAGE_SIZE=$(du -h /tmp/images-joy-pharma.tar.gz | cut -f1)
echo -e "${GREEN}✅ Archive créée: /tmp/images-joy-pharma.tar.gz ($IMAGE_SIZE)${NC}"
echo ""

# Étape 3: Upload sur le serveur
echo -e "${YELLOW}📤 Étape 3: Upload vers le serveur (peut prendre quelques minutes)...${NC}"
scp /tmp/images-joy-pharma.tar.gz $SERVER:/tmp/
echo -e "${GREEN}✅ Upload terminé${NC}"
echo ""

# Étape 4: Extraction et mise en place
echo -e "${YELLOW}📥 Étape 4: Extraction et mise en place sur le serveur...${NC}"
ssh $SERVER bash << 'ENDSSH'
    cd /tmp
    
    echo "Extraction de l'archive..."
    tar -xzf images-joy-pharma.tar.gz
    
    echo "Copie vers /joy-pharma-data/images/..."
    sudo rsync -av --progress public/images/ /joy-pharma-data/images/
    
    echo "Ajustement des permissions..."
    sudo chown -R 82:82 /joy-pharma-data/images/
    sudo chmod -R 755 /joy-pharma-data/images/
    
    echo "Nettoyage..."
    rm -rf public/ images-joy-pharma.tar.gz
    
    echo ""
    echo "✅ Fichiers copiés :"
    FILE_COUNT=$(sudo find /joy-pharma-data/images -type f | wc -l)
    echo "   - Nombre de fichiers: $FILE_COUNT"
    
    echo ""
    echo "Exemples de fichiers :"
    sudo ls -lh /joy-pharma-data/images/products/ | head -5
ENDSSH

if [ $? -ne 0 ]; then
    echo -e "${RED}❌ Erreur lors de l'extraction${NC}"
    exit 1
fi

echo -e "${GREEN}✅ Images installées avec succès${NC}"
echo ""

# Nettoyage local
rm /tmp/images-joy-pharma.tar.gz

echo -e "${GREEN}🎉 Configuration terminée !${NC}"
echo ""
echo "Prochaines étapes :"
echo "1. git add compose.yaml .dockerignore"
echo "2. git commit -m 'feat: volumes pour images persistantes'"
echo "3. git push"
echo "4. Le déploiement GitHub Actions va redémarrer avec les volumes"
echo ""
echo "Pour vérifier :"
echo "  ssh $SERVER 'sudo ls -lh /joy-pharma-data/images/products/ | head'"

