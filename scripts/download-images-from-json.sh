#!/bin/bash

# Script pour télécharger toutes les images depuis un fichier JSON
# Usage: ./download-images-from-json.sh <json_file> [output_dir]

set -e

# Couleurs pour les messages
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Fonction pour afficher les messages
log_info() {
    echo -e "${BLUE}ℹ${NC} $1"
}

log_success() {
    echo -e "${GREEN}✓${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

log_error() {
    echo -e "${RED}✗${NC} $1"
}

# Vérifier les arguments
if [ $# -lt 1 ]; then
    log_error "Usage: $0 <json_file> [output_dir]"
    echo ""
    echo "Exemple:"
    echo "  $0 medicaments_1.json"
    echo "  $0 medicaments_1.json /path/to/output"
    exit 1
fi

JSON_FILE="$1"
OUTPUT_DIR="${2:-public/images/products}"

# Vérifier que le fichier JSON existe
if [ ! -f "$JSON_FILE" ]; then
    log_error "Fichier JSON introuvable: $JSON_FILE"
    exit 1
fi

# Vérifier que jq est installé
if ! command -v jq &> /dev/null; then
    log_error "jq n'est pas installé. Installation requise:"
    echo "  macOS: brew install jq"
    echo "  Ubuntu/Debian: sudo apt-get install jq"
    exit 1
fi

# Créer le répertoire de sortie s'il n'existe pas
mkdir -p "$OUTPUT_DIR"

log_info "Fichier JSON: $JSON_FILE"
log_info "Répertoire de sortie: $OUTPUT_DIR"
echo ""

# Compter le nombre total d'images
TOTAL_IMAGES=$(jq -r '.[].images[]' "$JSON_FILE" 2>/dev/null | wc -l | tr -d ' ')

if [ "$TOTAL_IMAGES" -eq 0 ]; then
    log_warning "Aucune image trouvée dans le fichier JSON"
    exit 0
fi

log_info "Nombre total d'images à traiter: $TOTAL_IMAGES"
echo ""

# Compteurs
DOWNLOADED=0
SKIPPED=0
FAILED=0
CURRENT=0

# Extraire toutes les URLs d'images du JSON et les télécharger
jq -r '.[].images[]' "$JSON_FILE" 2>/dev/null | while read -r url; do
    CURRENT=$((CURRENT + 1))
    
    # Extraire le nom de fichier
    filename=$(basename "$url")
    output_path="$OUTPUT_DIR/$filename"
    
    printf "[%d/%d] " "$CURRENT" "$TOTAL_IMAGES"
    
    # Vérifier si le fichier existe déjà
    if [ -f "$output_path" ]; then
        log_warning "Existe déjà: $filename"
        SKIPPED=$((SKIPPED + 1))
        continue
    fi
    
    # Télécharger l'image
    echo -n "Téléchargement: $filename ... "
    
    if wget -q --timeout=30 --tries=3 -O "$output_path" "$url" 2>/dev/null; then
        # Vérifier que le fichier téléchargé n'est pas vide
        if [ -s "$output_path" ]; then
            log_success "OK"
            DOWNLOADED=$((DOWNLOADED + 1))
        else
            log_error "Fichier vide"
            rm -f "$output_path"
            FAILED=$((FAILED + 1))
        fi
    else
        log_error "Échec"
        rm -f "$output_path"
        FAILED=$((FAILED + 1))
    fi
done

# Résumé
echo ""
echo "═══════════════════════════════════════"
log_info "Résumé du téléchargement"
echo "═══════════════════════════════════════"
log_success "Téléchargées: $DOWNLOADED"
log_warning "Ignorées (déjà présentes): $SKIPPED"
if [ "$FAILED" -gt 0 ]; then
    log_error "Échouées: $FAILED"
else
    log_success "Échouées: 0"
fi
echo "═══════════════════════════════════════"
echo ""

# Afficher des statistiques sur les fichiers
if [ "$DOWNLOADED" -gt 0 ]; then
    TOTAL_SIZE=$(du -sh "$OUTPUT_DIR" | cut -f1)
    log_info "Taille totale du répertoire: $TOTAL_SIZE"
    log_info "Nombre de fichiers: $(find "$OUTPUT_DIR" -type f | wc -l | tr -d ' ')"
fi

# Code de sortie
if [ "$FAILED" -gt 0 ]; then
    exit 1
else
    exit 0
fi

