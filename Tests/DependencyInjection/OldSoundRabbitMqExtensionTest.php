<?php

namespace OldSound\RabbitMqBundle\Tests\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use OldSound\RabbitMqBundle\DependencyInjection\OldSoundRabbitMqExtension;
use Symfony\Component\DependencyInjection\Reference;

class OldSoundRabbitMqExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function testFooConnectionDefinition()
    {
        $container = $this->getContainer('test.yml');

        $this->assertTrue($container->has('old_sound_rabbit_mq.connection.foo_connection'));
        $definition = $container->getDefinition('old_sound_rabbit_mq.connection.foo_connection');
        $this->assertEquals('foo_host', $definition->getArgument(0));
        $this->assertEquals(123, $definition->getArgument(1));
        $this->assertEquals('foo_user', $definition->getArgument(2));
        $this->assertEquals('foo_password', $definition->getArgument(3));
        $this->assertEquals('/foo', $definition->getArgument(4));
        $this->assertEquals('%old_sound_rabbit_mq.connection.class%', $definition->getClass());
    }

    public function testLazyConnectionDefinition()
    {
        $container = $this->getContainer('test.yml');

        $this->assertTrue($container->has('old_sound_rabbit_mq.connection.lazy_connection'));
        $definition = $container->getDefinition('old_sound_rabbit_mq.connection.lazy_connection');
        $this->assertEquals('lazy_host', $definition->getArgument(0));
        $this->assertEquals(456, $definition->getArgument(1));
        $this->assertEquals('lazy_user', $definition->getArgument(2));
        $this->assertEquals('lazy_password', $definition->getArgument(3));
        $this->assertEquals('/lazy', $definition->getArgument(4));
        $this->assertEquals('%old_sound_rabbit_mq.lazy.connection.class%', $definition->getClass());
    }

    public function testSslConnectionDefinition()
    {
        $container = $this->getContainer('test.yml');

        $this->assertTrue($container->has('old_sound_rabbit_mq.connection.ssl_connection'));
        $definition = $container->getDefinition('old_sound_rabbit_mq.connection.ssl_connection');
        $this->assertEquals('ssl_host', $definition->getArgument(0));
        $this->assertEquals(678, $definition->getArgument(1));
        $this->assertEquals('ssl_user', $definition->getArgument(2));
        $this->assertEquals('ssl_password', $definition->getArgument(3));
        $this->assertEquals('/ssl', $definition->getArgument(4));
        $sslOptions = array(
            'verify_peer' => true,
            'capath' => '/etc/ssl/certs/',
        );
        $this->assertEquals($sslOptions, $definition->getArgument(5));
        $this->assertEquals('%old_sound_rabbit_mq.ssl.connection.class%', $definition->getClass());
    }

    public function testDefaultConnectionDefinition()
    {
        $container = $this->getContainer('test.yml');

        $this->assertTrue($container->has('old_sound_rabbit_mq.connection.default'));
        $definition = $container->getDefinition('old_sound_rabbit_mq.connection.default');
        $this->assertEquals('localhost', $definition->getArgument(0));
        $this->assertEquals(5672, $definition->getArgument(1));
        $this->assertEquals('guest', $definition->getArgument(2));
        $this->assertEquals('guest', $definition->getArgument(3));
        $this->assertEquals('/', $definition->getArgument(4));
        $this->assertEquals('%old_sound_rabbit_mq.connection.class%', $definition->getClass());
    }

    public function testFooProducerDefinition()
    {
        $container = $this->getContainer('test.yml');

        $this->assertTrue($container->has('old_sound_rabbit_mq.foo_producer_producer'));
        $definition = $container->getDefinition('old_sound_rabbit_mq.foo_producer_producer');
        $this->assertEquals((string) $definition->getArgument(0), 'old_sound_rabbit_mq.connection.foo_connection');
        $this->assertEquals((string) $definition->getArgument(1), 'old_sound_rabbit_mq.channel.foo_producer');
        $this->assertEquals(array(
                array(
                    'setExchangeOptions',
                    array(
                        array(
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
                        )
                    )
                ),
                array(
                    'setQueueOptions',
                    array(
                        array(
                            'name'        => null,
                        )
                    )
                )
            ),
            $definition->getMethodCalls()
        );
        $this->assertEquals('My\Foo\Producer', $definition->getClass());
    }

    public function testDefaultProducerDefinition()
    {
        $container = $this->getContainer('test.yml');

        $this->assertTrue($container->has('old_sound_rabbit_mq.default_producer_producer'));
        $definition = $container->getDefinition('old_sound_rabbit_mq.default_producer_producer');
        $this->assertEquals((string) $definition->getArgument(0), 'old_sound_rabbit_mq.connection.default');
        $this->assertEquals((string) $definition->getArgument(1), 'old_sound_rabbit_mq.channel.default_producer');
        $this->assertEquals(array(
                array(
                    'setExchangeOptions',
                    array(
                        array(
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
                        )
                    )
                ),
                array(
                    'setQueueOptions',
                    array(
                        array(
                            'name'        => null,
                        )
                    )
                )
            ),
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
        $this->assertEquals(array(
                array(
                    'setExchangeOptions',
                    array(
                        array(
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
                        )
                    )
                ),
                array(
                    'setQueueOptions',
                    array(
                        array(
                            'name'         => 'foo_queue',
                            'passive'      => true,
                            'durable'      => false,
                            'exclusive'    => true,
                            'auto_delete'  => true,
                            'nowait'       => true,
                            'arguments'    => null,
                            'ticket'       => null,
                            'routing_keys' => array('android.#.upload', 'iphone.upload'),
                        )
                    )
                ),
                array(
                    'setCallback',
                    array(array(new Reference('foo.callback'), 'execute'))
                )
            ),
            $definition->getMethodCalls()
        );
        $this->assertEquals('%old_sound_rabbit_mq.consumer.class%', $definition->getClass());
    }

    public function testDefaultConsumerDefinition()
    {
        $container = $this->getContainer('test.yml');

        $this->assertTrue($container->has('old_sound_rabbit_mq.default_consumer_consumer'));
        $definition = $container->getDefinition('old_sound_rabbit_mq.default_consumer_consumer');
        $this->assertEquals((string) $definition->getArgument(0), 'old_sound_rabbit_mq.connection.default');
        $this->assertEquals((string) $definition->getArgument(1), 'old_sound_rabbit_mq.channel.default_consumer');
        $this->assertEquals(array(
                array(
                    'setExchangeOptions',
                    array(
                        array(
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
                        )
                    )
                ),
                array(
                    'setQueueOptions',
                    array(
                        array(
                            'name'        => 'default_queue',
                            'passive'     => false,
                            'durable'     => true,
                            'exclusive'   => false,
                            'auto_delete' => false,
                            'nowait'      => false,
                            'arguments'   => null,
                            'ticket'      => null,
                            'routing_keys' => array(),
                        )
                    )
                ),
                array(
                    'setCallback',
                    array(array(new Reference('default.callback'), 'execute'))
                )
            ),
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

        $this->assertInternalType('array', $setQosParameters);
        $this->assertEquals(
            array(
                1024,
                1,
                true
            ),
            $setQosParameters
        );
    }

    public function testMultipleConsumerDefinition()
    {
        $container = $this->getContainer('test.yml');

        $this->assertTrue($container->has('old_sound_rabbit_mq.multi_test_consumer_multiple'));
        $definition = $container->getDefinition('old_sound_rabbit_mq.multi_test_consumer_multiple');
        $this->assertEquals(array(
                array(
                    'setExchangeOptions',
                    array(
                        array(
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
                        )
                    )
                ),
                array(
                    'setQueues',
                    array(
                        array(
                            'multi_test_1' => array(
                                'name'         => 'multi_test_1',
                                'passive'      => false,
                                'durable'      => true,
                                'exclusive'    => false,
                                'auto_delete'  => false,
                                'nowait'       => false,
                                'arguments'    => null,
                                'ticket'       => null,
                                'routing_keys' => array(),
                                'callback'     => array(new Reference('foo.multiple_test1.callback'), 'execute')
                            ),
                            'foo_bar_2' => array(
                                'name'         => 'foo_bar_2',
                                'passive'      => true,
                                'durable'      => false,
                                'exclusive'    => true,
                                'auto_delete'  => true,
                                'nowait'       => true,
                                'arguments'    => null,
                                'ticket'       => null,
                                'routing_keys' => array(
                                    'android.upload',
                                    'iphone.upload'
                                ),
                                'callback'     => array(new Reference('foo.multiple_test2.callback'), 'execute')
                            )
                        )
                    )
                ),
                array(
                    'setQueuesProvider',
                    array(
                        new Reference('foo.queues_provider')
                    )
                )
            ),
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
        $this->assertEquals(array(
                array(
                    'setExchangeOptions',
                    array(
                        array(
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
                        )
                    )
                ),
                array(
                    'setCallback',
                    array(array(new Reference('foo_anon.callback'), 'execute'))
                )
            ),
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
        $this->assertEquals(array(
                array(
                    'setExchangeOptions',
                    array(
                        array(
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
                        )
                    )
                ),
                array(
                    'setCallback',
                    array(array(new Reference('default_anon.callback'), 'execute'))
                )
            ),
            $definition->getMethodCalls()
        );
        $this->assertEquals('%old_sound_rabbit_mq.anon_consumer.class%', $definition->getClass());
    }

    public function testFooRpcClientDefinition()
    {
        $container = $this->getContainer('test.yml');

        $this->assertTrue($container->has('old_sound_rabbit_mq.foo_client_rpc'));
        $definition = $container->getDefinition('old_sound_rabbit_mq.foo_client_rpc');
        $this->assertEquals((string) $definition->getArgument(0), 'old_sound_rabbit_mq.connection.foo_connection');
        $this->assertEquals((string) $definition->getArgument(1), 'old_sound_rabbit_mq.channel.foo_client');
        $this->assertEquals(
            array(array('initClient', array(true))),
            $definition->getMethodCalls()
        );
        $this->assertEquals('%old_sound_rabbit_mq.rpc_client.class%', $definition->getClass());
    }

    public function testDefaultRpcClientDefinition()
    {
        $container = $this->getContainer('test.yml');

        $this->assertTrue($container->has('old_sound_rabbit_mq.default_client_rpc'));
        $definition = $container->getDefinition('old_sound_rabbit_mq.default_client_rpc');
        $this->assertEquals((string) $definition->getArgument(0), 'old_sound_rabbit_mq.connection.default');
        $this->assertEquals((string) $definition->getArgument(1), 'old_sound_rabbit_mq.channel.default_client');
        $this->assertEquals(
            array(array('initClient', array(true))),
            $definition->getMethodCalls()
        );
        $this->assertEquals('%old_sound_rabbit_mq.rpc_client.class%', $definition->getClass());
    }

    public function testFooRpcServerDefinition()
    {
        $container = $this->getContainer('test.yml');

        $this->assertTrue($container->has('old_sound_rabbit_mq.foo_server_server'));
        $definition = $container->getDefinition('old_sound_rabbit_mq.foo_server_server');
        $this->assertEquals((string) $definition->getArgument(0), 'old_sound_rabbit_mq.connection.foo_connection');
        $this->assertEquals((string) $definition->getArgument(1), 'old_sound_rabbit_mq.channel.foo_server');
        $this->assertEquals(array(
                array('initServer', array('foo_server')),
                array('setCallback', array(array(new Reference('foo_server.callback'), 'execute'))),
            ),
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
        $this->assertEquals(array(
                array('initServer', array('default_server')),
                array('setCallback', array(array(new Reference('default_server.callback'), 'execute'))),
            ),
            $definition->getMethodCalls()
        );
        $this->assertEquals('%old_sound_rabbit_mq.rpc_server.class%', $definition->getClass());
    }

    public function testHasCollectorWhenChannelsExist()
    {
        $container = $this->getContainer('collector.yml');

        $this->assertTrue($container->has('old_sound_rabbit_mq.data_collector'));
        $definition = $container->getDefinition('old_sound_rabbit_mq.data_collector');

        $this->assertEquals(array(
                new Reference('old_sound_rabbit_mq.channel.default_producer'),
                new Reference('old_sound_rabbit_mq.channel.default_consumer'),
            ),
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
        $this->assertEquals(array('name' => 'bar'), $options[0]['arguments']);

        $definition = $container->getDefinition('old_sound_rabbit_mq.consumer_consumer');
        $calls = $definition->getMethodCalls();
        $this->assertEquals('setExchangeOptions', $calls[0][0]);
        $options = $calls[0][1];
        $this->assertEquals(array('name' => 'bar'), $options[0]['arguments']);
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

    private function getContainer($file, $debug = false)
    {
        $container = new ContainerBuilder(new ParameterBag(array('kernel.debug' => $debug)));
        $container->registerExtension(new OldSoundRabbitMqExtension());

        $locator = new FileLocator(__DIR__.'/Fixtures');
        $loader = new YamlFileLoader($container, $locator);
        $loader->load($file);

        $container->getCompilerPassConfig()->setOptimizationPasses(array());
        $container->getCompilerPassConfig()->setRemovingPasses(array());
        $container->compile();

        return $container;
    }
}
