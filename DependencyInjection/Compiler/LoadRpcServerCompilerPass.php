<?php

namespace OldSound\RabbitMqBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @author Timothée Barray <tim@amicalement-web.net>
 */
class LoadRpcServerCompilerPass extends BaseCompilerPass
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        parent::process($container);

        foreach ($this->config['rpc_servers'] as $key => $server) {
            $definition = new Definition($container->getParameter('old_sound_rabbit_mq.rpc_server.class'));

            $this->injectConnection($definition, $server['connection']);
            if ($this->enableCollector) {
                $this->injectLoggedChannel($definition, $key, $server['connection'], $container);
            }

            $definition->addMethodCall('initServer', array($key));
            $definition->addMethodCall('setCallback', array(array(new Reference($server['callback']), 'execute')));

            $container->setDefinition(sprintf('old_sound_rabbit_mq.%s_server', $key), $definition);
        }
    }
}