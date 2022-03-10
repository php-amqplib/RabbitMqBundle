<?php

namespace OldSound\RabbitMqBundle\Tests\DependencyInjection;

use OldSound\RabbitMqBundle\DependencyInjection\OldSoundRabbitMqExtension;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use OldSound\RabbitMqBundle\RabbitMq\ProducerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\Reference;

class OldSoundRabbitMqExtensionTest extends TestCase
{
    public function testFooConnectionDefinition()
    {
        $container = $this->getContainer('test.yml');

        $this->assertTrue($container->has('old_sound_rabbit_mq.connection.foo_connection'));
        $definition = $container->getDefinition('old_sound_rabbit_mq.connection.foo_connection');
        $this->assertTrue($container->has('old_sound_rabbit_mq.connection_factory.foo_connection'));
        $factory = $container->getDefinition('old_sound_rabbit_mq.connection_factory.foo_connection');
        $this->assertEquals(['old_sound_rabbit_mq.connection_factory.foo_connection', 'createConnection'], $definition->getFactory());
        $this->assertEquals([
            'host' => 'foo_host',
            'port' => 123,
            'user' => 'foo_user',
            'password' => 'foo_password',
            'vhost' => '/foo',
            'lazy' => false,
            'connection_timeout' => 3,
            'read_write_timeout' => 3,
            'ssl_context' => [],
            'keepalive' => false,
            'heartbeat' => 0,
            'use_socket' => false,
            'url' => '',
            'hosts' => [],
        ], $factory->getArgument(1));
        $this->assertEquals('%old_sound_rabbit_mq.connection.class%', $definition->getClass());
    }

    public function testSslConnectionDefinition()
    {
        $container = $this->getContainer('test.yml');

        $this->assertTrue($container->has('old_sound_rabbit_mq.connection.ssl_connection'));
        $definition = $container->getDefinition('old_sound_rabbit_mq.connection.ssl_connection');
        $this->assertTrue($container->has('old_sound_rabbit_mq.connection_factory.ssl_connection'));
        $factory = $container->getDefinition('old_sound_rabbit_mq.connection_factory.ssl_connection');
        $this->assertEquals(['old_sound_rabbit_mq.connection_factory.ssl_connection', 'createConnection'], $definition->getFactory());
        $this->assertEquals([
            'host' => 'ssl_host',
            'port' => 123,
            'user' => 'ssl_user',
            'password' => 'ssl_password',
            'vhost' => '/ssl',
            'lazy' => false,
            'connection_timeout' => 3,
            'read_write_timeout' => 3,
            'ssl_context' => [
                'verify_peer' => false,
            ],
            'keepalive' => false,
            'heartbeat' => 0,
            'use_socket' => false,
            'url' => '',
            'hosts' => [],
        ], $factory->getArgument(1));
        $this->assertEquals('%old_sound_rabbit_mq.connection.class%', $definition->getClass());
    }

    public function testLazyConnectionDefinition()
    {
        $container = $this->getContainer('test.yml');

        $this->assertTrue($container->has('old_sound_rabbit_mq.connection.lazy_connection'));
        $definition = $container->getDefinition('old_sound_rabbit_mq.connection.lazy_connection');
        $this->assertTrue($container->has('old_sound_rabbit_mq.connection_factory.lazy_connection'));
        $factory = $container->getDefinition('old_sound_rabbit_mq.connection_factory.lazy_connection');
        $this->assertEquals(['old_sound_rabbit_mq.connection_factory.lazy_connection', 'createConnection'], $definition->getFactory());
        $this->assertEquals([
            'host' => 'lazy_host',
            'port' => 456,
            'user' => 'lazy_user',
            'password' => 'lazy_password',
            'vhost' => '/lazy',
            'lazy' => true,
            'connection_timeout' => 3,
            'read_write_timeout' => 3,
            'ssl_context' => [],
            'keepalive' => false,
            'heartbeat' => 0,
            'use_socket' => false,
            'url' => '',
            'hosts' => [],
        ], $factory->getArgument(1));
        $this->assertEquals('%old_sound_rabbit_mq.lazy.connection.class%', $definition->getClass());
    }

    public function testDefaultConnectionDefinition()
    {
        $container = $this->getContainer('test.yml');

        $this->assertTrue($container->has('old_sound_rabbit_mq.connection.default'));
        $definition = $container->getDefinition('old_sound_rabbit_mq.connection.default');
        $this->assertTrue($container->has('old_sound_rabbit_mq.connection_factory.default'));
        $factory = $container->getDefinition('old_sound_rabbit_mq.connection_factory.default');
        $this->assertEquals(['old_sound_rabbit_mq.connection_factory.default', 'createConnection'], $definition->getFactory());
        $this->assertEquals([
            'host' => 'localhost',
            'port' => 5672,
            'user' => 'guest',
            'password' => 'guest',
            'vhost' => '/',
            'lazy' => false,
            'connection_timeout' => 3,
            'read_write_timeout' => 3,
            'ssl_context' => [],
            'keepalive' => false,
            'heartbeat' => 0,
            'use_socket' => false,
            'url' => '',
            'hosts' => [],
        ], $factory->getArgument(1));
        $this->assertEquals('%old_sound_rabbit_mq.connection.class%', $definition->getClass());
    }

    public function testSocketConnectionDefinition()
    {
        $container = $this->getContainer('test.yml');
        $this->assertTrue($container->has('old_sound_rabbit_mq.connection.socket_connection'));
        $definiton = $container->getDefinition('old_sound_rabbit_mq.connection.socket_connection');
        $this->assertTrue($container->has('old_sound_rabbit_mq.connection_factory.socket_connection'));
        $this->assertEquals('%old_sound_rabbit_mq.socket_connection.class%', $definiton->getClass());
    }

    public function testLazySocketConnectionDefinition()
    {
        $container = $this->getContainer('test.yml');
        $this->assertTrue($container->has('old_sound_rabbit_mq.connection.lazy_socket'));
        $definiton = $container->getDefinition('old_sound_rabbit_mq.connection.lazy_socket');
        $this->assertTrue($container->has('old_sound_rabbit_mq.connection_factory.lazy_socket'));
        $this->assertEquals('%old_sound_rabbit_mq.lazy.socket_connection.class%', $definiton->getClass());
    }

    public function testClusterConnectionDefinition()
    {
        $container = $this->getContainer('test.yml');

        $this->assertTrue($container->has('old_sound_rabbit_mq.connection.cluster_connection'));
        $definition = $container->getDefinition('old_sound_rabbit_mq.connection.cluster_connection');
        $this->assertTrue($container->has('old_sound_rabbit_mq.connection_factory.cluster_connection'));
        $factory = $container->getDefinition('old_sound_rabbit_mq.connection_factory.cluster_connection');
        $this->assertEquals(['old_sound_rabbit_mq.connection_factory.cluster_connection', 'createConnection'], $definition->getFactory());
        $this->assertEquals([
            'hosts' => [
                [
                    'host' => 'cluster_host',
                    'port' => 111,
                    'user' => 'cluster_user',
                    'password' => 'cluster_password',
                    'vhost' => '/cluster',
                    'url' => '',
                ],
                [
                    'host' => 'localhost',
                    'port' => 5672,
                    'user' => 'guest',
                    'password' => 'guest',
                    'vhost' => '/',
                    'url' => 'amqp://cluster_url_host:cluster_url_pass@host:10000/cluster_url_vhost',
                ],
            ],
            'host' => 'localhost',
            'port' => 5672,
            'user' => 'guest',
            'password' => 'guest',
            'vhost' => '/',
            'lazy' => false,
            'connection_timeout' => 3,
            'read_write_timeout' => 3,
            'ssl_context' => [],
            'keepalive' => false,
            'heartbeat' => 0,
            'use_socket' => false,
            'url' => '',
        ], $factory->getArgument(1));
        $this->assertEquals('%old_sound_rabbit_mq.connection.class%', $definition->getClass());
    }

    public function testFooBinding()
    {
        $container = $this->getContainer('test.yml');
        $binding = [
            'arguments'                 => null,
            'class'                     => '%old_sound_rabbit_mq.binding.class%',
            'connection'                => 'default',
            'exchange'                  => 'foo',
            'destination'               => 'bar',
            'destination_is_exchange'   => false,
            'nowait'                    => false,
            'routing_key'               => 'baz',
        ];
        ksort($binding);
        $key = md5(json_encode($binding));
        $name = sprintf('old_sound_rabbit_mq.binding.%s', $key);
        $this->assertTrue($container->has($name));
        $definition = $container->getDefinition($name);
        $this->assertEquals((string) $definition->getArgument(0), 'old_sound_rabbit_mq.connection.default');
        $this->assertBindingMethodCalls($definition, $binding);
    }

    public function testMooBinding()
    {
        $container = $this->getContainer('test.yml');
        $binding = [
            'arguments'                 => ['moo' => 'cow'],
            'class'                     => '%old_sound_rabbit_mq.binding.class%',
            'connection'                => 'default2',
            'exchange'                  => 'moo',
            'destination'               => 'cow',
            'destination_is_exchange'   => true,
            'nowait'                    => true,
            'routing_key'               => null,
        ];
        ksort($binding);
        $key = md5(json_encode($binding));
        $name = sprintf('old_sound_rabbit_mq.binding.%s', $key);
        $this->assertTrue($container->has($name));
        $definition = $container->getDefinition($name);
        $this->assertEquals((string) $definition->getArgument(0), 'old_sound_rabbit_mq.connection.default2');
        $this->assertBindingMethodCalls($definition, $binding);
    }

    protected function assertBindingMethodCalls(Definition $definition, $binding)
    {
        $this->assertEquals(
            [
            [
                'setArguments',
                [
                    $binding['arguments'],
                ],
            ],
            [
                'setDestination',
                [
                    $binding['destination'],
                ],
            ],
            [
                'setDestinationIsExchange',
                [
                    $binding['destination_is_exchange'],
                ],
            ],
            [
                'setExchange',
                [
                    $binding['exchange'],
                ],
            ],
            [
                'isNowait',
                [
                    $binding['nowait'],
                ],
            ],
            [
                'setRoutingKey',
                [
                    $binding['routing_key'],
                ],
            ],
        ],
            $definition->getMethodCalls()
        );
    }
    public function testFooProducerDefinition()
    {
        $container = $this->getContainer('test.yml');

        $this->assertTrue($container->has('old_sound_rabbit_mq.foo_producer_producer'));
        $definition = $container->getDefinition('old_sound_rabbit_mq.foo_producer_producer');
        $this->assertEquals((string) $definition->getArgument(0), 'old_sound_rabbit_mq.connection.foo_connection');
        $this->assertEquals((string) $definition->getArgument(1), 'old_sound_rabbit_mq.channel.foo_producer');
        $this->assertEquals(
            [
                [
                    'setExchangeOptions',
                    [
                        [
                            'name'        => 'foo_exchange',
                            'type'        => 'direct',
                            'passive'     => true,
                            'durable'     => false,
                            'auto_delete' => true,
                            'internal'    => true,
                            'nowait'      => true,
                            'arguments'   => null,
                            'ticket'      => null,
                            'declare'     => true,
                        ],
                    ],
                ],
                [
                    'setQueueOptions',
                    [
                        [
                            'name'        => '',
                            'declare'     => false,
                        ],
                    ],
                ],
                [
                    'setDefaultRoutingKey',
                    [''],
                ],
                [
                    'setContentType',
                    ['text/plain'],
                ],
                [
                    'setDeliveryMode',
                    [2],
                ],
            ],
            $definition->getMethodCalls()
        );
        $this->assertEquals('My\Foo\Producer', $definition->getClass());
    }

    public function testProducerArgumentAliases()
    {
        /** @var ContainerBuilder $container */
        $container = $this->getContainer('test.yml');

        if (!method_exists($container, 'registerAliasForArgument')) {
            // don't test if autowiring arguments functionality is not available
            return;
        }

        // test expected aliases
        $expectedAliases = [
            ProducerInterface::class . ' $fooProducer' => 'old_sound_rabbit_mq.foo_producer_producer',
            'My\Foo\Producer $fooProducer' => 'old_sound_rabbit_mq.foo_producer_producer',
            ProducerInterface::class . ' $fooProducerAliasedProducer' => 'old_sound_rabbit_mq.foo_producer_aliased_producer',
            'My\Foo\Producer $fooProducerAliasedProducer' => 'old_sound_rabbit_mq.foo_producer_aliased_producer',
            ProducerInterface::class . ' $defaultProducer' => 'old_sound_rabbit_mq.default_producer_producer',
            '%old_sound_rabbit_mq.producer.class% $defaultProducer' => 'old_sound_rabbit_mq.default_producer_producer',
        ];

        foreach ($expectedAliases as $id => $target) {
            $this->assertTrue($container->hasAlias($id), sprintf('Container should have %s alias for autowiring support.', $id));

            $alias = $container->getAlias($id);
            $this->assertEquals($target, (string)$alias, sprintf('Autowiring for %s should use %s.', $id, $target));
            $this->assertFalse($alias->isPublic(), sprintf('Autowiring alias for %s should be private', $id));
        }
    }

    /**
     * @group alias
     */
    public function testAliasedFooProducerDefinition()
    {
        $container = $this->getContainer('test.yml');

        $this->assertTrue($container->has('old_sound_rabbit_mq.foo_producer_producer'));
        $this->assertTrue($container->has('foo_producer_alias'));
    }

    public function testDefaultProducerDefinition()
    {
        $container = $this->getContainer('test.yml');

        $this->assertTrue($container->has('old_sound_rabbit_mq.default_producer_producer'));
        $definition = $container->getDefinition('old_sound_rabbit_mq.default_producer_producer');
        $this->assertEquals((string) $definition->getArgument(0), 'old_sound_rabbit_mq.connection.default');
        $this->assertEquals((string) $definition->getArgument(1), 'old_sound_rabbit_mq.channel.default_producer');
        $this->assertEquals(
            [
                [
                    'setExchangeOptions',
                    [
                        [
                            'name'        => 'default_exchange',
                            'type'        => 'direct',
                            'passive'     => false,
                            'durable'     => true,
                            'auto_delete' => false,
                            'internal'    => false,
                            'nowait'      => false,
                            'arguments'   => null,
                            'ticket'      => null,
                            'declare'     => true,
                        ],
                    ],
                ],
                [
                    'setQueueOptions',
                    [
                        [
                            'name'        => '',
                            'declare'     => false,
                        ],
                    ],
                ],
                [
                    'setDefaultRoutingKey',
                    [''],
                ],
                [
                    'setContentType',
                    ['text/plain'],
                ],
                [
                    'setDeliveryMode',
                    [2],
                ],
            ],
            $definition->getMethodCalls()
        );
        $this->assertEquals('%old_sound_rabbit_mq.producer.class%', $definition->getClass());
    }

    public function testFooConsumerDefinition()
    {
        $container = $this->getContainer('test.yml');

        $this->assertTrue($container->has('old_sound_rabbit_mq.foo_consumer_consumer'));
        $definition = $container->getDefinition('old_sound_rabbit_mq.foo_consumer_consumer');
        $this->assertEquals((string) $definition->getArgument(0), 'old_sound_rabbit_mq.connection.foo_connection');
        $this->assertEquals((string) $definition->getArgument(1), 'old_sound_rabbit_mq.channel.foo_consumer');
        $this->assertEquals(
            [
                [
                    'setExchangeOptions',
                    [
                        [
                            'name'        => 'foo_exchange',
                            'type'        => 'direct',
                            'passive'     => true,
                            'durable'     => false,
                            'auto_delete' => true,
                            'internal'    => true,
                            'nowait'      => true,
                            'arguments'   => null,
                            'ticket'      => null,
                            'declare'     => true,
                        ],
                    ],
                ],
                [
                    'setQueueOptions',
                    [
                        [
                            'name'         => 'foo_queue',
                            'passive'      => true,
                            'durable'      => false,
                            'exclusive'    => true,
                            'auto_delete'  => true,
                            'nowait'       => true,
                            'arguments'    => null,
                            'ticket'       => null,
                            'routing_keys' => ['android.#.upload', 'iphone.upload'],
                            'declare'      => true,
                        ],
                    ],
                ],
                [
                    'setCallback',
                    [[new Reference('foo.callback'), 'execute']],
                ],
                [
                    'setTimeoutWait',
                    [3],
                ],
            ],
            $definition->getMethodCalls()
        );
        $this->assertEquals('%old_sound_rabbit_mq.consumer.class%', $definition->getClass());
    }

    public function testConsumerArgumentAliases()
    {
        /** @var ContainerBuilder $container */
        $container = $this->getContainer('test.yml');

        if (!method_exists($container, 'registerAliasForArgument')) {
            // don't test if autowiring arguments functionality is not available
            return;
        }

        $expectedAliases = [
            ConsumerInterface::class . ' $fooConsumer' => 'old_sound_rabbit_mq.foo_consumer_consumer',
            '%old_sound_rabbit_mq.consumer.class% $fooConsumer' => 'old_sound_rabbit_mq.foo_consumer_consumer',
            ConsumerInterface::class . ' $defaultConsumer' => 'old_sound_rabbit_mq.default_consumer_consumer',
            '%old_sound_rabbit_mq.consumer.class% $defaultConsumer' => 'old_sound_rabbit_mq.default_consumer_consumer',
            ConsumerInterface::class . ' $qosTestConsumer' => 'old_sound_rabbit_mq.qos_test_consumer_consumer',
            '%old_sound_rabbit_mq.consumer.class% $qosTestConsumer' => 'old_sound_rabbit_mq.qos_test_consumer_consumer',
        ];
        foreach ($expectedAliases as $id => $target) {
            $this->assertTrue($container->hasAlias($id), sprintf('Container should have %s alias for autowiring support.', $id));

            $alias = $container->getAlias($id);
            $this->assertEquals($target, (string)$alias, sprintf('Autowiring for %s should use %s.', $id, $target));
            $this->assertFalse($alias->isPublic(), sprintf('Autowiring alias for %s should be private', $id));
        }
    }

    public function testDefaultConsumerDefinition()
    {
        $container = $this->getContainer('test.yml');

        $this->assertTrue($container->has('old_sound_rabbit_mq.default_consumer_consumer'));
        $definition = $container->getDefinition('old_sound_rabbit_mq.default_consumer_consumer');
        $this->assertEquals((string) $definition->getArgument(0), 'old_sound_rabbit_mq.connection.default');
        $this->assertEquals((string) $definition->getArgument(1), 'old_sound_rabbit_mq.channel.default_consumer');
        $this->assertEquals(
            [
                [
                    'setExchangeOptions',
                    [
                        [
                            'name'        => 'default_exchange',
                            'type'        => 'direct',
                            'passive'     => false,
                            'durable'     => true,
                            'auto_delete' => false,
                            'internal'    => false,
                            'nowait'      => false,
                            'arguments'   => null,
                            'ticket'      => null,
                            'declare'     => true,
                        ],
                    ],
                ],
                [
                    'setQueueOptions',
                    [
                        [
                            'name'        => 'default_queue',
                            'passive'     => false,
                            'durable'     => true,
                            'exclusive'   => false,
                            'auto_delete' => false,
                            'nowait'      => false,
                            'arguments'   => null,
                            'ticket'      => null,
                            'routing_keys' => [],
                            'declare'     => true,
                        ],
                    ],
                ],
                [
                    'setCallback',
                    [[new Reference('default.callback'), 'execute']],
                ],
            ],
            $definition->getMethodCalls()
        );
        $this->assertEquals('%old_sound_rabbit_mq.consumer.class%', $definition->getClass());
    }

    public function testConsumerWithQosOptions()
    {
        $container = $this->getContainer('test.yml');

        $this->assertTrue($container->has('old_sound_rabbit_mq.qos_test_consumer_consumer'));
        $definition = $container->getDefinition('old_sound_rabbit_mq.qos_test_consumer_consumer');
        $methodCalls = $definition->getMethodCalls();

        $setQosParameters = null;
        foreach ($methodCalls as $methodCall) {
            if ($methodCall[0] === 'setQosOptions') {
                $setQosParameters = $methodCall[1];
            }
        }

        $this->assertIsArray($setQosParameters);
        $this->assertEquals(
            [
                1024,
                1,
                true,
            ],
            $setQosParameters
        );
    }

    public function testMultipleConsumerDefinition()
    {
        $container = $this->getContainer('test.yml');

        $this->assertTrue($container->has('old_sound_rabbit_mq.multi_test_consumer_multiple'));
        $definition = $container->getDefinition('old_sound_rabbit_mq.multi_test_consumer_multiple');
        $this->assertEquals(
            [
                [
                    'setExchangeOptions',
                    [
                        [
                            'name'        => 'foo_multiple_exchange',
                            'type'        => 'direct',
                            'passive'     => false,
                            'durable'     => true,
                            'auto_delete' => false,
                            'internal'    => false,
                            'nowait'      => false,
                            'arguments'   => null,
                            'ticket'      => null,
                            'declare'     => true,
                        ],
                    ],
                ],
                [
                    'setQueues',
                    [
                        [
                            'multi_test_1' => [
                                'name'         => 'multi_test_1',
                                'passive'      => false,
                                'durable'      => true,
                                'exclusive'    => false,
                                'auto_delete'  => false,
                                'nowait'       => false,
                                'arguments'    => null,
                                'ticket'       => null,
                                'routing_keys' => [],
                                'callback'     => [new Reference('foo.multiple_test1.callback'), 'execute'],
                                'declare'      => true,
                            ],
                            'foo_bar_2' => [
                                'name'         => 'foo_bar_2',
                                'passive'      => true,
                                'durable'      => false,
                                'exclusive'    => true,
                                'auto_delete'  => true,
                                'nowait'       => true,
                                'arguments'    => null,
                                'ticket'       => null,
                                'routing_keys' => [
                                    'android.upload',
                                    'iphone.upload',
                                ],
                                'callback'     => [new Reference('foo.multiple_test2.callback'), 'execute'],
                                'declare'      => true,
                            ],
                        ],
                    ],
                ],
                [
                    'setQueuesProvider',
                    [
                        new Reference('foo.queues_provider'),
                    ],
                ],
                [
                    'setTimeoutWait',
                    [3],
                ],
            ],
            $definition->getMethodCalls()
        );
    }

    public function testDynamicConsumerDefinition()
    {
        $container = $this->getContainer('test.yml');

        $this->assertTrue($container->has('old_sound_rabbit_mq.foo_dyn_consumer_dynamic'));
        $this->assertTrue($container->has('old_sound_rabbit_mq.bar_dyn_consumer_dynamic'));

        $definition = $container->getDefinition('old_sound_rabbit_mq.foo_dyn_consumer_dynamic');
        $this->assertEquals(
            [
                [
                    'setExchangeOptions',
                        [
                            [
                                'name' => 'foo_dynamic_exchange',
                                'type' => 'direct',
                                'passive' => false,
                                'durable' => true,
                                'auto_delete' => false,
                                'internal' => false,
                                'nowait' => false,
                                'declare' => true,
                                'arguments' => null,
                                'ticket' => null,
                            ],
                        ],
                ],
                [
                    'setCallback',
                        [
                            [new Reference('foo.dynamic.callback'), 'execute'],
                        ],
                ],
                [
                    'setQueueOptionsProvider',
                        [
                            new Reference('foo.dynamic.provider'),
                        ],
                ],
            ],
            $definition->getMethodCalls()
        );
    }

    public function testFooAnonConsumerDefinition()
    {
        $container = $this->getContainer('test.yml');

        $this->assertTrue($container->has('old_sound_rabbit_mq.foo_anon_consumer_anon'));
        $definition = $container->getDefinition('old_sound_rabbit_mq.foo_anon_consumer_anon');
        $this->assertEquals((string) $definition->getArgument(0), 'old_sound_rabbit_mq.connection.foo_connection');
        $this->assertEquals((string) $definition->getArgument(1), 'old_sound_rabbit_mq.channel.foo_anon_consumer');
        $this->assertEquals(
            [
                [
                    'setExchangeOptions',
                    [
                        [
                            'name'        => 'foo_anon_exchange',
                            'type'        => 'direct',
                            'passive'     => true,
                            'durable'     => false,
                            'auto_delete' => true,
                            'internal'    => true,
                            'nowait'      => true,
                            'arguments'   => null,
                            'ticket'      => null,
                            'declare'     => true,
                        ],
                    ],
                ],
                [
                    'setCallback',
                    [[new Reference('foo_anon.callback'), 'execute']],
                ],
            ],
            $definition->getMethodCalls()
        );
        $this->assertEquals('%old_sound_rabbit_mq.anon_consumer.class%', $definition->getClass());
    }

    public function testDefaultAnonConsumerDefinition()
    {
        $container = $this->getContainer('test.yml');

        $this->assertTrue($container->has('old_sound_rabbit_mq.default_anon_consumer_anon'));
        $definition = $container->getDefinition('old_sound_rabbit_mq.default_anon_consumer_anon');
        $this->assertEquals((string) $definition->getArgument(0), 'old_sound_rabbit_mq.connection.default');
        $this->assertEquals((string) $definition->getArgument(1), 'old_sound_rabbit_mq.channel.default_anon_consumer');
        $this->assertEquals(
            [
                [
                    'setExchangeOptions',
                    [
                        [
                            'name'        => 'default_anon_exchange',
                            'type'        => 'direct',
                            'passive'     => false,
                            'durable'     => true,
                            'auto_delete' => false,
                            'internal'    => false,
                            'nowait'      => false,
                            'arguments'   => null,
                            'ticket'      => null,
                            'declare'     => true,
                        ],
                    ],
                ],
                [
                    'setCallback',
                    [[new Reference('default_anon.callback'), 'execute']],
                ],
            ],
            $definition->getMethodCalls()
        );
        $this->assertEquals('%old_sound_rabbit_mq.anon_consumer.class%', $definition->getClass());
    }

    public function testFooRpcClientDefinition()
    {
        $container = $this->getContainer('rpc-clients.yml');

        $this->assertTrue($container->has('old_sound_rabbit_mq.foo_client_rpc'));
        $definition = $container->getDefinition('old_sound_rabbit_mq.foo_client_rpc');
        $this->assertEquals((string) $definition->getArgument(0), 'old_sound_rabbit_mq.connection.foo_connection');
        $this->assertEquals((string) $definition->getArgument(1), 'old_sound_rabbit_mq.channel.foo_client');
        $this->assertEquals(
            [
                ['initClient', [true]],
                ['setUnserializer', ['json_decode']],
                ['setDirectReplyTo', [true]],
            ],
            $definition->getMethodCalls()
        );
        $this->assertEquals('%old_sound_rabbit_mq.rpc_client.class%', $definition->getClass());
    }

    public function testDefaultRpcClientDefinition()
    {
        $container = $this->getContainer('rpc-clients.yml');

        $this->assertTrue($container->has('old_sound_rabbit_mq.default_client_rpc'));
        $definition = $container->getDefinition('old_sound_rabbit_mq.default_client_rpc');
        $this->assertEquals((string) $definition->getArgument(0), 'old_sound_rabbit_mq.connection.default');
        $this->assertEquals((string) $definition->getArgument(1), 'old_sound_rabbit_mq.channel.default_client');
        $this->assertEquals(
            [
                ['initClient', [true]],
                ['setUnserializer', ['unserialize']],
                ['setDirectReplyTo', [false]],
            ],
            $definition->getMethodCalls()
        );
        $this->assertFalse($definition->isLazy());
        $this->assertEquals('%old_sound_rabbit_mq.rpc_client.class%', $definition->getClass());
    }

    public function testLazyRpcClientDefinition()
    {
        $container = $this->getContainer('rpc-clients.yml');

        $this->assertTrue($container->has('old_sound_rabbit_mq.lazy_client_rpc'));
        $definition = $container->getDefinition('old_sound_rabbit_mq.lazy_client_rpc');
        $this->assertEquals((string) $definition->getArgument(0), 'old_sound_rabbit_mq.connection.default');
        $this->assertEquals((string) $definition->getArgument(1), 'old_sound_rabbit_mq.channel.lazy_client');
        $this->assertEquals(
            [
                ['initClient', [true]],
                ['setUnserializer', ['unserialize']],
                ['setDirectReplyTo', [false]],
            ],
            $definition->getMethodCalls()
        );
        $this->assertTrue($definition->isLazy());
        $this->assertEquals('%old_sound_rabbit_mq.rpc_client.class%', $definition->getClass());
    }

    public function testFooRpcServerDefinition()
    {
        $container = $this->getContainer('test.yml');

        $this->assertTrue($container->has('old_sound_rabbit_mq.foo_server_server'));
        $definition = $container->getDefinition('old_sound_rabbit_mq.foo_server_server');
        $this->assertEquals((string) $definition->getArgument(0), 'old_sound_rabbit_mq.connection.foo_connection');
        $this->assertEquals((string) $definition->getArgument(1), 'old_sound_rabbit_mq.channel.foo_server');
        $this->assertEquals(
            [
                ['initServer', ['foo_server']],
                ['setCallback', [[new Reference('foo_server.callback'), 'execute']]],
                ['setSerializer', ['json_encode']],
            ],
            $definition->getMethodCalls()
        );
        $this->assertEquals('%old_sound_rabbit_mq.rpc_server.class%', $definition->getClass());
    }

    public function testDefaultRpcServerDefinition()
    {
        $container = $this->getContainer('test.yml');

        $this->assertTrue($container->has('old_sound_rabbit_mq.default_server_server'));
        $definition = $container->getDefinition('old_sound_rabbit_mq.default_server_server');
        $this->assertEquals((string) $definition->getArgument(0), 'old_sound_rabbit_mq.connection.default');
        $this->assertEquals((string) $definition->getArgument(1), 'old_sound_rabbit_mq.channel.default_server');
        $this->assertEquals(
            [
                ['initServer', ['default_server']],
                ['setCallback', [[new Reference('default_server.callback'), 'execute']]],
                ['setSerializer', ['serialize']],
            ],
            $definition->getMethodCalls()
        );
        $this->assertEquals('%old_sound_rabbit_mq.rpc_server.class%', $definition->getClass());
    }

    public function testRpcServerWithQueueOptionsDefinition()
    {
        $container = $this->getContainer('test.yml');

        $this->assertTrue($container->has('old_sound_rabbit_mq.server_with_queue_options_server'));
        $definition = $container->getDefinition('old_sound_rabbit_mq.server_with_queue_options_server');
        $this->assertEquals((string) $definition->getArgument(0), 'old_sound_rabbit_mq.connection.default');
        $this->assertEquals((string) $definition->getArgument(1), 'old_sound_rabbit_mq.channel.server_with_queue_options');
        $this->assertEquals(
            [
                ['initServer', ['server_with_queue_options']],
                ['setCallback', [[new Reference('server_with_queue_options.callback'), 'execute']]],
                ['setQueueOptions', [[
                    'name'         => 'server_with_queue_options-queue',
                    'passive'      => false,
                    'durable'      => true,
                    'exclusive'    => false,
                    'auto_delete'  => false,
                    'nowait'       => false,
                    'arguments'    => null,
                    'ticket'       => null,
                    'routing_keys' => [],
                    'declare'      => true,
                ]]],
                ['setSerializer', ['serialize']],
            ],
            $definition->getMethodCalls()
        );
        $this->assertEquals('%old_sound_rabbit_mq.rpc_server.class%', $definition->getClass());
    }

    public function testRpcServerWithExchangeOptionsDefinition()
    {
        $container = $this->getContainer('test.yml');

        $this->assertTrue($container->has('old_sound_rabbit_mq.server_with_exchange_options_server'));
        $definition = $container->getDefinition('old_sound_rabbit_mq.server_with_exchange_options_server');
        $this->assertEquals((string) $definition->getArgument(0), 'old_sound_rabbit_mq.connection.default');
        $this->assertEquals((string) $definition->getArgument(1), 'old_sound_rabbit_mq.channel.server_with_exchange_options');
        $this->assertEquals(
            [
            ['initServer', ['server_with_exchange_options']],
            ['setCallback', [[new Reference('server_with_exchange_options.callback'), 'execute']]],
            ['setExchangeOptions', [[
                'name'         => 'exchange',
                'type'         => 'topic',
                'passive'      => false,
                'durable'      => true,
                'auto_delete'  => false,
                'internal'     => null,
                'nowait'       => false,
                'declare'      => true,
                'arguments'    => null,
                'ticket'       => null,
            ]]],
            ['setSerializer', ['serialize']],
        ],
            $definition->getMethodCalls()
        );
        $this->assertEquals('%old_sound_rabbit_mq.rpc_server.class%', $definition->getClass());
    }

    public function testHasCollectorWhenChannelsExist()
    {
        $container = $this->getContainer('collector.yml');

        $this->assertTrue($container->has('old_sound_rabbit_mq.data_collector'));
        $definition = $container->getDefinition('old_sound_rabbit_mq.data_collector');

        $this->assertEquals(
            [
                new Reference('old_sound_rabbit_mq.channel.default_producer'),
                new Reference('old_sound_rabbit_mq.channel.default_consumer'),
            ],
            $definition->getArgument(0)
        );
    }

    public function testHasNoCollectorWhenNoChannelsExist()
    {
        $container = $this->getContainer('no_collector.yml');
        $this->assertFalse($container->has('old_sound_rabbit_mq.data_collector'));
    }

    public function testCollectorCanBeDisabled()
    {
        $container = $this->getContainer('collector_disabled.yml');
        $this->assertFalse($container->has('old_sound_rabbit_mq.data_collector'));
    }

    public function testExchangeArgumentsAreArray()
    {
        $container = $this->getContainer('exchange_arguments.yml');

        $definition = $container->getDefinition('old_sound_rabbit_mq.producer_producer');
        $calls = $definition->getMethodCalls();
        $this->assertEquals('setExchangeOptions', $calls[0][0]);
        $options = $calls[0][1];
        $this->assertEquals(['name' => 'bar'], $options[0]['arguments']);

        $definition = $container->getDefinition('old_sound_rabbit_mq.consumer_consumer');
        $calls = $definition->getMethodCalls();
        $this->assertEquals('setExchangeOptions', $calls[0][0]);
        $options = $calls[0][1];
        $this->assertEquals(['name' => 'bar'], $options[0]['arguments']);
    }

    public function testProducerWithoutExplicitExchangeOptionsConnectsToAMQPDefault()
    {
        $container = $this->getContainer('no_exchange_options.yml');

        $definition = $container->getDefinition('old_sound_rabbit_mq.producer_producer');
        $calls = $definition->getMethodCalls();
        $this->assertEquals('setExchangeOptions', $calls[0][0]);
        $options = $calls[0][1];

        $this->assertEquals('', $options[0]['name']);
        $this->assertEquals('direct', $options[0]['type']);
        $this->assertEquals(false, $options[0]['declare']);
        $this->assertEquals(true, $options[0]['passive']);
    }

    public function testProducersWithLogger()
    {
        $container = $this->getContainer('config_with_enable_logger.yml');
        $definition = $container->getDefinition('old_sound_rabbit_mq.default_consumer_consumer');
        $this->assertTrue(
            $definition->hasTag('monolog.logger'),
            'service should be marked for logger'
        );
    }

    private function getContainer($file, $debug = false)
    {
        $container = new ContainerBuilder(new ParameterBag(['kernel.debug' => $debug]));
        $container->registerExtension(new OldSoundRabbitMqExtension());

        $locator = new FileLocator(__DIR__.'/Fixtures');
        $loader = new YamlFileLoader($container, $locator);
        $loader->load($file);

        $container->getCompilerPassConfig()->setOptimizationPasses([]);
        $container->getCompilerPassConfig()->setRemovingPasses([]);
        $container->compile();

        return $container;
    }
}
