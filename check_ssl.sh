#!/bin/bash

# Script de diagnostic SSL pour back-preprod.joy-pharma.com
# Usage: ./check_ssl.sh

DOMAIN="back-preprod.joy-pharma.com"

echo "=========================================="
echo "Diagnostic SSL pour $DOMAIN"
echo "=========================================="
echo ""

# 1. Vérification DNS
echo "1. Vérification DNS..."
if dig +short $DOMAIN | grep -q '^[0-9]'; then
    IP=$(dig +short $DOMAIN | head -n 1)
    echo "✓ DNS résolu : $DOMAIN -> $IP"
else
    echo "✗ Erreur : DNS non résolu pour $DOMAIN"
    echo "  Configurez un enregistrement A pour $DOMAIN"
    exit 1
fi
echo ""

# 2. Vérification HTTP
echo "2. Vérification HTTP..."
HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" http://$DOMAIN)
if [ "$HTTP_STATUS" = "308" ] || [ "$HTTP_STATUS" = "301" ] || [ "$HTTP_STATUS" = "302" ]; then
    echo "✓ HTTP fonctionne (redirection vers HTTPS)"
elif [ "$HTTP_STATUS" = "200" ]; then
    echo "⚠ HTTP fonctionne mais pas de redirection HTTPS"
else
    echo "✗ Erreur HTTP : Code $HTTP_STATUS"
fi
echo ""

# 3. Vérification du port 443
echo "3. Vérification du port 443..."
if command -v nc >/dev/null 2>&1; then
    if nc -z -w 2 $DOMAIN 443 2>/dev/null; then
        echo "✓ Port 443 accessible"
    else
        echo "✗ Port 443 non accessible (fermé ou filtré)"
        echo "  → Vérifiez le firewall et que Traefik écoute sur le port 443"
    fi
else
    echo "⚠ nc (netcat) non disponible, test du port ignoré"
fi
echo ""

# 4. Vérification HTTPS (avec contournement)
echo "4. Vérification HTTPS (connexion)..."
HTTPS_OUTPUT=$(curl -k -v -s -o /dev/null -w "%{http_code}" https://$DOMAIN 2>&1)
HTTPS_STATUS=$(echo "$HTTPS_OUTPUT" | grep -oE '[0-9]{3}' | tail -1)

if [ -n "$HTTPS_STATUS" ] && echo "$HTTPS_STATUS" | grep -qE "200|301|302|308"; then
    echo "✓ Connexion HTTPS réussie (certificat ignoré)"
    echo "  Code HTTP : $HTTPS_STATUS"
elif echo "$HTTPS_OUTPUT" | grep -q "Connection refused"; then
    echo "✗ Erreur : Connexion refusée sur le port 443"
    echo "  → Traefik n'écoute peut-être pas sur le port 443"
    echo "  → Vérifiez que Traefik est démarré : docker ps | grep traefik"
elif echo "$HTTPS_OUTPUT" | grep -q "Connection timed out\|timed out"; then
    echo "✗ Erreur : Timeout de connexion"
    echo "  → Le port 443 est peut-être filtré par un firewall"
    echo "  → Vérifiez les règles de firewall sur le serveur"
elif echo "$HTTPS_OUTPUT" | grep -q "Could not resolve host"; then
    echo "✗ Erreur : Impossible de résoudre le nom d'hôte"
    echo "  → Problème DNS (déjà détecté à l'étape 1)"
else
    echo "✗ Erreur : Impossible de se connecter en HTTPS"
    echo "  Détails de l'erreur :"
    echo "$HTTPS_OUTPUT" | grep -i "error\|failed\|refused\|timeout" | head -3 | sed 's/^/    /'
    echo ""
    echo "  Diagnostics supplémentaires :"
    echo "  → Vérifiez que Traefik est démarré : docker ps | grep traefik"
    echo "  → Vérifiez les logs Traefik : docker logs <container-traefik>"
    echo "  → Vérifiez que le conteneur joy-pharma-back est démarré : docker ps | grep joy-pharma-back"
    echo "  → Vérifiez les logs du conteneur : docker logs joy-pharma-back"
fi
echo ""

# 5. Vérification du certificat
echo "5. Vérification du certificat SSL..."
CERT_INFO=$(echo | openssl s_client -connect $DOMAIN:443 -servername $DOMAIN 2>&1)
ISSUER=$(echo "$CERT_INFO" | openssl x509 -noout -issuer 2>/dev/null)
SUBJECT=$(echo "$CERT_INFO" | openssl x509 -noout -subject 2>/dev/null)
DATES=$(echo "$CERT_INFO" | openssl x509 -noout -dates 2>/dev/null)

if [ -z "$ISSUER" ]; then
    echo "✗ Erreur : Impossible de récupérer le certificat"
    if echo "$CERT_INFO" | grep -q "Connection refused"; then
        echo "  → Le port 443 est fermé ou le service n'écoute pas"
    elif echo "$CERT_INFO" | grep -q "timeout"; then
        echo "  → Timeout de connexion"
    else
        echo "  → Vérifiez que le service HTTPS est démarré"
    fi
    echo ""
    echo "  Actions recommandées :"
    echo "  1. Vérifiez que Traefik est démarré sur le serveur"
    echo "  2. Vérifiez que le conteneur joy-pharma-back est démarré"
    echo "  3. Vérifiez les logs : docker logs <container-traefik>"
    echo "  4. Vérifiez la configuration Traefik"
    ISSUER=""
else
    echo "  Émetteur : $ISSUER"
    echo "  Sujet : $SUBJECT"
    echo "  $DATES"
    
    # Vérifier si c'est Let's Encrypt production
    if echo "$ISSUER" | grep -q "Let's Encrypt" && ! echo "$ISSUER" | grep -q "STAGING\|Fake"; then
        echo "✓ Certificat Let's Encrypt production détecté"
    elif echo "$ISSUER" | grep -q "STAGING\|Fake"; then
        echo "⚠ Certificat Let's Encrypt STAGING détecté (non valide pour production)"
        echo "  → Changez le certresolver de 'letsencrypt-staging' à 'letsencrypt'"
    elif echo "$ISSUER" | grep -q "CN=$DOMAIN"; then
        echo "⚠ Certificat auto-signé détecté (non valide)"
        echo "  → Traefik n'a pas réussi à obtenir un certificat Let's Encrypt"
    else
        echo "⚠ Certificat inconnu ou invalide"
    fi
fi
echo ""

# 6. Test curl avec vérification SSL
if [ -n "$ISSUER" ]; then
    echo "6. Test curl avec vérification SSL..."
    if curl -s -o /dev/null -w "%{http_code}" https://$DOMAIN 2>&1 | grep -q "200\|301\|302\|308"; then
        echo "✓ curl avec vérification SSL réussie"
    else
        echo "✗ curl échoue avec vérification SSL"
        echo "  Erreur probable : 'unable to get local issuer certificate'"
        echo "  → Voir section Troubleshooting dans docs/DEPLOYMENT.md"
    fi
    echo ""
fi

# 7. Vérification des logs Traefik (si accessible via SSH)
echo "7. Vérification des erreurs Traefik..."
echo "  Note : Pour vérifier les logs Traefik, exécutez sur le serveur :"
echo "    docker logs <container-traefik> | grep -i 'certresolver\|error'"
echo ""
echo "  Si vous voyez 'Router uses a nonexistent certificate resolver',"
echo "  cela signifie que Traefik n'a pas de certresolver 'letsencrypt' configuré."
echo "  → Voir section 'Erreur Traefik Router uses a nonexistent certificate resolver'"
echo "    dans docs/DEPLOYMENT.md"
echo ""

# 8. Résumé
echo "=========================================="
echo "Résumé"
echo "=========================================="

if [ -z "$ISSUER" ]; then
    echo "✗ Problème de connexion HTTPS détecté"
    echo ""
    echo "Le service HTTPS n'est pas accessible. Vérifiez :"
    echo "  1. Que Traefik est démarré sur le serveur"
    echo "  2. Que le conteneur joy-pharma-back est démarré"
    echo "  3. Que les ports 80 et 443 sont ouverts dans le firewall"
    echo "  4. Les logs Traefik : docker logs <container-traefik>"
    echo "  5. Les logs du conteneur : docker logs joy-pharma-back"
    echo ""
    echo "⚠️  Erreur courante : 'Router uses a nonexistent certificate resolver'"
    echo "   → Traefik n'a pas de certresolver 'letsencrypt' configuré"
    echo "   → Voir docs/DEPLOYMENT.md pour la configuration"
elif echo "$ISSUER" | grep -q "Let's Encrypt" && ! echo "$ISSUER" | grep -q "STAGING\|Fake"; then
    echo "✓ Configuration SSL correcte"
    echo ""
    echo "Pour tester :"
    echo "  curl -I https://$DOMAIN"
else
    echo "⚠ Problème de certificat SSL détecté"
    echo ""
    echo "Actions recommandées :"
    echo "  1. Vérifiez que Traefik utilise 'letsencrypt' (pas 'letsencrypt-staging')"
    echo "  2. Vérifiez les logs Traefik : docker logs <container-traefik>"
    echo "  3. Redémarrez le conteneur : docker restart joy-pharma-back"
    echo "  4. Consultez docs/DEPLOYMENT.md pour plus de détails"
fi
echo ""

