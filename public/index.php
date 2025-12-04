<?php

// Load Twig stub autoloader manually before Composer autoloader
require_once dirname(__DIR__) . '/src/Stub/TwigAutoloader.php';

use App\Kernel;
use App\Stub\TwigAutoloader;

// Register Twig stub autoloader before Composer autoloader
TwigAutoloader::register();

// Load dotenv-vault before Symfony runtime
require_once dirname(__DIR__).'/load-dotenv-vault.php';

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
