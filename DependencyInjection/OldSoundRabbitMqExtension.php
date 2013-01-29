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

    private $channelIds = array();

    private $config = array();

    public function load(array $configs, ContainerBuilder $container)
    {
        $this->container = $container;

        $loader = new XmlFileLoader($this->container, new FileLocator(array(__DIR__.'/../Resources/config')));
        $loader->load('rabbitmq.xml');

        $configuration = new Configuration();
        $this->config = $this->processConfiguration($configuration, $configs);

        $this->collectorEnabled = $this->config['enable_collector'];

        $this->loadConnections();
        $this->loadProducers();
        $this->loadConsumers();
        $this->loadAnonConsumers();
        $this->loadRpcClients();
        $this->loadRpcServers();

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

            $this->container->setDefinition(sprintf('old_sound_rabbit_mq.connection.%s', $key), $definition);
        }
    }

    protected function loadProducers()
    {
        if ($this->config['sandbox'] == false) {
            foreach ($this->config['producers'] as $key => $producer) {
                $definition = new Definition('%old_sound_rabbit_mq.producer.class%');
                $definition->addMethodCall('setExchangeOptions', array($producer['exchange_options']));
                //this producer doesn't define a queue
                if (!isset($producer['queue_options'])) {
                    $producer['queue_options']['name'] = null;
                }
                $definition->addMethodCall('setQueueOptions', array($producer['queue_options']));
                $this->injectConnection($definition, $producer['connection']);
                if ($this->collectorEnabled) {
                    $this->injectLoggedChannel($definition, $key, $producer['connection']);
                }

                $this->container->setDefinition(sprintf('old_sound_rabbit_mq.%s_producer', $key), $definition);
            }
        } else {
            foreach ($this->config['producers'] as $key => $producer) {
                $definition = new Definition('%old_sound_rabbit_mq.fallback.class%');
                $this->container->setDefinition(sprintf('old_sound_rabbit_mq.%s_producer', $key), $definition);
            }
        }
    }

    protected function loadConsumers()
    {
        foreach ($this->config['consumers'] as $key => $consumer) {
            $definition = new Definition('%old_sound_rabbit_mq.consumer.class%');
            $definition
                ->addMethodCall('setExchangeOptions', array($consumer['exchange_options']))
                ->addMethodCall('setQueueOptions', array($consumer['queue_options']))
                ->addMethodCall('setCallback', array(array(new Reference($consumer['callback']), 'execute')));

            if (array_key_exists('qos_options', $consumer)) {
                $definition->addMethodCall('setQosOptions', array(
                    $consumer['qos_options']['prefetch_size'],
                    $consumer['qos_options']['prefetch_count'],
                    $consumer['qos_options']['global']
                ));
            }

            $this->injectConnection($definition, $consumer['connection']);
            if ($this->collectorEnabled) {
                $this->injectLoggedChannel($definition, $key, $consumer['connection']);
            }

            $this->container->setDefinition(sprintf('old_sound_rabbit_mq.%s_consumer', $key), $definition);
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
