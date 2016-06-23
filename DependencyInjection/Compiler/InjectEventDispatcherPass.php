<?php

namespace OldSound\RabbitMqBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class InjectEventDispatcherPass
 *
 * @package OldSound\RabbitMqBundle\DependencyInjection\Compiler
 */
class InjectEventDispatcherPass implements CompilerPassInterface
{
    /**
     * @inheritDoc
     */
    public function process(ContainerBuilder $container)
    {
        try {
            $eventDispatcherDefinition = $container->findDefinition('event_dispatcher');
            $taggedConsumers = $container->findTaggedServiceIds('old_sound_rabbit_mq.base_amqp');

            foreach ($taggedConsumers as $id => $tag) {
                $definition = $container->getDefinition($id);
                $definition->addMethodCall(
                    'setEventDispatcher',
                    [$eventDispatcherDefinition]
                );
            }

        } catch (\Exception $e) {
            // DO Nothing
        }
    }
}
