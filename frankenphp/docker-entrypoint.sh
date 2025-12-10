#!/bin/sh
set -e

# Docker entrypoint script for FrankenPHP
# This script ensures JWT keys are generated before starting the application

echo "🚀 Starting Joy Pharma Backend initialization..."

# Wait a bit for the filesystem to be ready
sleep 2

# Check if JWT keys exist, generate them if not
if [ ! -f "/app/config/jwt/private.pem" ] || [ ! -f "/app/config/jwt/public.pem" ]; then
    echo "🔑 JWT keys not found, generating..."
    
    # Ensure config/jwt directory exists
    mkdir -p /app/config/jwt
    
    # Generate JWT keypair
    if php /app/bin/console lexik:jwt:generate-keypair --overwrite --no-interaction; then
        echo "✅ JWT keypair generated successfully"
    else
        echo "⚠️  JWT generation failed, the application may not work correctly"
    fi
else
    echo "✅ JWT keys already exist"
fi

# Ensure proper permissions
chmod 644 /app/config/jwt/*.pem 2>/dev/null || true

echo "✅ Initialization complete, starting application..."

# Execute the CMD
exec "$@"

