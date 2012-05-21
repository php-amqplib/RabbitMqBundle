<?php

namespace OldSound\RabbitMqBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

/**
 * @author Timothée Barray <tim@amicalement-web.net>
 */
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
            if ($this->enable_collector) {
                $this->injectLoggedChannel($definition, $key, $client['connection'], $container);
            }

            $definition->addMethodCall('initClient');
            $container->setDefinition(sprintf('old_sound_rabbit_mq.%s_rpc', $key), $definition);
        }
    }
}