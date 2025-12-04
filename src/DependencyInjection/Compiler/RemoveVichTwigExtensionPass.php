<?php

namespace App\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class RemoveVichTwigExtensionPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        // Remove VichUploaderBundle Twig extension if Twig is not available
        $twigExtensionClass = 'Vich\UploaderBundle\Twig\Extension\UploaderExtension';
        
        // Remove by service ID (common IDs used by VichUploaderBundle)
        $possibleServiceIds = [
            'vich_uploader.twig.extension.uploader',
            'Vich\UploaderBundle\Twig\Extension\UploaderExtension',
        ];
        
        foreach ($possibleServiceIds as $serviceId) {
            if ($container->hasDefinition($serviceId)) {
                $container->removeDefinition($serviceId);
            }
            if ($container->hasAlias($serviceId)) {
                $container->removeAlias($serviceId);
            }
        }
        
        // Remove by class name (search all definitions)
        foreach ($container->getDefinitions() as $id => $definition) {
            $class = $definition->getClass();
            if ($class === $twigExtensionClass || 
                (is_string($class) && str_contains($class, 'Vich\\UploaderBundle\\Twig'))) {
                $container->removeDefinition($id);
            }
        }
        
        // Also remove any aliases pointing to Twig extension
        foreach ($container->getAliases() as $id => $alias) {
            $target = (string) $alias;
            if (str_contains($target, 'Vich\\UploaderBundle\\Twig') || 
                str_contains($target, 'UploaderExtension')) {
                $container->removeAlias($id);
            }
        }
    }
}

