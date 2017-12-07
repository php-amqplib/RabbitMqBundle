<?php

namespace OldSound\RabbitMqBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class RegisterProducerConsumerServicesPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!class_exists(ServiceLocatorTagPass::class)) {
            return;
        }

        $types = [
            'old_sound_rabbit_mq.consumer'=> 'old_sound_rabbit_mq.consumer.command'
        ];

        foreach ($types as $type => $commandType) {
            $services = $container->findTaggedServiceIds($type);
            $handlerServices = array();

            foreach ($services as $id => $tagParams) {
                $handlerServices[$id] = new Reference($id);
            }
            $serviceLocator = ServiceLocatorTagPass::register($container, $handlerServices);

            $commands = $container->findTaggedServiceIds($commandType);
            foreach ($commands as $id => $tagParams) {
                $container->getDefinition($id)->setArgument(1, $serviceLocator);
            }
        }
    }
}
