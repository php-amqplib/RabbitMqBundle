<?php

namespace OldSound\RabbitMqBundle\DependencyInjection;

use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
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
    /**
     * @var ContainerBuilder
     */
    private $container;

    /**
     * @var Boolean Whether the data collector is enabled
     */
    private $collectorEnabled;

    /**
     * @var array
     */
    private $channelIds = array();

    /**
     * @var array
     */
    private $config;

    /**
     * @var string
     */
    private $defaultConnection;

    /**
     * @var Definition
     */
    private $poolDefinition;

    public function load(array $configs, ContainerBuilder $container)
    {
        $this->container = $container;

        $loader = new XmlFileLoader($this->container, new FileLocator(array(__DIR__.'/../Resources/config')));
        $loader->load('rabbitmq.xml');

        $configuration = new Configuration();
        $this->config = $this->processConfiguration($configuration, $configs);

        $this->collectorEnabled = $this->config['enable_collector'];

        if ($this->container->hasDefinition('old_sound_rabbit_mq.config_pool')) {
            $this->poolDefinition = $this->container->getDefinition('old_sound_rabbit_mq.config_pool');
        }

        $this->loadConnections();
        $this->loadProducers();
        $this->loadExchanges();
        $this->loadQueues();
//        $this->loadAnonConsumers();
//        $this->loadRpcClients();
//        $this->loadRpcServers();

        if ($this->collectorEnabled && $this->channelIds) {
            $channels = array();
            foreach (array_unique($this->channelIds) as $id) {
                $channels[] = new Reference($id);
            }

            $definition = $container->getDefinition('old_sound_rabbit_mq.data_collector');
            $definition->replaceArgument(0, $channels);
        } else {
            $this->container->removeDefinition('old_sound_rabbit_mq.data_collector');
        }
    }

    protected function loadConnections()
    {
        foreach ($this->config['connections'] as $key => $connection) {
            $definition = new Definition('%old_sound_rabbit_mq.connection.class%', array(
                $connection['host'],
                $connection['port'],
                $connection['user'],
                $connection['password'],
                $connection['vhost']
            ));

            $id = sprintf('old_sound_rabbit_mq.connection.%s', $key);
            $this->container->setDefinition($id, $definition);
            $this->poolDefinition->addMethodCall('addConnection', array($key, new Reference($id)));
            if ($connection['is_default']) {
                $this->defaultConnection = $connection;
                $this->poolDefinition->addMethodCall('setDefaultConnection', array(new Reference($id)));
            }
        }
    }

    protected function loadExchanges()
    {
        foreach ($this->config['exchanges'] as $name => $options) {
            $definition = new Definition('%old_sound_rabbit_mq.exchange.class%');
            $definition->addArgument($name);
            $definition->addArgument($options);

            $id = sprintf('old_sound_rabbit_mq.%s_exchange', $name);
            $this->container->setDefinition($id, $definition);

            $this->poolDefinition->addMethodCall('addExchange', array($name, new Reference($id)));
        }
    }

    protected function loadQueues()
    {
        foreach ($this->config['queues'] as $name => $options) {
            $definition = new Definition('%old_sound_rabbit_mq.queue.class%');
            $definition->addArgument($name);
            $definition->addArgument($options);

            $id = sprintf('old_sound_rabbit_mq.%s_queue', $name);
            $this->container->setDefinition($id, $definition);

            $this->poolDefinition->addMethodCall('addQueue', array($name, new Reference($id)));
        }
    }

    protected function loadProducers()
    {
        foreach ($this->config['producers'] as $key => $producer) {
            $definition = new Definition('%old_sound_rabbit_mq.producer.class%');

            // exchange
            $exchangeId = sprintf('old_sound_rabbit_mq.%s_exchange', $producer['exchange']);
            $definition->addMethodCall('setExchange', array(new Reference($exchangeId)));

            if (isset($producer['connection'])) {
                $this->injectConnection($definition, $producer['connection']);
            } elseif (isset($this->defaultConnection)) {
                $this->injectConnection($definition, $this->defaultConnection);
            } else {
                // TODO: throw exception
            }

            if ($this->collectorEnabled) {
                $this->injectLoggedChannel($definition, $key, $producer['connection']);
            }

            $id = sprintf('old_sound_rabbit_mq.%s_producer', $key);
            $this->container->setDefinition($id, $definition);

            $this->poolDefinition->addMethodCall('addProducer', array($key, new Reference($id)));
        }
    }

    protected function loadAnonConsumers()
    {
        foreach ($this->config['anon_consumers'] as $key => $anon) {
            $definition = new Definition('%old_sound_rabbit_mq.anon_consumer.class%');
            $definition
                ->addMethodCall('setExchangeOptions', array($anon['exchange_options']))
                ->addMethodCall('setCallback', array(array(new Reference($anon['callback']), 'execute')))
            ;
            $this->injectConnection($definition, $anon['connection']);
            if ($this->collectorEnabled) {
                $this->injectLoggedChannel($definition, $key, $anon['connection']);
            }

            $this->container->setDefinition(sprintf('old_sound_rabbit_mq.%s_anon', $key), $definition);
        }
    }

    protected function loadRpcClients()
    {
        foreach ($this->config['rpc_clients'] as $key => $client) {
            $definition = new Definition('%old_sound_rabbit_mq.rpc_client.class%');
            $definition->addMethodCall('initClient');
            $this->injectConnection($definition, $client['connection']);
            if ($this->collectorEnabled) {
                $this->injectLoggedChannel($definition, $key, $client['connection']);
            }

            $this->container->setDefinition(sprintf('old_sound_rabbit_mq.%s_rpc', $key), $definition);
        }
    }

    protected function loadRpcServers()
    {
        foreach ($this->config['rpc_servers'] as $key => $server) {
            $definition = new Definition('%old_sound_rabbit_mq.rpc_server.class%');
            $definition
                ->addMethodCall('initServer', array($key))
                ->addMethodCall('setCallback', array(array(new Reference($server['callback']), 'execute')))
            ;
            $this->injectConnection($definition, $server['connection']);
            if ($this->collectorEnabled) {
                $this->injectLoggedChannel($definition, $key, $server['connection']);
            }

            $this->container->setDefinition(sprintf('old_sound_rabbit_mq.%s_server', $key), $definition);
        }
    }

    protected function injectLoggedChannel(Definition $definition, $name, $connectionName)
    {
        $id = sprintf('old_sound_rabbit_mq.channel.%s', $name);
        $channel = new Definition('%old_sound_rabbit_mq.logged.channel.class%');
        $channel
            ->setPublic(false)
            ->addTag('old_sound_rabbit_mq.logged_channel')
        ;
        $this->injectConnection($channel, $connectionName);

        $this->container->setDefinition($id, $channel);

        $this->channelIds[] = $id;
        $definition->addArgument(new Reference($id));
    }

    protected function injectConnection(Definition $definition, $connectionName)
    {
        $definition->addArgument(new Reference(sprintf('old_sound_rabbit_mq.connection.%s', $connectionName)));
    }

    public function getAlias()
    {
        return 'old_sound_rabbit_mq';
    }
}
