<?php

namespace OldSound\RabbitMqBundle\DependencyInjection;

use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
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
        $loader = new YamlFileLoader($container, new FileLocator(array(__DIR__.'/../Resources/config')));
        $loader->load('rabbitmq.yml');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $this->loadConnections($config['connections'], $container);
        $this->loadProducers($config['producers'], $container);
        $this->loadConsumers($config['consumers'], $container);
        $this->loadAnonConsumers($config['anon_consumers'], $container);
        $this->loadRpcClients($config['rpc_clients'], $container);
        $this->loadRpcServers($config['rpc_servers'], $container);
    }

    protected function loadConnections(array $connections, ContainerBuilder $container)
    {
        foreach ($connections as $key => $connection) {
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

    protected function loadProducers(array $producers, ContainerBuilder $container)
    {
        foreach ($producers as $key => $producer) {
            $definition = new Definition($container->getParameter('old_sound_rabbit_mq.producer.class'));

            $this->injectConnection($definition, $producer['connection']);
            $definition->addMethodCall('setExchangeOptions', array($producer['exchange_options']));
            //TODO add configuration option that allows to not do this all the time.
            $definition->addMethodCall('exchangeDeclare');

            $container->setDefinition(sprintf('old_sound_rabbit_mq.%s_producer', $key), $definition);
        }
    }

    protected function loadConsumers(array $consumers, ContainerBuilder $container)
    {
        foreach ($consumers as $key => $consumer) {
            $definition = new Definition($container->getParameter('old_sound_rabbit_mq.consumer.class'));

            $this->injectConnection($definition, $consumer['connection']);
            $definition->addMethodCall('setExchangeOptions', array($consumer['exchange_options']));
            $definition->addMethodCall('setQueueOptions', array($consumer['queue_options']));
            $definition->addMethodCall('setCallback', array(array(new Reference($consumer['callback']), 'execute')));

            $container->setDefinition(sprintf('old_sound_rabbit_mq.%s_consumer', $key), $definition);
        }
    }

    protected function loadAnonConsumers(array $anons, ContainerBuilder $container)
    {
        foreach ($anons as $key => $anon) {
            $definition = new Definition($container->getParameter('old_sound_rabbit_mq.anon_consumer.class'));

            $this->injectConnection($definition, $anon['connection']);
            $definition->addMethodCall('setExchangeOptions', array($consumer['exchange_options']));
            $definition->addMethodCall('setCallback', array(array(new Reference($consumer['callback']), 'execute')));

            $container->setDefinition(sprintf('old_sound_rabbit_mq.%s_anon', $key), $definition);
        }
    }

    protected function loadRpcClients(array $clients, ContainerBuilder $container)
    {
        foreach ($clients as $key => $client) {
            $definition = new Definition($container->getParameter('old_sound_rabbit_mq.rpc_client.class'));

            $this->injectConnection($definition, $client['connection']);
            $definition->addMethodCall('initClient');
            $container->setDefinition(sprintf('old_sound_rabbit_mq.%s_rpc', $key), $definition);
        }
    }

    protected function loadRpcServers(array $servers, ContainerBuilder $container)
    {
        foreach ($servers as $key => $server) {
            $definition = new Definition($container->getParameter('old_sound_rabbit_mq.rpc_server.class'));

            $this->injectConnection($definition, $server['connection']);

            $definition->addMethodCall('initServer', array($server['alias']));
            $definition->addMethodCall('setCallback', array(array(new Reference($server['callback']), 'execute')));

            $container->setDefinition(sprintf('old_sound_rabbit_mq.%s_server', $key), $definition);
        }
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

