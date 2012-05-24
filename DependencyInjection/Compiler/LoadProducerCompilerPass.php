<?php

namespace OldSound\RabbitMqBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class LoadProducerCompilerPass extends BaseCompilerPass
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        parent::process($container);

        foreach ($this->config['producers'] as $key => $producer) {
            $definition = new Definition($container->getParameter('old_sound_rabbit_mq.producer.class'));

            $this->injectConnection($definition, $producer['connection']);
            if ($this->enableCollector) {
                $this->injectLoggedChannel($definition, $key, $producer['connection'], $container);
            }
            $definition->addMethodCall('setExchangeOptions', array($producer['exchange_options']));

            $container->setDefinition(sprintf('old_sound_rabbit_mq.%s_producer', $key), $definition);
        }
    }
}