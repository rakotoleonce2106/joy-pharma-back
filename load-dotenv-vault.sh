#!/bin/sh
# Script to load dotenv-vault and export variables to shell environment

if [ -z "$DOTENV_KEY" ]; then
    echo "DOTENV_KEY not set, skipping dotenv-vault loading"
    exit 0
fi

if [ ! -f .env.vault ]; then
    echo ".env.vault file not found, skipping dotenv-vault loading"
    exit 0
fi

# Load dotenv-vault using PHP and export variables to shell
if php export-dotenv-vault.php > /tmp/dotenv-exports.sh 2>/dev/null; then
    set -a
    . /tmp/dotenv-exports.sh
    set +a
    echo "Loaded environment variables from dotenv-vault"
    rm -f /tmp/dotenv-exports.sh
else
    echo "Warning: Failed to load dotenv-vault, continuing with existing environment"
fi

