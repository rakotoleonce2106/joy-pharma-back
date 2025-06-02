<?php

declare(strict_types=1);

namespace App\DependencyInjection\Compiler;

use App\Service\ToastService;
use App\Traits\ToastTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

class ToastServiceCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $container->getDefinition(ToastService::class)->setPublic(true);

        foreach (array_merge($container->findTaggedServiceIds('controller.service_arguments'), $container->findTaggedServiceIds('twig.component')) as $id => $tags) {
            $definition = $container->getDefinition($id);
            $class = $definition->getClass();

            // check if the class uses the ToastTrait
            if ($class === null || !in_array(ToastTrait::class, class_uses($class ?? ''))) {
                continue;
            }

            $definition->addMethodCall('setToastService', [new Reference(ToastService::class)]);
        }
    }
}
