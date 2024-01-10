<?php

namespace OldSound\RabbitMqBundle\DependencyInjection\Compiler;

use OldSound\RabbitMqBundle\Command\BaseRabbitMqCommand;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ServiceContainerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        foreach ($container->findTaggedServiceIds('console.command') as $id => $attributes) {
            $command = $container->findDefinition($id);
            if (is_a($command->getClass(), BaseRabbitMqCommand::class, true)) {
                $command->addMethodCall('setContainer', [new Reference('service_container')]);
            }
        }
    }
}
