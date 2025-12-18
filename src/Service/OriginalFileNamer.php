<?php

namespace App\Service;

use Vich\UploaderBundle\Mapping\PropertyMapping;
use Vich\UploaderBundle\Naming\NamerInterface;

/**
 * Namer qui préserve le nom original du fichier
 * pour les images de produits uploadées via JSON
 */
class OriginalFileNamer implements NamerInterface
{
    /**
     * @param object $object L'objet contenant le fichier
     * @param PropertyMapping $mapping La configuration du mapping
     * @return string Le nom du fichier à utiliser
     */
    public function name($object, PropertyMapping $mapping): string
    {
        /** @var \Symfony\Component\HttpFoundation\File\File $file */
        $file = $mapping->getFile($object);
        
        // Récupérer le nom original du fichier
        $originalName = $file->getClientOriginalName();
        
        // Si pas de nom original (fichier système), utiliser le nom du fichier
        if (empty($originalName)) {
            $originalName = $file->getFilename();
        }
        
        // Nettoyer le nom de fichier pour éviter les problèmes
        // Garder uniquement les caractères alphanumériques, tirets, underscores et points
        $cleanName = preg_replace('/[^a-zA-Z0-9\-_\.]/', '-', $originalName);
        
        return $cleanName;
    }
}

