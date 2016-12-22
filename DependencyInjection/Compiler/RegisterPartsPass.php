<?php

namespace OldSound\RabbitMqBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

class RegisterPartsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $services = $container->findTaggedServiceIds('old_sound_rabbit_mq.base_amqp');
        $container->setParameter('old_sound_rabbit_mq.base_amqp', array_keys($services));
        if (!$container->hasDefinition('old_sound_rabbit_mq.parts_holder')) {
            return;
        }

        $definition = $container->getDefinition('old_sound_rabbit_mq.parts_holder');

        $tags = array(
            'old_sound_rabbit_mq.base_amqp',
            'old_sound_rabbit_mq.binding',
            'old_sound_rabbit_mq.producer',
            'old_sound_rabbit_mq.consumer',
            'old_sound_rabbit_mq.multi_consumer',
            'old_sound_rabbit_mq.anon_consumer',
            'old_sound_rabbit_mq.rpc_client',
            'old_sound_rabbit_mq.rpc_server',
        );

        foreach ($tags as $tag) {
            foreach ($container->findTaggedServiceIds($tag) as $id => $attributes) {
                $definition->addMethodCall('addPart', array($tag, new Reference($id)));
            }
        }
    }
}
