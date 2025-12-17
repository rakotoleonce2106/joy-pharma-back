#!/bin/bash

# Script de setup des images sur le serveur
# À exécuter DEPUIS votre Mac

set -e

# ========================================
# Configuration - MODIFIER CES VALEURS
# ========================================
SERVER_USER="root"
SERVER_HOST="your-server.com"
SERVER_SSH="$SERVER_USER@$SERVER_HOST"
LOCAL_IMAGES_PATH="public/images/"
REMOTE_DATA_PATH="/joy-pharma-data"
REMOTE_APP_PATH="/joy-pharma-back"

# Couleurs pour l'affichage
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# ========================================
# Fonctions
# ========================================

echo_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

echo_warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

echo_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

check_ssh_connection() {
    echo_info "Vérification de la connexion SSH..."
    if ssh -o ConnectTimeout=5 $SERVER_SSH "echo 'OK'" > /dev/null 2>&1; then
        echo_info "✅ Connexion SSH établie"
        return 0
    else
        echo_error "❌ Impossible de se connecter au serveur"
        echo_error "Vérifiez : ssh $SERVER_SSH"
        exit 1
    fi
}

create_remote_directories() {
    echo_info "Création des dossiers sur le serveur..."
    
    ssh $SERVER_SSH << 'EOF'
        # Créer la structure
        sudo mkdir -p /joy-pharma-data/images/products
        sudo mkdir -p /joy-pharma-data/images/profile
        sudo mkdir -p /joy-pharma-data/media
        sudo mkdir -p /joy-pharma-data/uploads
        
        # Définir les permissions
        sudo chown -R www-data:www-data /joy-pharma-data/
        sudo chmod -R 755 /joy-pharma-data/
        
        echo "✅ Dossiers créés avec succès"
        ls -la /joy-pharma-data/
EOF
    
    echo_info "✅ Dossiers créés sur le serveur"
}

sync_images() {
    echo_info "Synchronisation des images (cela peut prendre plusieurs minutes)..."
    
    if [ ! -d "$LOCAL_IMAGES_PATH" ]; then
        echo_error "Le dossier $LOCAL_IMAGES_PATH n'existe pas"
        exit 1
    fi
    
    # Compter les fichiers locaux
    local_files=$(find "$LOCAL_IMAGES_PATH" -type f | wc -l | xargs)
    echo_info "📊 Nombre de fichiers locaux : $local_files"
    
    # Synchroniser avec rsync
    rsync -avz --progress \
        --exclude='.DS_Store' \
        --exclude='*.tmp' \
        --exclude='Thumbs.db' \
        "$LOCAL_IMAGES_PATH" \
        "$SERVER_SSH:$REMOTE_DATA_PATH/images/"
    
    echo_info "✅ Images synchronisées"
}

fix_permissions() {
    echo_info "Ajustement des permissions..."
    
    ssh $SERVER_SSH << EOF
        sudo chown -R www-data:www-data $REMOTE_DATA_PATH/images/
        sudo chmod -R 755 $REMOTE_DATA_PATH/images/
        
        # Afficher les statistiques
        echo "📊 Statistiques :"
        echo "  Nombre de fichiers : \$(find $REMOTE_DATA_PATH/images -type f | wc -l)"
        echo "  Taille totale : \$(du -sh $REMOTE_DATA_PATH/images | cut -f1)"
        
        echo "✅ Permissions ajustées"
EOF
    
    echo_info "✅ Permissions configurées"
}

update_docker_compose() {
    echo_info "Mise à jour du docker-compose.yml sur le serveur..."
    
    # Copier le fichier
    scp docker-compose.prod.example.yml $SERVER_SSH:$REMOTE_APP_PATH/docker-compose.yml.new
    
    ssh $SERVER_SSH << EOF
        cd $REMOTE_APP_PATH
        
        # Backup de l'ancien fichier
        if [ -f docker-compose.yml ]; then
            cp docker-compose.yml docker-compose.yml.backup.$(date +%Y%m%d_%H%M%S)
            echo "✅ Backup de l'ancien docker-compose.yml créé"
        fi
        
        # Remplacer par le nouveau
        mv docker-compose.yml.new docker-compose.yml
        
        echo "✅ docker-compose.yml mis à jour"
EOF
    
    echo_info "✅ docker-compose.yml mis à jour"
}

restart_containers() {
    echo_warn "Redémarrage des containers..."
    
    ssh $SERVER_SSH << EOF
        cd $REMOTE_APP_PATH
        docker compose down
        docker compose up -d
        
        echo "⏳ Attente du démarrage (10 secondes)..."
        sleep 10
        
        echo "📊 État des containers :"
        docker compose ps
EOF
    
    echo_info "✅ Containers redémarrés"
}

verify_setup() {
    echo_info "Vérification de l'installation..."
    
    ssh $SERVER_SSH << EOF
        cd $REMOTE_APP_PATH
        
        echo "🔍 Vérification 1 : Volumes montés"
        docker compose exec -T php df -h | grep images || echo "❌ Volume non monté"
        
        echo ""
        echo "🔍 Vérification 2 : Fichiers accessibles depuis le container"
        docker compose exec -T php ls -lh /app/public/images/products/ | head -5 || echo "❌ Fichiers non accessibles"
        
        echo ""
        echo "🔍 Vérification 3 : Permissions"
        docker compose exec -T php ls -la /app/public/images/ || echo "❌ Problème de permissions"
EOF
    
    echo_info "✅ Vérification terminée"
}

# ========================================
# Menu principal
# ========================================

show_menu() {
    echo ""
    echo "=========================================="
    echo "  Setup Images Joy Pharma - Serveur"
    echo "=========================================="
    echo ""
    echo "Configuration actuelle :"
    echo "  Serveur : $SERVER_SSH"
    echo "  Images locales : $LOCAL_IMAGES_PATH"
    echo "  Dossier distant : $REMOTE_DATA_PATH"
    echo ""
    echo "Options :"
    echo "  1) Setup complet (recommandé)"
    echo "  2) Créer les dossiers uniquement"
    echo "  3) Synchroniser les images uniquement"
    echo "  4) Mettre à jour docker-compose.yml"
    echo "  5) Redémarrer les containers"
    echo "  6) Vérifier l'installation"
    echo "  0) Quitter"
    echo ""
    echo -n "Votre choix : "
}

# ========================================
# Script principal
# ========================================

echo ""
echo "🚀 Setup des images sur le serveur Joy Pharma"
echo ""

# Vérifier que nous sommes dans le bon dossier
if [ ! -f "composer.json" ]; then
    echo_error "Ce script doit être exécuté depuis la racine du projet joy-pharma-back"
    exit 1
fi

# Vérifier la connexion SSH
check_ssh_connection

# Afficher le menu
while true; do
    show_menu
    read choice
    
    case $choice in
        1)
            echo_info "🚀 Setup complet en cours..."
            create_remote_directories
            sync_images
            fix_permissions
            echo_warn "Voulez-vous mettre à jour docker-compose.yml ? (y/n)"
            read -r update_compose
            if [[ "$update_compose" =~ ^[Yy]$ ]]; then
                update_docker_compose
                echo_warn "Voulez-vous redémarrer les containers maintenant ? (y/n)"
                read -r restart
                if [[ "$restart" =~ ^[Yy]$ ]]; then
                    restart_containers
                fi
            fi
            verify_setup
            echo_info "🎉 Setup complet terminé !"
            ;;
        2)
            create_remote_directories
            ;;
        3)
            sync_images
            fix_permissions
            ;;
        4)
            update_docker_compose
            ;;
        5)
            restart_containers
            ;;
        6)
            verify_setup
            ;;
        0)
            echo_info "Au revoir !"
            exit 0
            ;;
        *)
            echo_error "Option invalide"
            ;;
    esac
    
    echo ""
    echo "Appuyez sur Entrée pour continuer..."
    read
done

