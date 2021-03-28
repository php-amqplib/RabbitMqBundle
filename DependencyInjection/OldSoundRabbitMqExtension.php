<?php

namespace OldSound\RabbitMqBundle\DependencyInjection;

use OldSound\RabbitMqBundle\Consumer\ConsumersRegistry;
use OldSound\RabbitMqBundle\DataCollector\MessageDataCollector;
use OldSound\RabbitMqBundle\Declarations\ConsumerDef;
use OldSound\RabbitMqBundle\Declarations\DeclarationsRegistry;
use OldSound\RabbitMqBundle\Declarations\ConsumeOptions;
use OldSound\RabbitMqBundle\Declarations\BatchConsumeOptions;
use OldSound\RabbitMqBundle\Declarations\BindingDeclaration;
use OldSound\RabbitMqBundle\Declarations\ExchangeDeclaration;
use OldSound\RabbitMqBundle\Declarations\QueueDeclaration;
use OldSound\RabbitMqBundle\ExecuteCallbackStrategy\SimpleExecuteCallbackStrategy;
use OldSound\RabbitMqBundle\ExecuteCallbackStrategy\BatchExecuteCallbackStrategy;
use OldSound\RabbitMqBundle\Producer\NullProducer;
use OldSound\RabbitMqBundle\Producer\Producer;
use OldSound\RabbitMqBundle\RabbitMq\AMQPConnectionFactory;
use OldSound\RabbitMqBundle\RabbitMq\TraceableAMQPChannel;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Connection\AMQPLazyConnection;
use PhpAmqpLib\Connection\AMQPLazySocketConnection;
use PhpAmqpLib\Connection\AMQPSocketConnection;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\Log\Logger;
use Symfony\Component\Serializer\SerializerInterface;

/**+
 * OldSoundRabbitMqExtension.
 *
 * @author Alvaro Videla
 * @author Marc Weistroff <marc.weistroff@sensio.com>
 */
class OldSoundRabbitMqExtension extends Extension
{
    /** @var ContainerBuilder */
    private $container;
    /** @var array */
    private $config;
    /** @var Boolean Whether the data collector is enabled */
    private $collectorEnabled;
    /** @var string */
    private $alias;

    public function __construct(string $alias = 'old_sound_rabbit_mq')
    {
        $this->alias = $alias;
    }

    public function load(array $configs, ContainerBuilder $container)
    {
        $this->container = $container;
        $configuration = $this->getConfiguration($configs, $container);
        $this->config = $this->processConfiguration($configuration, $configs);

        $loader = new XmlFileLoader($this->container, new FileLocator([__DIR__ . '/../Resources/config']));
        $loader->load('rabbitmq.xml');

        $this->collectorEnabled = $this->config['enable_collector'];

        $this->loadConnections();

        $declarationRegistryDef = new Definition(DeclarationsRegistry::class);
        $declarationRegistryDef->setPublic(true);
        $declarationRegistryDef->setAutowired(true);
        $this->container->setDefinition('old_sound_rabbit_mq.declaration_registry', $declarationRegistryDef);

        foreach ($this->loadExchanges($this->config['exchanges']) as $exchange) {
            $declarationRegistryDef->addMethodCall('addExchange', [$exchange]);
        };
        foreach ($this->loadQueues($this->config['queues']) as $queue) {
            $declarationRegistryDef->addMethodCall('addQueue', [$queue]);
        };
        foreach ($this->loadBindings($this->config['bindings']) as $binding) {
            $this->container->getDefinition('old_sound_rabbit_mq.declaration_registry')->addMethodCall('addBinding', [$binding]);
        };

        $this->loadProducers();
        $this->loadConsumers($declarationRegistryDef);

        if ($this->collectorEnabled) {
            $dataCollectorDef = new Definition(MessageDataCollector::class);
            $dataCollectorDef->setArgument('$channels', new TaggedIteratorArgument('old_sound_rabbit_mq.traceable_channel'));
            $dataCollectorDef->addTag('data_collector', [
               ['template' => '@OldSoundRabbitMq/Collector/collector.html.twig', 'id' => 'rabbit_mq']
            ]);
            $this->container->setDefinition('old_sound_rabbit_mq.data_collector', $dataCollectorDef);
        }
    }

    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        return new Configuration($this->getAlias());
    }

    /**
     * @return Definition[]
     */
    protected function loadExchanges($exchanges): array
    {
        return array_map(function ($exchange) {
            $exchangeDeclaration = new Definition(ExchangeDeclaration::class);
            $exchangeDeclaration->setProperties($exchange);

            foreach($this->loadBindings($exchange['bindings'], $exchange['name'], null) as $binding) {
                $this->container->getDefinition('old_sound_rabbit_mq.declaration_registry')->addMethodCall('addBinding', [$binding]);
            }

            $this->container->setDefinition('old_sound_rabbit_mq.exchange.'.$exchange['name'], $exchangeDeclaration);
            return $exchangeDeclaration;
        }, $exchanges);
    }

    /**
     * @return Definition[]
     */
    protected function loadQueues($queues): array
    {
        return array_map(function ($queue, $key) use ($queues) {
            $queue['name'] = $queue['name'] ?? $key;
            $queueDeclaration = new Definition(QueueDeclaration::class);
            $queueDeclaration->setProperties($queue);

            foreach ($this->loadBindings($queue['bindings'], null, $queue['name'], false) as $binding) {
                $this->container->getDefinition('old_sound_rabbit_mq.declaration_registry')->addMethodCall('addBinding', [$binding]);
            }

            return $queueDeclaration;
        }, $queues, array_keys($queues));
    }

    protected function createBindingDef($binding, string $exchange = null, string $destination = null, bool $destinationIsExchange = null): Definition
    {
        $routingKeys = $binding['routing_keys'];
        if (isset($binding['routing_key'])) {
            $routingKeys[] = $binding['routing_key'];
        }
        $definition = new Definition(BindingDeclaration::class);
        $definition->setProperties([
            'exchange' => $exchange ? $exchange : $binding['exchange'],
            'destinationIsExchange' => isset($destinationIsExchange) ? $destinationIsExchange : $binding['destination_is_exchange'],
            'destination' => $destination ? $destination : $binding['destination'],
            'routingKeys' => array_unique($routingKeys),
            // TODO 'arguments' => $binding['arguments'],
            //'nowait' => $binding['nowait'],
        ]);

        return $definition;
    }

    protected function loadBindings($bindings, string $exchange = null, string $destination = null, bool $destinationIsExchange = null): array
    {
        $definitions = [];
        foreach ($bindings as $binding) {
            $definitions[] = $this->createBindingDef($binding, $exchange, $destination, $destinationIsExchange);
        }

        return $definitions;
    }

    protected function loadConnections()
    {
        $connFactoryDer = new Definition(AMQPConnectionFactory::class);

        foreach ($this->config['connections'] as $connectionName => $connection) {
            if ($connection['lazy']) {
                $connectionClass = $connection['use_socket'] ?
                    AMQPLazySocketConnection::class :
                    AMQPLazyConnection::class;
            } else {
                $connectionClass = $connection['use_socket'] ?
                    AMQPSocketConnection::class :
                    AMQPConnection::class;
            }

            $definition = new Definition($connectionClass);
            $definition->setFactory([$connFactoryDer, 'createConnection']);
            $definition->setArguments([$connectionClass, $connection]);

            $definition->addTag('old_sound_rabbit_mq.connection');
            $definition->setPublic(true);

            $connectionAliase = sprintf('old_sound_rabbit_mq.connection.%s', $connectionName);
            $this->container->setDefinition($connectionAliase, $definition);

            $this->createChannelDef($connectionName);
        }
    }

    protected function loadProducers()
    {
        $defaultAutoDeclare = $this->container->getParameter('kernel.environment') !== 'prod';
        foreach ($this->config['producers'] as $producerName => $producer) {
            $definition = new Definition($this->config['sandbox'] ? NullProducer::class : Producer::class);
            $definition->setPublic(true);
            $definition->addTag('old_sound_rabbit_mq.producer', ['producer' => $producerName]);
            if ($this->config['sandbox']) {
                continue;
            }

            $alias = sprintf('old_sound_rabbit_mq.producer.%s', $producerName);

            $connectionRef = $this->createConnectionRef($producer['connection'] ?? 'default');
            $definition
                ->setArgument('$connection', $connectionRef)
                ->setArgument('$exchange', $producer['exchange']);
            if ($producer['auto_declare']) {
                $definition->addMethodCall('setRegisterDeclare', [new Reference('old_sound_rabbit_mq.declaration_registry')]);
            }

            if (isset($producer['additional_properties'])) {
                $definition->addMethodCall('setAdditionalProperties', [$producer['additional_properties']]);
            }

            $this->container->setDefinition($alias, $definition);
        }
    }

    protected function loadConsumers(Definition $declarationRegistryDef)
    {
        $consumeOptionsDefs = [];
        foreach ($this->config['consumers'] as $consumerName => $consumer) {
            $alias = sprintf('old_sound_rabbit_mq.consumer.%s', $consumerName);
            $serializerAlias = sprintf('old_sound_rabbit_mq.consumer.%s.serializer', $consumerName);// TODO


            foreach($consumer['consume'] as $index => $consumeOptions) {
                $isBatch = isset($consumeOptions['batch_count']);
                $consumeOptionsDef = new Definition($isBatch ? BatchConsumeOptions::class : ConsumeOptions::class);

                $receiver = $consumeOptions['receiver'];
                if (!preg_match('/^[^\:]+(?:::(?:[^\:]+))?$/', $receiver)) {
                    throw new \InvalidArgumentException(sprintf(
                        'Invalid receiver "%s" passed to the %s consumer: use the format "object_id::method" or "object_id" if your object class has an "__invoke" method.',
                        $receiver,
                        $consumerName
                    ));
                }
                $parts = explode('::', $receiver);
                $method = $parts[1] ?? '__invoke';
                $receiver = new Reference($parts[0]);

                $consumeOptionsDef->setProperties([
                    'queue' => $consumeOptions['queue'],
                    'qosPrefetchCount' => $consumeOptions['qos_prefetch_count'],
                    'batchCount' => $consumeOptions['batch_count'] ?? null,
                    'receiver' => [$receiver, $method]
                ]);

                if ($isBatch) {
                    $consumeOptionsDef->setProperty('batchCount', $consumeOptions['batch_count']);
                }

                $consumeOptionsDefs[] = $consumeOptionsDef;
            }

            $definition = new Definition(ConsumerDef::class);
            $definition
                ->setProperties([
                    'name' => $consumerName,
                    'connection' => $this->createConnectionRef($consumer['connection'] ?? 'default'),
                    'consumeOptions' => $consumeOptionsDefs
                ])
                ->setPublic(true);

            if (isset($consumer['idle_timeout'])) {
                $definition->addMethodCall('setIdleTimeout', [$consumer['idle_timeout']]);
            }
            if (isset($consumer['idle_timeout_exit_code'])) {
                $definition->addMethodCall('setIdleTimeoutExitCode', [$consumer['idle_timeout_exit_code']]);
            }
            if (isset($consumer['timeout_wait'])) {
                $definition->setProperty('timeoutWait', [$consumer['timeout_wait']]);
            }
            if (isset($consumer['graceful_max_execution'])) {
                $definition->addMethodCall(
                    'setGracefulMaxExecutionDateTimeFromSecondsInTheFuture',
                    [$consumer['graceful_max_execution']['timeout']]
                );
                $definition->addMethodCall(
                    'setGracefulMaxExecutionTimeoutExitCode',
                    [$consumer['graceful_max_execution']['exit_code']]
                );
            }

            $this->container->setDefinition($alias, $definition);
            $declarationRegistryDef->addMethodCall('addConsumer', [new Reference($alias)]);

            //if ($consumer['logging']) {
            //    $this->injectLogger($alias, $definition);
            //}
        }
    }

    public function getAlias()
    {
        return $this->alias;
    }

    private function createChannelDef(string $connectionName)
    {
        $channelID = sprintf('old_sound_rabbit_mq.channel.%s', $connectionName);
        $def = new Definition(
            $this->collectorEnabled ? TraceableAMQPChannel::class : AMQPChannel::class
        );

        $def->setPublic(true);
        $def->setLazy(true);

        $def->setFactory([AMQPConnectionFactory::class, 'getChannelFromConnection']);
        $def->setArgument('$connection', $this->createConnectionRef($connectionName));
        if ($this->collectorEnabled) {
            $def->addTag('old_sound_rabbit_mq.traceable_channel');
        }

        $this->container->setDefinition($channelID, $def);
    }

    private function createConnectionRef($connectionName): Reference
    {
        return new Reference(sprintf('old_sound_rabbit_mq.connection.%s', $connectionName));
    }

    private function injectLogger(string $definitionAlias, Definition $definition)
    {
        $definition->addTag('monolog.logger', [
            'channel' => 'phpamqplib'
        ]);

        $loggerAlias = $definitionAlias . '.loggeer';
        $this->container->setAlias($loggerAlias, 'logger');
        $definition->addMethodCall('setLogger', [new Reference($loggerAlias, ContainerInterface::IGNORE_ON_INVALID_REFERENCE)]);
    }
}
