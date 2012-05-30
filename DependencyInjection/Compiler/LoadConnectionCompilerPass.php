<?php

namespace OldSound\RabbitMqBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class LoadConnectionCompilerPass extends BaseCompilerPass
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        parent::process($container);

        if (!isset($this->config['connections'])) {
            return;
        }

        foreach ($this->config['connections'] as $key => $connection) {
            $definition = new Definition($container->getParameter('old_sound_rabbit_mq.connection.class'),
                                         array(
                                            $connection['host'],
                                            $connection['port'],
                                            $connection['user'],
                                            $connection['password'],
                                            $connection['vhost'])
                                        );

            $container->setDefinition(sprintf('old_sound_rabbit_mq.connection.%s', $key), $definition);
        }
    }
}