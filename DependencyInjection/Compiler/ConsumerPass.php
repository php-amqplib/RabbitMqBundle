<?php

namespace OldSound\RabbitMqBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Definition;

/**
 * Adds tagged old_sound.consumer
 *
 * @author Benjamin Dulau <benjamin.dulau@anonymation.com>
 */
class ConsumerPass implements CompilerPassInterface
{

    /**
     * @var ContainerBuilder
     */
    private $container;

    /**
     * @var Definition
     */
    private $configPoolDefinition;


    public function process(ContainerBuilder $container)
    {
        $this->container = $container;

        if (false === $container->hasDefinition('old_sound_rabbit_mq.config_pool')) {
            return;
        }

        $this->configPoolDefinition = $container->getDefinition('old_sound_rabbit_mq.config_pool');

        foreach ($container->findTaggedServiceIds('old_sound_rabbit_mq.consumer') as $id => $consumers) {
            foreach ($consumers as $consumer) {
                $connection = isset($consumer['connection']) ? $consumer['connection'] : null;

                // Exchange
                // TODO: Validate exchange (required)
                $exchangeName = isset($consumer['exchange']) ? $consumer['exchange'] : null;
                $exchangeId = sprintf('old_sound_rabbit_mq.%s_exchange', $exchangeName);
                if (!$container->hasDefinition($exchangeId)) {
                    $this->createDefaultExchange($exchangeName, $exchangeId);
                }

                // Queue
                // TODO: Validate queue (required)
                $queueName = isset($consumer['queue']) ? $consumer['queue'] : null;
                $queueId = sprintf('old_sound_rabbit_mq.%s_queue', $queueName);
                if (!$container->hasDefinition($queueId)) {
                    $this->createDefaultQueue($queueName, $queueId);
                }

                $consumerDefinition = new Definition('%old_sound_rabbit_mq.consumer.class%');
                $consumerDefinition
                    ->addMethodCall('setExchange', array(new Reference($exchangeId)))
                    ->addMethodCall('setQueue', array(new Reference($queueId)))
                    ->addMethodCall('setCallback', array(array(new Reference($id), 'execute')))
                ;

                if (!empty($connection)) {
                    $consumerDefinition->addArgument(
                        new Reference(sprintf('old_sound_rabbit_mq.connection.%s', $connection))
                    );
                }

                // TODO: logged channel

                $consumerId = sprintf('old_sound_rabbit_mq.%s_consumer', $consumer['id']);
                $container->setDefinition($consumerId, $consumerDefinition);
                $this->configPoolDefinition->addMethodCall('addConsumer', array($consumer['id'], new Reference($consumerId)));
            }
        }
    }

    private function createDefaultExchange($name, $id)
    {
        $definition = new Definition('%old_sound_rabbit_mq.exchange.class%');
        $definition->addArgument($name);
        $this->container->setDefinition($id, $definition);

        $this->configPoolDefinition->addMethodCall('addExchange', array($name, new Reference($id)));
    }

    private function createDefaultQueue($name, $id)
    {
        $definition = new Definition('%old_sound_rabbit_mq.queue.class%');
        $definition->addArgument($name);
        $this->container->setDefinition($id, $definition);

        $this->configPoolDefinition->addMethodCall('addQueue', array($name, new Reference($id)));
    }
}
