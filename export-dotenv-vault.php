<?php
/**
 * Export dotenv-vault variables to shell environment
 * This script loads dotenv-vault and exports all variables via putenv()
 * so they are available to shell commands like composer dump-env
 */

// Load dotenv-vault
require_once __DIR__ . '/load-dotenv-vault.php';

// Get all environment variables and export them via putenv
// This makes them available to child processes
$envVars = $_ENV;
foreach ($envVars as $key => $value) {
    if (is_string($value)) {
        putenv("$key=$value");
    }
}

// Also export from $_SERVER (some variables might be there)
foreach ($_SERVER as $key => $value) {
    if (is_string($value) && !isset($envVars[$key])) {
        putenv("$key=$value");
    }
}

// Output variables in shell format for sourcing
// This allows the shell script to export them
foreach ($envVars as $key => $value) {
    if (is_string($value)) {
        // Escape special characters for shell
        $escapedValue = escapeshellarg($value);
        echo "export $key=$escapedValue\n";
    }
}

