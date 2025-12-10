<?php

/**
 * Health check endpoint for Docker
 * Returns 200 OK if the application is healthy
 */

header('Content-Type: application/json');

$health = [
    'status' => 'ok',
    'timestamp' => date('c'),
    'service' => 'joy-pharma-backend',
];

// Simple health check - just verify PHP is working
http_response_code(200);
echo json_encode($health, JSON_PRETTY_PRINT);

