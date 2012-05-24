<?php

namespace OldSound\RabbitMqBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class LoadConsumerCompilerPass extends BaseCompilerPass
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        parent::process($container);

        foreach ($this->config['consumers'] as $key => $consumer) {
            $definition = new Definition($container->getParameter('old_sound_rabbit_mq.consumer.class'));

            $this->injectConnection($definition, $consumer['connection']);
            if ($this->enableCollector) {
                $this->injectLoggedChannel($definition, $key, $consumer['connection'], $container);
            }
            $definition->addMethodCall('setExchangeOptions', array($consumer['exchange_options']));
            $definition->addMethodCall('setQueueOptions', array($consumer['queue_options']));
            $definition->addMethodCall('setCallback', array(array(new Reference($consumer['callback']), 'execute')));

            $container->setDefinition(sprintf('old_sound_rabbit_mq.%s_consumer', $key), $definition);
        }
    }
}