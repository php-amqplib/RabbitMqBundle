<?php

namespace OldSound\RabbitMqBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class InjectEventDispatcherPass
 *
 * @package OldSound\RabbitMqBundle\DependencyInjection\Compiler
 */
class InjectEventDispatcherPass implements CompilerPassInterface
{
    const EVENT_DISPATCHER_SERVICE_ID = 'event_dispatcher';

    /**
     * @inheritDoc
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->has(self::EVENT_DISPATCHER_SERVICE_ID)) {
            return;
        }
        $taggedConsumers = $container->findTaggedServiceIds('old_sound_rabbit_mq.base_amqp');

        foreach ($taggedConsumers as $id => $tag) {
            $definition = $container->getDefinition($id);
            $definition->addMethodCall(
                'setEventDispatcher',
                array(
                    new Reference(self::EVENT_DISPATCHER_SERVICE_ID, ContainerInterface::IGNORE_ON_INVALID_REFERENCE)
                )
            );
        }

    }
}
