<?php

namespace App\Service;

use Monolog\Handler\StreamHandler;
use Monolog\LogRecord;

/**
 * Filtre les avertissements de dépréciation Doctrine concernant l'autoloader de proxy
 * qui sont non critiques car l'application utilise déjà enable_lazy_ghost_objects
 */
class DoctrineDeprecationFilterHandler extends StreamHandler
{
    public function handle(LogRecord $record): bool
    {
        // Filtrer uniquement les messages de dépréciation Doctrine concernant l'autoloader de proxy
        if (
            $record->channel === 'deprecation' &&
            $record->level->value <= 200 && // INFO level
            isset($record->context['exception'])
        ) {
            // Gérer le cas où exception est un objet ou un tableau
            $exception = $record->context['exception'];
            $message = is_object($exception) 
                ? ($exception->getMessage() ?? $exception->message ?? '')
                : ($exception['message'] ?? '');
            
            if (
                str_contains($message, 'Proxy\\Autoloader') ||
                str_contains($message, 'native lazy objects')
            ) {
                // Ignorer ce message
                return false;
            }
        }

        // Laisser passer tous les autres messages au handler parent
        return parent::handle($record);
    }
}

