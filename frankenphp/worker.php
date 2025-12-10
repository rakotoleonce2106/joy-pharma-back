<?php

/**
 * Worker entrypoint pour FrankenPHP
 * Ce fichier est utilisé en mode Worker pour précharger l'application Symfony
 */

use App\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};

