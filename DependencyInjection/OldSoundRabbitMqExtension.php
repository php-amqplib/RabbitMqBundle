<?php

namespace OldSound\RabbitMqBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Extension\Extension;
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

        $loader = new XmlFileLoader($this->container, new FileLocator(array(__DIR__ . '/../Resources/config')));
        $loader->load('rabbitmq.xml');

        $configuration = new Configuration($this->getAlias());
        $this->config = $this->processConfiguration($configuration, $configs);

        $this->collectorEnabled = $this->config['enable_collector'];

        $this->loadConnections();
        $this->loadBindings();
        $this->loadProducers();
        $this->loadConsumers();
        $this->loadMultipleConsumers();
        $this->loadDynamicConsumers();
        $this->loadBatchConsumers();
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
            $connectionSuffix = $connection['use_socket'] ? 'socket_connection.class' : 'connection.class';
            $classParam =
                $connection['lazy']
                    ? '%old_sound_rabbit_mq.lazy.'.$connectionSuffix.'%'
                    : '%old_sound_rabbit_mq.'.$connectionSuffix.'%';

            $definition = new Definition('%old_sound_rabbit_mq.connection_factory.class%', array(
                $classParam, $connection,
            ));
            if (isset($connection['connection_parameters_provider'])) {
                $definition->addArgument(new Reference($connection['connection_parameters_provider']));
                unset($connection['connection_parameters_provider']);
            }
            $definition->setPublic(false);
            $factoryName = sprintf('old_sound_rabbit_mq.connection_factory.%s', $key);
            $this->container->setDefinition($factoryName, $definition);

            $definition = new Definition($classParam);
            if (method_exists($definition, 'setFactory')) {
                // to be inlined in services.xml when dependency on Symfony DependencyInjection is bumped to 2.6
                $definition->setFactory(array(new Reference($factoryName), 'createConnection'));
            } else {
                // to be removed when dependency on Symfony DependencyInjection is bumped to 2.6
                $definition->setFactoryService($factoryName);
                $definition->setFactoryMethod('createConnection');
            }
            $definition->addTag('old_sound_rabbit_mq.connection');

            $this->container->setDefinition(sprintf('old_sound_rabbit_mq.connection.%s', $key), $definition);
        }
    }

    protected function loadBindings()
    {
        if ($this->config['sandbox']) {
            return;
        }
        foreach ($this->config['bindings'] as $binding) {
            ksort($binding);
            $definition = new Definition($binding['class']);
            $definition->addTag('old_sound_rabbit_mq.binding');
            $definition->addMethodCall('setArguments', array($binding['arguments']));
            $definition->addMethodCall('setDestination', array($binding['destination']));
            $definition->addMethodCall('setDestinationIsExchange', array($binding['destination_is_exchange']));
            $definition->addMethodCall('setExchange', array($binding['exchange']));
            $definition->addMethodCall('isNowait', array($binding['nowait']));
            $definition->addMethodCall('setRoutingKey', array($binding['routing_key']));
            $this->injectConnection($definition, $binding['connection']);
            $key = md5(json_encode($binding));
            if ($this->collectorEnabled) {
                // in the context of a binding, I don't thing logged channels are needed?
                $this->injectLoggedChannel($definition, $key, $binding['connection']);
            }
            $this->container->setDefinition(sprintf('old_sound_rabbit_mq.binding.%s', $key), $definition);
        }
    }

    protected function loadProducers()
    {
        if ($this->config['sandbox'] == false) {
            foreach ($this->config['producers'] as $key => $producer) {
                $definition = new Definition($producer['class']);
                $definition->addTag('old_sound_rabbit_mq.base_amqp');
                $definition->addTag('old_sound_rabbit_mq.producer');
                //this producer doesn't define an exchange -> using AMQP Default
                if (!isset($producer['exchange_options'])) {
                    $producer['exchange_options'] = $this->getDefaultExchangeOptions();
                }
                $definition->addMethodCall('setExchangeOptions', array($this->normalizeArgumentKeys($producer['exchange_options'])));
                //this producer doesn't define a queue -> using AMQP Default
                if (!isset($producer['queue_options'])) {
                    $producer['queue_options'] = $this->getDefaultQueueOptions();
                }
                $definition->addMethodCall('setQueueOptions', array($producer['queue_options']));
                $this->injectConnection($definition, $producer['connection']);
                if ($this->collectorEnabled) {
                    $this->injectLoggedChannel($definition, $key, $producer['connection']);
                }
                if (!$producer['auto_setup_fabric']) {
                    $definition->addMethodCall('disableAutoSetupFabric');
                }

                if ($producer['enable_logger']) {
                    $this->injectLogger($definition);
                }

                $producerServiceName = sprintf('old_sound_rabbit_mq.%s_producer', $key);

                $this->container->setDefinition($producerServiceName, $definition);
                if (null !== $producer['service_alias']) {
                    $this->container->setAlias($producer['service_alias'], $producerServiceName);
                }
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
            $definition->addTag('old_sound_rabbit_mq.base_amqp');
            $definition->addTag('old_sound_rabbit_mq.consumer');
            //this consumer doesn't define an exchange -> using AMQP Default
            if (!isset($consumer['exchange_options'])) {
                $consumer['exchange_options'] = $this->getDefaultExchangeOptions();
            }
            $definition->addMethodCall('setExchangeOptions', array($this->normalizeArgumentKeys($consumer['exchange_options'])));
            //this consumer doesn't define a queue -> using AMQP Default
            if (!isset($consumer['queue_options'])) {
                $consumer['queue_options'] = $this->getDefaultQueueOptions();
            }
            $definition->addMethodCall('setQueueOptions', array($this->normalizeArgumentKeys($consumer['queue_options'])));
            $definition->addMethodCall('setCallback', array(array(new Reference($consumer['callback']), 'execute')));

            if (array_key_exists('qos_options', $consumer)) {
                $definition->addMethodCall('setQosOptions', array(
                    $consumer['qos_options']['prefetch_size'],
                    $consumer['qos_options']['prefetch_count'],
                    $consumer['qos_options']['global']
                ));
            }

            if (isset($consumer['idle_timeout'])) {
                $definition->addMethodCall('setIdleTimeout', array($consumer['idle_timeout']));
            }
            if (isset($consumer['idle_timeout_exit_code'])) {
                $definition->addMethodCall('setIdleTimeoutExitCode', array($consumer['idle_timeout_exit_code']));
            }
            if (isset($consumer['graceful_max_execution'])) {
                $definition->addMethodCall(
                    'setGracefulMaxExecutionDateTimeFromSecondsInTheFuture',
                    array($consumer['graceful_max_execution']['timeout'])
                );
                $definition->addMethodCall(
                    'setGracefulMaxExecutionTimeoutExitCode',
                    array($consumer['graceful_max_execution']['exit_code'])
                );
            }
            if (!$consumer['auto_setup_fabric']) {
                $definition->addMethodCall('disableAutoSetupFabric');
            }

            $this->injectConnection($definition, $consumer['connection']);
            if ($this->collectorEnabled) {
                $this->injectLoggedChannel($definition, $key, $consumer['connection']);
            }

            if ($consumer['enable_logger']) {
                $this->injectLogger($definition);
            }

            $name = sprintf('old_sound_rabbit_mq.%s_consumer', $key);
            $this->container->setDefinition($name, $definition);
            $this->addDequeuerAwareCall($consumer['callback'], $name);
        }
    }

    protected function loadMultipleConsumers()
    {
        foreach ($this->config['multiple_consumers'] as $key => $consumer) {
            $queues = array();
            $callbacks = array();

            if (empty($consumer['queues']) && empty($consumer['queues_provider'])) {
                throw new InvalidConfigurationException(
                    "Error on loading $key multiple consumer. " .
                    "Either 'queues' or 'queues_provider' parameters should be defined."
                );
            }

            foreach ($consumer['queues'] as $queueName => $queueOptions) {
                $queues[$queueOptions['name']] = $queueOptions;
                $queues[$queueOptions['name']]['callback'] = array(new Reference($queueOptions['callback']), 'execute');
                $callbacks[] = new Reference($queueOptions['callback']);
            }

            $definition = new Definition('%old_sound_rabbit_mq.multi_consumer.class%');
            $definition
                ->addTag('old_sound_rabbit_mq.base_amqp')
                ->addTag('old_sound_rabbit_mq.multi_consumer')
                ->addMethodCall('setExchangeOptions', array($this->normalizeArgumentKeys($consumer['exchange_options'])))
                ->addMethodCall('setQueues', array($this->normalizeArgumentKeys($queues)));

            if ($consumer['queues_provider']) {
                $definition->addMethodCall(
                    'setQueuesProvider',
                    array(new Reference($consumer['queues_provider']))
                );
            }

            if (array_key_exists('qos_options', $consumer)) {
                $definition->addMethodCall('setQosOptions', array(
                    $consumer['qos_options']['prefetch_size'],
                    $consumer['qos_options']['prefetch_count'],
                    $consumer['qos_options']['global']
                ));
            }

            if (isset($consumer['idle_timeout'])) {
                $definition->addMethodCall('setIdleTimeout', array($consumer['idle_timeout']));
            }
            if (isset($consumer['idle_timeout_exit_code'])) {
                $definition->addMethodCall('setIdleTimeoutExitCode', array($consumer['idle_timeout_exit_code']));
            }
            if (!$consumer['auto_setup_fabric']) {
                $definition->addMethodCall('disableAutoSetupFabric');
            }

            $this->injectConnection($definition, $consumer['connection']);
            if ($this->collectorEnabled) {
                $this->injectLoggedChannel($definition, $key, $consumer['connection']);
            }

            if ($consumer['enable_logger']) {
                $this->injectLogger($definition);
            }

            $name = sprintf('old_sound_rabbit_mq.%s_multiple', $key);
            $this->container->setDefinition($name, $definition);
            if ($consumer['queues_provider']) {
                $this->addDequeuerAwareCall($consumer['queues_provider'], $name);
            }
            foreach ($callbacks as $callback) {
                $this->addDequeuerAwareCall($callback, $name);
            }
        }
    }

    protected function loadDynamicConsumers()
    {
        foreach ($this->config['dynamic_consumers'] as $key => $consumer) {

            if (empty($consumer['queue_options_provider'])) {
                throw new InvalidConfigurationException(
                    "Error on loading $key dynamic consumer. " .
                    "'queue_provider' parameter should be defined."
                );
            }

            $definition = new Definition('%old_sound_rabbit_mq.dynamic_consumer.class%');
            $definition
                ->addTag('old_sound_rabbit_mq.base_amqp')
                ->addTag('old_sound_rabbit_mq.consumer')
                ->addTag('old_sound_rabbit_mq.dynamic_consumer')
                ->addMethodCall('setExchangeOptions', array($this->normalizeArgumentKeys($consumer['exchange_options'])))
                ->addMethodCall('setCallback', array(array(new Reference($consumer['callback']), 'execute')));

            if (array_key_exists('qos_options', $consumer)) {
                $definition->addMethodCall('setQosOptions', array(
                    $consumer['qos_options']['prefetch_size'],
                    $consumer['qos_options']['prefetch_count'],
                    $consumer['qos_options']['global']
                ));
            }

            $definition->addMethodCall(
                'setQueueOptionsProvider',
                array(new Reference($consumer['queue_options_provider']))
            );

            if (isset($consumer['idle_timeout'])) {
                $definition->addMethodCall('setIdleTimeout', array($consumer['idle_timeout']));
            }
            if (isset($consumer['idle_timeout_exit_code'])) {
                $definition->addMethodCall('setIdleTimeoutExitCode', array($consumer['idle_timeout_exit_code']));
            }
            if (!$consumer['auto_setup_fabric']) {
                $definition->addMethodCall('disableAutoSetupFabric');
            }

            $this->injectConnection($definition, $consumer['connection']);
            if ($this->collectorEnabled) {
                $this->injectLoggedChannel($definition, $key, $consumer['connection']);
            }

            if ($consumer['enable_logger']) {
                $this->injectLogger($definition);
            }

            $name = sprintf('old_sound_rabbit_mq.%s_dynamic', $key);
            $this->container->setDefinition($name, $definition);
            $this->addDequeuerAwareCall($consumer['callback'], $name);
            $this->addDequeuerAwareCall($consumer['queue_options_provider'], $name);
        }
    }

    protected function loadBatchConsumers()
    {
        foreach ($this->config['batch_consumers'] as $key => $consumer) {
            $definition = new Definition('%old_sound_rabbit_mq.batch_consumer.class%');

            if (!isset($consumer['exchange_options'])) {
                $consumer['exchange_options'] = $this->getDefaultExchangeOptions();
            }

            $definition
                ->addTag('old_sound_rabbit_mq.base_amqp')
                ->addTag('old_sound_rabbit_mq.batch_consumer')
                ->addMethodCall('setTimeoutWait', array($consumer['timeout_wait']))
                ->addMethodCall('setPrefetchCount', array($consumer['qos_options']['prefetch_count']))
                ->addMethodCall('setCallback', array(array(new Reference($consumer['callback']), 'batchExecute')))
                ->addMethodCall('setExchangeOptions', array($this->normalizeArgumentKeys($consumer['exchange_options'])))
                ->addMethodCall('setQueueOptions', array($this->normalizeArgumentKeys($consumer['queue_options'])))
                ->addMethodCall('setQosOptions', array(
                    $consumer['qos_options']['prefetch_size'],
                    $consumer['qos_options']['prefetch_count'],
                    $consumer['qos_options']['global']
                ))
            ;

            if (isset($consumer['idle_timeout_exit_code'])) {
                $definition->addMethodCall('setIdleTimeoutExitCode', array($consumer['idle_timeout_exit_code']));
            }

            if (isset($consumer['idle_timeout'])) {
                $definition->addMethodCall('setIdleTimeout', array($consumer['idle_timeout']));
            }

            if (!$consumer['auto_setup_fabric']) {
                $definition->addMethodCall('disableAutoSetupFabric');
            }

            $this->injectConnection($definition, $consumer['connection']);
            if ($this->collectorEnabled) {
                $this->injectLoggedChannel($definition, $key, $consumer['connection']);
            }

            if ($consumer['enable_logger']) {
                $this->injectLogger($definition);
            }

            $this->container->setDefinition(sprintf('old_sound_rabbit_mq.%s_batch', $key), $definition);
        }
    }

    protected function loadAnonConsumers()
    {
        foreach ($this->config['anon_consumers'] as $key => $anon) {
            $definition = new Definition('%old_sound_rabbit_mq.anon_consumer.class%');
            $definition
                ->addTag('old_sound_rabbit_mq.base_amqp')
                ->addTag('old_sound_rabbit_mq.anon_consumer')
                ->addMethodCall('setExchangeOptions', array($this->normalizeArgumentKeys($anon['exchange_options'])))
                ->addMethodCall('setCallback', array(array(new Reference($anon['callback']), 'execute')));
            $this->injectConnection($definition, $anon['connection']);
            if ($this->collectorEnabled) {
                $this->injectLoggedChannel($definition, $key, $anon['connection']);
            }

            $name = sprintf('old_sound_rabbit_mq.%s_anon', $key);
            $this->container->setDefinition($name, $definition);
            $this->addDequeuerAwareCall($anon['callback'], $name);
        }
    }

    /**
     * Symfony 2 converts '-' to '_' when defined in the configuration. This leads to problems when using x-ha-policy
     * parameter. So we revert the change for right configurations.
     *
     * @param array $config
     *
     * @return array
     */
    private function normalizeArgumentKeys(array $config)
    {
        if (isset($config['arguments'])) {
            $arguments = $config['arguments'];
            // support for old configuration
            if (is_string($arguments)) {
                $arguments = $this->argumentsStringAsArray($arguments);
            }

            $newArguments = array();
            foreach ($arguments as $key => $value) {
                if (strstr($key, '_')) {
                    $key = str_replace('_', '-', $key);
                }
                $newArguments[$key] = $value;
            }
            $config['arguments'] = $newArguments;
        }
        return $config;
    }

    /**
     * Support for arguments provided as string. Support for old configuration files.
     *
     * @deprecated
     * @param string $arguments
     * @return array
     */
    private function argumentsStringAsArray($arguments)
    {
        $argumentsArray = array();

        $argumentPairs = explode(',', $arguments);
        foreach ($argumentPairs as $argument) {
            $argumentPair = explode(':', $argument);
            $type = 'S';
            if (isset($argumentPair[2])) {
                $type = $argumentPair[2];
            }
            $argumentsArray[$argumentPair[0]] = array($type, $argumentPair[1]);
        }

        return $argumentsArray;
    }

    protected function loadRpcClients()
    {
        foreach ($this->config['rpc_clients'] as $key => $client) {
            $definition = new Definition('%old_sound_rabbit_mq.rpc_client.class%');
            $definition->setLazy($client['lazy']);
            $definition
                ->addTag('old_sound_rabbit_mq.rpc_client')
                ->addMethodCall('initClient', array($client['expect_serialized_response']));
            $this->injectConnection($definition, $client['connection']);
            if ($this->collectorEnabled) {
                $this->injectLoggedChannel($definition, $key, $client['connection']);
            }
            if (array_key_exists('unserializer', $client)) {
                $definition->addMethodCall('setUnserializer', array($client['unserializer']));
            }
            if (array_key_exists('direct_reply_to', $client)) {
                $definition->addMethodCall('setDirectReplyTo', array($client['direct_reply_to']));
            }

            $this->container->setDefinition(sprintf('old_sound_rabbit_mq.%s_rpc', $key), $definition);
        }
    }

    protected function loadRpcServers()
    {
        foreach ($this->config['rpc_servers'] as $key => $server) {
            $definition = new Definition('%old_sound_rabbit_mq.rpc_server.class%');
            $definition
                ->addTag('old_sound_rabbit_mq.base_amqp')
                ->addTag('old_sound_rabbit_mq.rpc_server')
                ->addMethodCall('initServer', array($key))
                ->addMethodCall('setCallback', array(array(new Reference($server['callback']), 'execute')));
            $this->injectConnection($definition, $server['connection']);
            if ($this->collectorEnabled) {
                $this->injectLoggedChannel($definition, $key, $server['connection']);
            }
            if (array_key_exists('qos_options', $server)) {
                $definition->addMethodCall('setQosOptions', array(
                    $server['qos_options']['prefetch_size'],
                    $server['qos_options']['prefetch_count'],
                    $server['qos_options']['global']
                ));
            }
            if (array_key_exists('exchange_options', $server)) {
                $definition->addMethodCall('setExchangeOptions', array($server['exchange_options']));
            }
            if (array_key_exists('queue_options', $server)) {
                $definition->addMethodCall('setQueueOptions', array($server['queue_options']));
            }
            if (array_key_exists('serializer', $server)) {
                $definition->addMethodCall('setSerializer', array($server['serializer']));
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
            ->addTag('old_sound_rabbit_mq.logged_channel');
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

    /**
     * Add proper dequeuer aware call
     *
     * @param string $callback
     * @param string $name
     */
    protected function addDequeuerAwareCall($callback, $name)
    {
        if (!$this->container->has($callback)) {
            return;
        }

        $callbackDefinition = $this->container->findDefinition($callback);
        $refClass = new \ReflectionClass($callbackDefinition->getClass());
        if ($refClass->implementsInterface('OldSound\RabbitMqBundle\RabbitMq\DequeuerAwareInterface')) {
            $callbackDefinition->addMethodCall('setDequeuer', array(new Reference($name)));
        }
    }

    private function injectLogger(Definition $definition)
    {
        $definition->addTag('monolog.logger', array(
            'channel' => 'phpamqplib'
        ));
        $definition->addMethodCall('setLogger', array(new Reference('logger', ContainerInterface::IGNORE_ON_INVALID_REFERENCE)));
    }

    /**
     * Get default AMQP exchange options
     *
     * @return array
     */
    protected function getDefaultExchangeOptions()
    {
        return array(
            'name' => '',
            'type' => 'direct',
            'passive' => true,
            'declare' => false
        );
    }

    /**
     * Get default AMQP queue options
     *
     * @return array
     */
    protected function getDefaultQueueOptions()
    {
        return array(
            'name' => '',
            'declare' => false
        );
    }
}
