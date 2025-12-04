<?php

namespace App\Stub;

/**
 * Autoloader stub for Twig classes
 * This provides minimal Twig classes needed by VichUploaderBundle
 * without requiring the full Twig package.
 */
class TwigAutoloader
{
    public static function register(): void
    {
        spl_autoload_register([self::class, 'autoload'], true, true);
    }

    public static function autoload(string $class): bool
    {
        // Only handle Twig\Extension namespace
        if (strpos($class, 'Twig\\Extension\\') !== 0) {
            return false;
        }

        // Map Twig classes to our stub
        $stubMap = [
            'Twig\\Extension\\AbstractExtension' => __DIR__ . '/Twig/Extension/AbstractExtension.php',
        ];

        if (isset($stubMap[$class])) {
            require_once $stubMap[$class];
            return true;
        }

        return false;
    }
}

