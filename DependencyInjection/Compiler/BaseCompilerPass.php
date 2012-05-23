<?php

namespace OldSound\RabbitMqBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @author Timothée Barray <tim@amicalement-web.net>
 */
abstract class BaseCompilerPass implements CompilerPassInterface
{
    protected 
        $config = array(),
        $enableCollector = false;

    public function process(ContainerBuilder $container)
    {
        $config = $container->getExtensionConfig('old_sound_rabbit_mq');

        $this->config = array_shift($config);

        if (isset($this->config['enable_collector']) && $this->config['enable_collector']) {
            $this->enableCollector = true;
        }

        if (!isset($this->config['rpc_clients'])) {
            $this->config['rpc_clients'] = array();
        }

        if (!isset($this->config['rpc_servers'])) {
            $this->config['rpc_servers'] = array();
        }

        if (!isset($this->config['anon_consumers'])) {
            $this->config['anon_consumers'] = array();
        }

        if (!isset($this->config['consumers'])) {
            $this->config['consumers'] = array();
        }

        if (!isset($this->config['producers'])) {
            $this->config['producers'] = array();
        }
    }

    protected function injectConnection(Definition $def, $connectionName)
    {
        $def->addArgument(new Reference(sprintf('old_sound_rabbit_mq.connection.%s', $connectionName)));
    }

    protected function injectLoggedChannel(Definition $definition, $name, $connectionName, $container)
    {
        $channel = new Definition($container->getParameter('old_sound_rabbit_mq.logged.channel.class'));
        $this->injectConnection($channel, $connectionName);
        $channel->setPublic(false);
        $channel->addTag('old_sound_rabbit_mq.logged_channel');

        $id = sprintf('old_sound_rabbit_mq.channel.%s', $name);
        $container->setDefinition($id, $channel);

        $definition->addArgument(new Reference($id));
    }
}