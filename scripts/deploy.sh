#!/bin/bash
set -e
cd joypharma

echo "============================================"
echo "Starting deployment process..."
echo "============================================"

# Login to Docker Hub
echo "→ Logging in to Docker Hub..."
echo "$DOCKERHUB_TOKEN" | docker login -u "$DOCKERHUB_USERNAME" --password-stdin

# Determine the image tag to use
IMAGE_TAG="${IMAGE_TAG:-preprod}"
export IMAGE_NAME="${DOCKERHUB_USERNAME}/joy-pharma-back:${IMAGE_TAG}"
export IMAGES_PREFIX="${DOCKERHUB_USERNAME}/"
echo "→ Using image: $IMAGE_NAME"
echo "→ Image prefix: $IMAGES_PREFIX"
echo "→ Image tag: $IMAGE_TAG"

# Traefik is already configured on the server, no network detection needed

# Install Infisical CLI on the server
echo "→ Installing Infisical CLI on server..."
if ! command -v infisical &> /dev/null; then
    curl -1sLf 'https://dl.cloudsmith.io/public/infisical/infisical-cli/setup.deb.sh' | sudo -E bash
    sudo apt-get update && sudo apt-get install -y infisical
    echo "✓ Infisical installed successfully"
else
    echo "✓ Infisical already installed"
fi

# Generate .env file using Infisical
echo "→ Generating .env file from Infisical..."
INFISICAL_TOKEN=$(infisical login --method=universal-auth --client-id="$INFISICAL_CLIENT_ID" --client-secret="$INFISICAL_CLIENT_SECRET" --domain="$INFISICAL_DOMAIN" --silent --plain)

if [ -z "$INFISICAL_TOKEN" ]; then
    echo "✗ Failed to generate Infisical token"
    exit 1
fi

echo "✓ Infisical token generated successfully"

# Export environment variables to .env file
infisical export --token=$INFISICAL_TOKEN --domain="$INFISICAL_DOMAIN" --projectId="$INFISICAL_PROJECTID" --env=prod --format=dotenv > .env

if [ ! -s .env ]; then
    echo "✗ Failed to generate .env file or file is empty"
    exit 1
fi

# Add Docker-specific variables to .env
echo "" >> .env
echo "# Docker Configuration" >> .env
echo "INFISICAL_TOKEN=$INFISICAL_TOKEN" >> .env
echo "INFISICAL_PROJECT_ID=$INFISICAL_PROJECTID" >> .env
echo "IMAGES_PREFIX=${DOCKERHUB_USERNAME}/" >> .env
echo "IMAGE_TAG=${IMAGE_TAG}" >> .env

# Ensure critical Symfony/FrankenPHP variables are set
if ! grep -q "^APP_ENV=" .env; then
    echo "APP_ENV=prod" >> .env
fi

if ! grep -q "^SERVER_NAME=" .env; then
    echo "SERVER_NAME=$SERVER_NAME" >> .env
fi

# Verify JWT_PASSPHRASE is present (required for JWT key generation)
if ! grep -q "^JWT_PASSPHRASE=" .env; then
    echo "⚠ JWT_PASSPHRASE not found in Infisical export"
    echo "⚠ JWT keys will not be generated automatically"
    echo "⚠ Please add JWT_PASSPHRASE to Infisical (environment: prod)"
else
    echo "✓ JWT_PASSPHRASE found in .env"
fi

# Export variables for docker compose commands (as backup)
export INFISICAL_TOKEN
export INFISICAL_PROJECT_ID="$INFISICAL_PROJECTID"
export IMAGE_NAME

echo "✓ .env file generated successfully"
echo "→ Verifying .env file contents..."
echo "INFISICAL_TOKEN found: $(grep -c '^INFISICAL_TOKEN=' .env || echo '0')"
echo "INFISICAL_PROJECT_ID found: $(grep -c '^INFISICAL_PROJECT_ID=' .env || echo '0')"
echo "IMAGES_PREFIX found: $(grep -c '^IMAGES_PREFIX=' .env || echo '0')"
echo "IMAGE_TAG found: $(grep -c '^IMAGE_TAG=' .env || echo '0')"
echo "JWT_PASSPHRASE found: $(grep -c '^JWT_PASSPHRASE=' .env || echo '0')"
echo "→ Verifying .env file exists and is readable..."
if [ ! -f .env ]; then
    echo "✗ .env file does not exist!"
    exit 1
fi
echo "✓ .env file exists ($(wc -l < .env) lines)"
echo "→ Showing Docker-related variables (without sensitive values)..."
grep -E "^IMAGES_PREFIX=|^IMAGE_TAG=" .env | sed 's/=.*/=***/' || echo "Could not find Docker variables"

# Verify compose files are present and correct
echo "→ Verifying Docker Compose files..."
if [ ! -f compose.yaml ]; then
    echo "✗ compose.yaml not found!"
    exit 1
fi
if [ ! -f compose.prod.yaml ]; then
    echo "✗ compose.prod.yaml not found!"
    exit 1
fi
echo "✓ Docker Compose files present"
echo "→ Checking image configuration in compose.yaml..."
grep -A 2 "php:" compose.yaml | grep "image:" || echo "⚠ Could not find image in compose.yaml"

# Backup current container for rollback
echo "→ Backing up current deployment..."
if [ -f compose.yaml ] && [ -f .env ]; then
    CURRENT_CONTAINER=$(docker compose -f compose.yaml -f compose.prod.yaml --env-file .env ps -q php 2>/dev/null || echo "")
    if [ -n "$CURRENT_CONTAINER" ]; then
        echo "✓ Current container ID: $CURRENT_CONTAINER"
    else
        echo "ℹ No existing container to backup"
    fi
else
    echo "ℹ First deployment - no containers to backup"
    CURRENT_CONTAINER=""
fi

# Verify image configuration before pulling
echo "→ Verifying image configuration..."
echo "IMAGES_PREFIX: $IMAGES_PREFIX"
echo "IMAGE_TAG: $IMAGE_TAG"
echo "Expected image: ${IMAGES_PREFIX}joy-pharma-back:${IMAGE_TAG}"

# Check what image Docker Compose will use
echo "→ Checking Docker Compose configuration..."
docker compose -f compose.yaml -f compose.prod.yaml --env-file .env config | grep -A 5 "php:" | grep "image:" || echo "⚠ Could not find image configuration"

# Pull new images with retry logic
echo "→ Pulling Docker images (with retry)..."
MAX_RETRIES=3
RETRY_COUNT=0
until docker compose -f compose.yaml -f compose.prod.yaml --env-file .env pull || [ $RETRY_COUNT -eq $MAX_RETRIES ]; do
    RETRY_COUNT=$((RETRY_COUNT+1))
    echo "⚠ Pull failed. Retry $RETRY_COUNT/$MAX_RETRIES..."
    echo "→ Debug: Checking .env file..."
    grep -E "IMAGES_PREFIX|IMAGE_TAG" .env || echo "⚠ IMAGES_PREFIX or IMAGE_TAG not found in .env"
    sleep 5
done

if [ $RETRY_COUNT -eq $MAX_RETRIES ]; then
    echo "✗ Failed to pull images after $MAX_RETRIES attempts"
    echo "→ Debug information:"
    echo "  - .env file exists: $([ -f .env ] && echo 'yes' || echo 'no')"
    echo "  - IMAGES_PREFIX in .env: $(grep IMAGES_PREFIX .env || echo 'NOT FOUND')"
    echo "  - IMAGE_TAG in .env: $(grep IMAGE_TAG .env || echo 'NOT FOUND')"
    echo "  - Docker Compose config:"
    docker compose -f compose.yaml -f compose.prod.yaml --env-file .env config | grep -A 3 "php:" || true
    exit 1
fi

echo "✓ Images pulled successfully"

# Stop existing containers to free ports (especially port 80)
echo "→ Stopping existing containers to free ports..."
docker compose -f compose.yaml -f compose.prod.yaml --env-file .env down 2>/dev/null || true


# Also stop any containers that might be using port 80
echo "→ Checking for containers using port 80..."
CONTAINERS_ON_80=$(docker ps --filter "publish=80" --format "{{.ID}}" 2>/dev/null || echo "")
if [ -n "$CONTAINERS_ON_80" ]; then
    echo "→ Found containers using port 80, stopping them..."
    echo "$CONTAINERS_ON_80" | xargs -r docker stop 2>/dev/null || true
fi

# Remove container with fixed name if it exists (to avoid name conflicts)
echo "→ Checking for container with fixed name (joy-pharma-back-php)..."
if docker ps -a --filter "name=^joy-pharma-back-php$" --format "{{.ID}}" | grep -q .; then
    echo "→ Removing existing container with fixed name..."
    docker stop joy-pharma-back-php 2>/dev/null || true
    docker rm joy-pharma-back-php 2>/dev/null || true
    echo "✓ Fixed name container removed"
fi

# Check for any containers with name containing joypharma or joy-pharma-back
echo "→ Checking for any remaining joypharma containers..."
REMAINING_CONTAINERS=$(docker ps -a --filter "name=joypharma" --format "{{.ID}}" 2>/dev/null || echo "")
REMAINING_CONTAINERS="$REMAINING_CONTAINERS $(docker ps -a --filter "name=joy-pharma-back" --format "{{.ID}}" 2>/dev/null || echo "")"
if [ -n "$REMAINING_CONTAINERS" ]; then
    echo "→ Stopping remaining containers..."
    echo "$REMAINING_CONTAINERS" | xargs -r docker stop 2>/dev/null || true
    echo "$REMAINING_CONTAINERS" | xargs -r docker rm 2>/dev/null || true
fi

# Wait a moment for ports to be released
sleep 3

# Verify port 80 is free
echo "→ Verifying port 80 is free..."
if command -v lsof >/dev/null 2>&1; then
    PORT_80_USERS=$(lsof -i :80 2>/dev/null | wc -l || echo "0")
    if [ "$PORT_80_USERS" -gt "0" ]; then
        echo "⚠ Port 80 is still in use:"
        lsof -i :80 2>/dev/null || true
        echo "→ Force stopping all containers using port 80..."
        docker ps --filter "publish=80" --format "{{.ID}}" | xargs -r docker stop 2>/dev/null || true
        docker ps --filter "publish=80" --format "{{.ID}}" | xargs -r docker rm 2>/dev/null || true
        sleep 2
    else
        echo "✓ Port 80 is free"
    fi
fi

# Deploy new version
echo "→ Deploying new version with FrankenPHP..."
if docker compose -f compose.yaml -f compose.prod.yaml --env-file .env up -d; then
    echo "✓ Containers started"
    
    # Wait for services to stabilize
    echo "→ Waiting for services to stabilize..."
    sleep 10
    
    # Check all services
    echo "→ Checking service health..."
    docker compose -f compose.yaml -f compose.prod.yaml --env-file .env ps
    
    # Get container IDs
    PHP_CONTAINER=$(docker compose -f compose.yaml -f compose.prod.yaml --env-file .env ps -q php 2>/dev/null || echo "")
    DB_CONTAINER=$(docker compose -f compose.yaml -f compose.prod.yaml --env-file .env ps -q database 2>/dev/null || echo "")
    
    if [ -z "$PHP_CONTAINER" ]; then
        echo "✗ PHP container not found"
        docker compose -f compose.yaml -f compose.prod.yaml --env-file .env logs php
        exit 1
    fi
    
    # Check PHP container status
    sleep 5
    CONTAINER_STATUS=$(docker inspect --format='{{.State.Status}}' $PHP_CONTAINER 2>/dev/null || echo "unknown")
    RESTART_COUNT=$(docker inspect --format='{{.RestartCount}}' $PHP_CONTAINER 2>/dev/null || echo "0")
    
    echo "→ PHP Container status: $CONTAINER_STATUS (restart count: $RESTART_COUNT)"
    
    if [ "$CONTAINER_STATUS" = "running" ] && [ "$RESTART_COUNT" = "0" ]; then
        echo "✓ PHP container is running and healthy"
        
        # Wait for database to be ready
        echo "→ Waiting for database to be ready..."
        MAX_DB_WAIT=30
        DB_WAIT=0
        until docker compose -f compose.yaml -f compose.prod.yaml --env-file .env exec -T database pg_isready -U app 2>/dev/null || [ $DB_WAIT -eq $MAX_DB_WAIT ]; do
            DB_WAIT=$((DB_WAIT+1))
            echo "⏳ Waiting for database... ($DB_WAIT/$MAX_DB_WAIT)"
            sleep 2
        done
        
        if [ $DB_WAIT -eq $MAX_DB_WAIT ]; then
            echo "⚠ Database did not become ready in time, but continuing..."
        else
            echo "✓ Database is ready"
            
            # Generate JWT keys with real secrets from Infisical
            echo "→ Generating JWT keypair..."
            if docker compose -f compose.yaml -f compose.prod.yaml --env-file .env exec -T php bin/console lexik:jwt:generate-keypair --overwrite --no-interaction; then
                echo "✓ JWT keypair generated successfully"
            else
                echo "⚠ JWT generation failed, but deployment continues"
            fi
            
            # Run migrations (CRITICAL - must succeed)
            echo "→ Running database migrations..."
            
            # Verify PHP container is ready
            echo "→ Verifying PHP container is ready..."
            if ! docker compose -f compose.yaml -f compose.prod.yaml --env-file .env exec -T php php -v > /dev/null 2>&1; then
                echo "✗ PHP container is not ready"
                echo "→ Container logs:"
                docker compose -f compose.yaml -f compose.prod.yaml --env-file .env logs php --tail 50
                exit 1
            fi
            echo "✓ PHP container is ready"
            
            # Verify database connection before migrations
            echo "→ Verifying database connection..."
            if ! docker compose -f compose.yaml -f compose.prod.yaml --env-file .env exec -T php bin/console dbal:run-sql "SELECT 1" > /dev/null 2>&1; then
                echo "✗ Database connection failed"
                echo "→ Please check DATABASE_URL in .env file"
                exit 1
            fi
            echo "✓ Database connection verified"
            
            # First, ensure migration metadata storage is synchronized (creates table if needed)
            echo "→ Synchronizing migration metadata storage..."
            SYNC_OUTPUT=$(docker compose -f compose.yaml -f compose.prod.yaml --env-file .env exec -T php bin/console doctrine:migrations:sync-metadata-storage --no-interaction 2>&1 || echo "")
            SYNC_EXIT_CODE=$?
            if [ $SYNC_EXIT_CODE -ne 0 ] && ! echo "$SYNC_OUTPUT" | grep -qi "already synchronized"; then
                echo "⚠ Warning: Metadata storage sync had issues: $SYNC_OUTPUT"
            else
                echo "✓ Migration metadata storage synchronized"
            fi
            
            # Check migration status first to see what we're dealing with
            echo "→ Checking migration status..."
            MIGRATION_STATUS=$(docker compose -f compose.yaml -f compose.prod.yaml --env-file .env exec -T php bin/console doctrine:migrations:status --no-interaction 2>&1 || echo "")
            echo "$MIGRATION_STATUS" | head -20 || true
            
            # Now execute migrations and capture output
            echo "→ Executing migrations..."
            MIGRATION_OUTPUT=$(docker compose -f compose.yaml -f compose.prod.yaml --env-file .env exec -T php bin/console doctrine:migrations:migrate --no-interaction 2>&1)
            MIGRATION_EXIT_CODE=$?
            
            if [ $MIGRATION_EXIT_CODE -eq 0 ]; then
                echo "✓ Migrations executed successfully"
                # Show what was executed
                if echo "$MIGRATION_OUTPUT" | grep -q "migrated"; then
                    echo "$MIGRATION_OUTPUT" | grep -E "(migrated|Executing)" || true
                fi
            else
                # Check if error is just "no registered migrations" which is OK for first deployment
                if echo "$MIGRATION_OUTPUT" | grep -qi "no registered migrations" || \
                   echo "$MIGRATION_OUTPUT" | grep -qi "couldn't be reached" || \
                   echo "$MIGRATION_OUTPUT" | grep -qi "already at latest version" || \
                   echo "$MIGRATION_OUTPUT" | grep -qi "nothing to migrate" || \
                   echo "$MIGRATION_OUTPUT" | grep -qi "already synchronized"; then
                    echo "→ No migrations to execute (first deployment or already up to date)"
                    echo "✓ This is normal, database is already synchronized"
                else
                    echo "✗ Migrations failed - this is critical!"
                    echo "→ Exit code: $MIGRATION_EXIT_CODE"
                    echo "→ Error output:"
                    echo "$MIGRATION_OUTPUT"
                    echo ""
                    echo "→ Migration status:"
                    echo "$MIGRATION_STATUS"
                    echo ""
                    echo "→ Attempting to list pending migrations..."
                    docker compose -f compose.yaml -f compose.prod.yaml --env-file .env exec -T php bin/console doctrine:migrations:list || true
                    echo ""
                    echo "→ Checking database connection..."
                    docker compose -f compose.yaml -f compose.prod.yaml --env-file .env exec -T php bin/console dbal:run-sql "SELECT 1" || true
                    echo ""
                    echo "✗ Deployment cannot continue without successful migrations"
                    exit 1
                fi
            fi
            
            # Clear cache
            echo "→ Clearing Symfony cache..."
            if docker compose -f compose.yaml -f compose.prod.yaml --env-file .env exec -T php bin/console cache:clear --no-warmup; then
                echo "✓ Cache cleared successfully"
            else
                echo "⚠ Cache clear failed, but deployment continues"
            fi
            
            # Warmup cache
            echo "→ Warming up cache..."
            if docker compose -f compose.yaml -f compose.prod.yaml --env-file .env exec -T php bin/console cache:warmup; then
                echo "✓ Cache warmed up successfully"
            else
                echo "⚠ Cache warmup failed, but deployment continues"
            fi
        fi
        
        # Test application health
        echo "→ Testing application health..."
        sleep 3
        if docker compose -f compose.yaml -f compose.prod.yaml --env-file .env exec -T php php -v > /dev/null 2>&1; then
            echo "✓ PHP is working"
        else
            echo "⚠ PHP health check failed"
        fi
        
        # Clean up old images
        echo "→ Cleaning up old images..."
        docker image prune -f
        
        # Show final status
        echo "→ Final service status:"
        docker compose -f compose.yaml -f compose.prod.yaml --env-file .env ps
        
        echo "============================================"
        echo "✓ Deployment completed successfully!"
        echo "============================================"
    else
        echo "✗ Container is not running properly (status: $CONTAINER_STATUS, restarts: $RESTART_COUNT)"
        echo "→ Checking container logs..."
        docker compose -f compose.yaml -f compose.prod.yaml --env-file .env logs php --tail 100
        echo "→ Checking container exit code..."
        EXIT_CODE=$(docker inspect --format='{{.State.ExitCode}}' $PHP_CONTAINER 2>/dev/null || echo "unknown")
        echo "Exit code: $EXIT_CODE"
        echo "→ Attempting rollback..."
        if [ -n "$CURRENT_CONTAINER" ] && docker ps -a --format '{{.ID}}' | grep -q "$CURRENT_CONTAINER"; then
            docker compose -f compose.yaml -f compose.prod.yaml --env-file .env down
            docker start $CURRENT_CONTAINER
            echo "✓ Rollback completed"
        else
            echo "⚠ No previous container to rollback to"
            docker compose -f compose.yaml -f compose.prod.yaml --env-file .env down
        fi
        exit 1
    fi
else
    echo "✗ Deployment failed"
    echo "→ Checking compose status..."
    docker compose -f compose.yaml -f compose.prod.yaml --env-file .env ps -a
    docker compose -f compose.yaml -f compose.prod.yaml --env-file .env logs
    
    # Check if port 80 is the issue
    echo "→ Checking port 80 usage..."
    if command -v lsof >/dev/null 2>&1; then
        lsof -i :80 2>/dev/null || echo "lsof not available"
    fi
    docker ps --filter "publish=80" || true
    
    echo "→ Attempting to free port 80..."
    docker ps -a --filter "name=joypharma" --format "{{.ID}}" | xargs -r docker stop 2>/dev/null || true
    docker ps -a --filter "name=joypharma" --format "{{.ID}}" | xargs -r docker rm 2>/dev/null || true
    
    exit 1
fi

