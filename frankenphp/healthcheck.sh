#!/bin/sh
set -e

# Health check script pour FrankenPHP
# Vérifie que l'application répond correctement

# Vérifier que FrankenPHP répond
if ! curl -f -s http://localhost/api > /dev/null 2>&1; then
    echo "FrankenPHP is not responding"
    exit 1
fi

# Vérifier que PHP fonctionne
if ! php -v > /dev/null 2>&1; then
    echo "PHP is not working"
    exit 1
fi

echo "Health check passed"
exit 0

