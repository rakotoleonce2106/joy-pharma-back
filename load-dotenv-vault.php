<?php

/**
 * Load dotenv-vault before Symfony loads environment variables
 * This script should be called before autoload_runtime.php
 * 
 * Based on: https://dotenv.org/docs/languages/php
 */

// Get DOTENV_KEY from environment
$dotenvKey = getenv('DOTENV_KEY');
if ($dotenvKey === false) {
    $dotenvKey = $_ENV['DOTENV_KEY'] ?? $_SERVER['DOTENV_KEY'] ?? null;
}

// If no DOTENV_KEY, skip vault loading (will use regular .env files)
if (empty($dotenvKey)) {
    return;
}

// Store DOTENV_KEY in superglobals for later use
$_ENV['DOTENV_KEY'] = $dotenvKey;
$_SERVER['DOTENV_KEY'] = $dotenvKey;
putenv('DOTENV_KEY=' . $dotenvKey);

// Load dotenv-vault if available
$vaultFile = __DIR__ . '/.env.vault';
if (!file_exists($vaultFile)) {
    // No vault file, skip
    return;
}

try {
    // Try to load using dotenv-vault package if available
    // The package should be installed via composer: dotenv-org/phpdotenv-vault
    if (file_exists(__DIR__ . '/vendor/autoload.php')) {
        require_once __DIR__ . '/vendor/autoload.php';
    }
    
    // Check if the dotenv-vault package is available (dotenv-org/phpdotenv-vault)
    if (class_exists('DotenvVault\DotenvVault')) {
        // Use the official dotenv-vault PHP package
        $dotenv = DotenvVault\DotenvVault::createImmutable(__DIR__);
        $dotenv->safeLoad();
        error_log("[dotenv-vault][INFO] Loading env from encrypted .env.vault");
    } elseif (class_exists('\Dotenv\Vault\Vault')) {
        // Alternative namespace (dotenv-php/dotenv-vault)
        \Dotenv\Vault\Vault::load($vaultFile);
        error_log("[dotenv-vault][INFO] Loading env from encrypted .env.vault");
    } elseif (function_exists('dotenv_vault_load')) {
        // Alternative function-based API
        dotenv_vault_load($vaultFile);
        error_log("[dotenv-vault][INFO] Loading env from encrypted .env.vault");
    } else {
        // Package not installed yet - log a warning but don't fail
        // This allows the project to work before composer install
        error_log("dotenv-vault package not found. Install it with: composer require dotenv-org/phpdotenv-vault");
    }
} catch (\Exception $e) {
    // Silently fail if vault loading fails - fall back to regular .env files
    // This ensures the application can still run without the vault
    error_log("Failed to load .env.vault: " . $e->getMessage());
}

