<?php

namespace OldSound\RabbitMqBundle\DependencyInjection;

use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Variable;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\Config\FileLocator;

/**
 * OldSoundRabbitMqExtension.
 *
 * @author Alvaro Videla
 * @author Marc Weistroff <marc.weistroff@sensio.com>
 */
class OldSoundRabbitMqExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $this->container = $container;

        $loader = new XmlFileLoader($this->container, new FileLocator(array(__DIR__.'/../Resources/config')));
        $loader->load('rabbitmq.xml');

        $configuration = new Configuration();
        $this->config = $this->processConfiguration($configuration, $configs);
        $this->enable_collector = $this->config['enable_collector'];

        $this->loadConnections();
        $this->loadProducers();
        $this->loadConsumers();
        $this->loadAnonConsumers();
        $this->loadRpcClients();
        $this->loadRpcServers();

        if ($this->enable_collector) {
            $this->loadDataCollector();
        }
    }

    protected function loadConnections()
    {
        foreach ($this->config['connections'] as $key => $connection) {
            $definition = new Definition($this->container->getParameter('old_sound_rabbit_mq.connection.class'),
                                         array(
                                            $connection['host'],
                                            $connection['port'],
                                            $connection['user'],
                                            $connection['password'],
                                            $connection['vhost'])
                                        );

            $this->container->setDefinition(sprintf('old_sound_rabbit_mq.connection.%s', $key), $definition);
        }
    }

    protected function loadProducers()
    {
        foreach ($this->config['producers'] as $key => $producer) {
            $definition = new Definition($this->container->getParameter('old_sound_rabbit_mq.producer.class'));

            $this->injectConnection($definition, $producer['connection']);
            if ($this->enable_collector) {
                $this->injectLoggedChannel($definition, $key, $producer['connection']);
            }
            $definition->addMethodCall('setExchangeOptions', array($producer['exchange_options']));

            $this->container->setDefinition(sprintf('old_sound_rabbit_mq.%s_producer', $key), $definition);
        }
    }

    protected function loadConsumers()
    {
        foreach ($this->config['consumers'] as $key => $consumer) {
            $definition = new Definition($this->container->getParameter('old_sound_rabbit_mq.consumer.class'));

            $this->injectConnection($definition, $consumer['connection']);
            if ($this->enable_collector) {
                $this->injectLoggedChannel($definition, $key, $consumer['connection']);
            }
            $definition->addMethodCall('setExchangeOptions', array($consumer['exchange_options']));
            $definition->addMethodCall('setQueueOptions', array($consumer['queue_options']));
            $definition->addMethodCall('setCallback', array(array(new Reference($consumer['callback']), 'execute')));

            $this->container->setDefinition(sprintf('old_sound_rabbit_mq.%s_consumer', $key), $definition);
        }
    }

    protected function loadAnonConsumers()
    {
        foreach ($this->config['anon_consumers'] as $key => $anon) {
            $definition = new Definition($this->container->getParameter('old_sound_rabbit_mq.anon_consumer.class'));

            $this->injectConnection($definition, $anon['connection']);
            if ($this->enable_collector) {
                $this->injectLoggedChannel($definition, $key, $anon['connection']);
            }
            $definition->addMethodCall('setExchangeOptions', array($anon['exchange_options']));
            $definition->addMethodCall('setCallback', array(array(new Reference($anon['callback']), 'execute')));

            $this->container->setDefinition(sprintf('old_sound_rabbit_mq.%s_anon', $key), $definition);
        }
    }

    protected function loadRpcClients()
    {
        foreach ($this->config['rpc_clients'] as $key => $client) {
            $definition = new Definition($this->container->getParameter('old_sound_rabbit_mq.rpc_client.class'));

            $this->injectConnection($definition, $client['connection']);
            if ($this->enable_collector) {
                $this->injectLoggedChannel($definition, $key, $client['connection']);
            }

            $definition->addMethodCall('initClient');
            $this->container->setDefinition(sprintf('old_sound_rabbit_mq.%s_rpc', $key), $definition);
        }
    }

    protected function loadRpcServers()
    {
        foreach ($this->config['rpc_servers'] as $key => $server) {
            $definition = new Definition($this->container->getParameter('old_sound_rabbit_mq.rpc_server.class'));

            $this->injectConnection($definition, $server['connection']);
            if ($this->enable_collector) {
                $this->injectLoggedChannel($definition, $key, $server['connection']);
            }

            $definition->addMethodCall('initServer', array($key));
            $definition->addMethodCall('setCallback', array(array(new Reference($server['callback']), 'execute')));

            $this->container->setDefinition(sprintf('old_sound_rabbit_mq.%s_server', $key), $definition);
        }
    }

    protected function loadDataCollector()
    {
        $definition = new Definition($this->container->getParameter('old_sound_rabbit_mq.data_collector.class'));
        $channels = array();
        foreach ($this->container->findTaggedServiceIds('old_sound_rabbit_mq.logged_channel') as $id => $params) {
            $channels[] = new Reference($id);
        }

        $this->container->setDefinition('data_collector.rabbit_mq', $definition)
                        ->addArgument($channels)
                        ->addTag('data_collector', array(
                            'template' => 'OldSoundRabbitMqBundle:Collector:collector.html.twig',
                            'id'       => 'rabbit_mq',
                        ));
    }

    protected function injectLoggedChannel(Definition $definition, $name, $connectionName)
    {
        $channel = new Definition($this->container->getParameter('old_sound_rabbit_mq.logged.channel.class'));
        $this->injectConnection($channel, $connectionName);
        $channel->setPublic(false);
        $channel->addTag('old_sound_rabbit_mq.logged_channel');

        $id = sprintf('old_sound_rabbit_mq.channel.%s', $name);
        $this->container->setDefinition($id, $channel);

        $definition->addArgument(new Reference($id));
    }

    protected function injectConnection(Definition $def, $connectionName)
    {
        $def->addArgument(new Reference(sprintf('old_sound_rabbit_mq.connection.%s', $connectionName)));
    }

    public function getAlias()
    {
        return 'old_sound_rabbit_mq';
    }
}
