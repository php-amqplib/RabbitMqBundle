<?php

namespace OldSound\RabbitMqBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class LoadRpcClientCompilerPass extends BaseCompilerPass
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        parent::process($container);

        foreach ($this->config['rpc_clients'] as $key => $client) {
            $definition = new Definition($container->getParameter('old_sound_rabbit_mq.rpc_client.class'));

            $this->injectConnection($definition, $client['connection']);
            if ($this->enableCollector) {
                $this->injectLoggedChannel($definition, $key, $client['connection'], $container);
            }

            $definition->addMethodCall('initClient');
            $container->setDefinition(sprintf('old_sound_rabbit_mq.%s_rpc', $key), $definition);
        }
    }
}