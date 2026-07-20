<?php

use OldSound\RabbitMqBundle\DependencyInjection\OldSoundRabbitMqExtension;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use OldSound\RabbitMqBundle\RabbitMq\ProducerInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\Reference;

function buildContainer(string $file, bool $debug = false): ContainerBuilder
{
    $container = new ContainerBuilder(new ParameterBag(['kernel.debug' => $debug]));
    $container->registerExtension(new OldSoundRabbitMqExtension());

    $locator = new FileLocator(__DIR__ . '/Fixtures');
    $loader  = new YamlFileLoader($container, $locator);
    $loader->load($file);

    $container->getCompilerPassConfig()->setOptimizationPasses([]);
    $container->getCompilerPassConfig()->setRemovingPasses([]);
    $container->compile();

    return $container;
}

function assertBindingMethodCallsEqual(Definition $definition, array $binding): void
{
    expect($definition->getMethodCalls())->toEqual([
        ['setArguments',           [$binding['arguments']]],
        ['setDestination',         [$binding['destination']]],
        ['setDestinationIsExchange', [$binding['destination_is_exchange']]],
        ['setExchange',            [$binding['exchange']]],
        ['isNowait',               [$binding['nowait']]],
        ['setRoutingKey',          [$binding['routing_key']]],
    ]);
}

test('foo connection definition', function () {
    $container  = buildContainer('test.yml');
    $definition = $container->getDefinition('old_sound_rabbit_mq.connection.foo_connection');
    $factory    = $container->getDefinition('old_sound_rabbit_mq.connection_factory.foo_connection');

    expect($container->has('old_sound_rabbit_mq.connection.foo_connection'))->toBeTrue();
    expect($container->has('old_sound_rabbit_mq.connection_factory.foo_connection'))->toBeTrue();
    expect($definition->getFactory())->toEqual(['old_sound_rabbit_mq.connection_factory.foo_connection', 'createConnection']);
    expect($factory->getArgument(1))->toEqual([
        'host' => 'foo_host', 'port' => 123, 'user' => 'foo_user', 'password' => 'foo_password',
        'vhost' => '/foo', 'lazy' => false, 'connection_timeout' => 3, 'read_write_timeout' => 3,
        'ssl_context' => [], 'keepalive' => false, 'heartbeat' => 0, 'use_socket' => false,
        'url' => '', 'hosts' => [], 'channel_rpc_timeout' => 0.0, 'login_method' => 'AMQPLAIN',
    ]);
    expect($definition->getClass())->toBe('%old_sound_rabbit_mq.connection.class%');
});

test('ssl connection definition', function () {
    $container  = buildContainer('test.yml');
    $definition = $container->getDefinition('old_sound_rabbit_mq.connection.ssl_connection');
    $factory    = $container->getDefinition('old_sound_rabbit_mq.connection_factory.ssl_connection');

    expect($container->has('old_sound_rabbit_mq.connection.ssl_connection'))->toBeTrue();
    expect($container->has('old_sound_rabbit_mq.connection_factory.ssl_connection'))->toBeTrue();
    expect($definition->getFactory())->toEqual(['old_sound_rabbit_mq.connection_factory.ssl_connection', 'createConnection']);
    expect($factory->getArgument(1))->toEqual([
        'host' => 'ssl_host', 'port' => 123, 'user' => 'ssl_user', 'password' => 'ssl_password',
        'vhost' => '/ssl', 'lazy' => false, 'connection_timeout' => 3, 'read_write_timeout' => 3,
        'ssl_context' => ['verify_peer' => false], 'keepalive' => false, 'heartbeat' => 0, 'use_socket' => false,
        'url' => '', 'hosts' => [], 'channel_rpc_timeout' => 0.0, 'login_method' => 'AMQPLAIN',
    ]);
    expect($definition->getClass())->toBe('%old_sound_rabbit_mq.connection.class%');
});

test('lazy connection definition', function () {
    $container  = buildContainer('test.yml');
    $definition = $container->getDefinition('old_sound_rabbit_mq.connection.lazy_connection');
    $factory    = $container->getDefinition('old_sound_rabbit_mq.connection_factory.lazy_connection');

    expect($container->has('old_sound_rabbit_mq.connection.lazy_connection'))->toBeTrue();
    expect($container->has('old_sound_rabbit_mq.connection_factory.lazy_connection'))->toBeTrue();
    expect($definition->getFactory())->toEqual(['old_sound_rabbit_mq.connection_factory.lazy_connection', 'createConnection']);
    expect($factory->getArgument(1))->toEqual([
        'host' => 'lazy_host', 'port' => 456, 'user' => 'lazy_user', 'password' => 'lazy_password',
        'vhost' => '/lazy', 'lazy' => true, 'connection_timeout' => 3, 'read_write_timeout' => 3,
        'ssl_context' => [], 'keepalive' => false, 'heartbeat' => 0, 'use_socket' => false,
        'url' => '', 'hosts' => [], 'channel_rpc_timeout' => 0.0, 'login_method' => 'AMQPLAIN',
    ]);
    expect($definition->getClass())->toBe('%old_sound_rabbit_mq.lazy.connection.class%');
});

test('default connection definition', function () {
    $container  = buildContainer('test.yml');
    $definition = $container->getDefinition('old_sound_rabbit_mq.connection.default');
    $factory    = $container->getDefinition('old_sound_rabbit_mq.connection_factory.default');

    expect($container->has('old_sound_rabbit_mq.connection.default'))->toBeTrue();
    expect($container->has('old_sound_rabbit_mq.connection_factory.default'))->toBeTrue();
    expect($definition->getFactory())->toEqual(['old_sound_rabbit_mq.connection_factory.default', 'createConnection']);
    expect($factory->getArgument(1))->toEqual([
        'host' => 'localhost', 'port' => 5672, 'user' => 'guest', 'password' => 'guest',
        'vhost' => '/', 'lazy' => false, 'connection_timeout' => 3, 'read_write_timeout' => 3,
        'ssl_context' => [], 'keepalive' => false, 'heartbeat' => 0, 'use_socket' => false,
        'url' => '', 'hosts' => [], 'channel_rpc_timeout' => 0.0, 'login_method' => 'AMQPLAIN',
    ]);
    expect($definition->getClass())->toBe('%old_sound_rabbit_mq.connection.class%');
});

test('socket connection definition', function () {
    $container = buildContainer('test.yml');

    expect($container->has('old_sound_rabbit_mq.connection.socket_connection'))->toBeTrue();
    expect($container->has('old_sound_rabbit_mq.connection_factory.socket_connection'))->toBeTrue();
    expect($container->getDefinition('old_sound_rabbit_mq.connection.socket_connection')->getClass())
        ->toBe('%old_sound_rabbit_mq.socket_connection.class%');
});

test('lazy socket connection definition', function () {
    $container = buildContainer('test.yml');

    expect($container->has('old_sound_rabbit_mq.connection.lazy_socket'))->toBeTrue();
    expect($container->has('old_sound_rabbit_mq.connection_factory.lazy_socket'))->toBeTrue();
    expect($container->getDefinition('old_sound_rabbit_mq.connection.lazy_socket')->getClass())
        ->toBe('%old_sound_rabbit_mq.lazy.socket_connection.class%');
});

test('cluster connection definition', function () {
    $container  = buildContainer('test.yml');
    $definition = $container->getDefinition('old_sound_rabbit_mq.connection.cluster_connection');
    $factory    = $container->getDefinition('old_sound_rabbit_mq.connection_factory.cluster_connection');

    expect($container->has('old_sound_rabbit_mq.connection.cluster_connection'))->toBeTrue();
    expect($container->has('old_sound_rabbit_mq.connection_factory.cluster_connection'))->toBeTrue();
    expect($definition->getFactory())->toEqual(['old_sound_rabbit_mq.connection_factory.cluster_connection', 'createConnection']);
    expect($factory->getArgument(1))->toEqual([
        'hosts' => [
            ['host' => 'cluster_host', 'port' => 111, 'user' => 'cluster_user', 'password' => 'cluster_password', 'vhost' => '/cluster', 'url' => ''],
            ['host' => 'localhost', 'port' => 5672, 'user' => 'guest', 'password' => 'guest', 'vhost' => '/', 'url' => 'amqp://cluster_url_host:cluster_url_pass@host:10000/cluster_url_vhost'],
        ],
        'host' => 'localhost', 'port' => 5672, 'user' => 'guest', 'password' => 'guest', 'vhost' => '/',
        'lazy' => false, 'connection_timeout' => 3, 'read_write_timeout' => 3, 'ssl_context' => [],
        'keepalive' => false, 'heartbeat' => 0, 'use_socket' => false, 'url' => '',
        'channel_rpc_timeout' => 0.0, 'login_method' => 'AMQPLAIN',
    ]);
    expect($definition->getClass())->toBe('%old_sound_rabbit_mq.connection.class%');
});

test('foo binding definition', function () {
    $container = buildContainer('test.yml');
    $binding   = [
        'arguments'               => null,
        'class'                   => '%old_sound_rabbit_mq.binding.class%',
        'connection'              => 'default',
        'exchange'                => 'foo',
        'destination'             => 'bar',
        'destination_is_exchange' => false,
        'nowait'                  => false,
        'routing_key'             => 'baz',
    ];
    ksort($binding);
    $name = sprintf('old_sound_rabbit_mq.binding.%s', md5(json_encode($binding)));

    expect($container->has($name))->toBeTrue();

    $definition = $container->getDefinition($name);
    expect((string) $definition->getArgument(0))->toBe('old_sound_rabbit_mq.connection.default');
    assertBindingMethodCallsEqual($definition, $binding);
});

test('moo binding definition', function () {
    $container = buildContainer('test.yml');
    $binding   = [
        'arguments'               => ['moo' => 'cow'],
        'class'                   => '%old_sound_rabbit_mq.binding.class%',
        'connection'              => 'default2',
        'exchange'                => 'moo',
        'destination'             => 'cow',
        'destination_is_exchange' => true,
        'nowait'                  => true,
        'routing_key'             => null,
    ];
    ksort($binding);
    $name = sprintf('old_sound_rabbit_mq.binding.%s', md5(json_encode($binding)));

    expect($container->has($name))->toBeTrue();

    $definition = $container->getDefinition($name);
    expect((string) $definition->getArgument(0))->toBe('old_sound_rabbit_mq.connection.default2');
    assertBindingMethodCallsEqual($definition, $binding);
});

test('foo producer definition', function () {
    $container  = buildContainer('test.yml');
    $definition = $container->getDefinition('old_sound_rabbit_mq.foo_producer_producer');

    expect($container->has('old_sound_rabbit_mq.foo_producer_producer'))->toBeTrue();
    expect((string) $definition->getArgument(0))->toBe('old_sound_rabbit_mq.connection.foo_connection');
    expect((string) $definition->getArgument(1))->toBe('old_sound_rabbit_mq.channel.foo_producer');
    expect($definition->getMethodCalls())->toEqual([
        ['setExchangeOptions', [['name' => 'foo_exchange', 'type' => 'direct', 'passive' => true, 'durable' => false, 'auto_delete' => true, 'internal' => true, 'nowait' => true, 'arguments' => null, 'ticket' => null, 'declare' => true]]],
        ['setQueueOptions', [['name' => '', 'declare' => false]]],
        ['setDefaultRoutingKey', ['']],
        ['setContentType', ['text/plain']],
        ['setDeliveryMode', [2]],
    ]);
    expect($definition->getClass())->toBe('My\Foo\Producer');
});

test('producer argument aliases', function () {
    $container = buildContainer('test.yml');

    if (!method_exists($container, 'registerAliasForArgument')) {
        return;
    }

    $expectedAliases = [
        ProducerInterface::class . ' $fooProducer'              => 'old_sound_rabbit_mq.foo_producer_producer',
        'My\Foo\Producer $fooProducer'                          => 'old_sound_rabbit_mq.foo_producer_producer',
        ProducerInterface::class . ' $fooProducerAliasedProducer' => 'old_sound_rabbit_mq.foo_producer_aliased_producer',
        'My\Foo\Producer $fooProducerAliasedProducer'           => 'old_sound_rabbit_mq.foo_producer_aliased_producer',
        ProducerInterface::class . ' $defaultProducer'          => 'old_sound_rabbit_mq.default_producer_producer',
        '%old_sound_rabbit_mq.producer.class% $defaultProducer' => 'old_sound_rabbit_mq.default_producer_producer',
    ];

    foreach ($expectedAliases as $id => $target) {
        expect($container->hasAlias($id))->toBeTrue("Container should have $id alias for autowiring support.");
        $alias = $container->getAlias($id);
        expect((string) $alias)->toBe($target, "Autowiring for $id should use $target.");
        expect($alias->isPublic())->toBeFalse("Autowiring alias for $id should be private.");
    }
});

test('aliased foo producer definition', function () {
    $container = buildContainer('test.yml');

    expect($container->has('old_sound_rabbit_mq.foo_producer_producer'))->toBeTrue();
    expect($container->has('foo_producer_alias'))->toBeTrue();
})->group('alias');

test('default producer definition', function () {
    $container  = buildContainer('test.yml');
    $definition = $container->getDefinition('old_sound_rabbit_mq.default_producer_producer');

    expect($container->has('old_sound_rabbit_mq.default_producer_producer'))->toBeTrue();
    expect((string) $definition->getArgument(0))->toBe('old_sound_rabbit_mq.connection.default');
    expect((string) $definition->getArgument(1))->toBe('old_sound_rabbit_mq.channel.default_producer');
    expect($definition->getMethodCalls())->toEqual([
        ['setExchangeOptions', [['name' => 'default_exchange', 'type' => 'direct', 'passive' => false, 'durable' => true, 'auto_delete' => false, 'internal' => false, 'nowait' => false, 'arguments' => null, 'ticket' => null, 'declare' => true]]],
        ['setQueueOptions', [['name' => '', 'declare' => false]]],
        ['setDefaultRoutingKey', ['']],
        ['setContentType', ['text/plain']],
        ['setDeliveryMode', [2]],
    ]);
    expect($definition->getClass())->toBe('%old_sound_rabbit_mq.producer.class%');
});

test('foo consumer definition', function () {
    $container  = buildContainer('test.yml');
    $definition = $container->getDefinition('old_sound_rabbit_mq.foo_consumer_consumer');

    expect($container->has('old_sound_rabbit_mq.foo_consumer_consumer'))->toBeTrue();
    expect((string) $definition->getArgument(0))->toBe('old_sound_rabbit_mq.connection.foo_connection');
    expect((string) $definition->getArgument(1))->toBe('old_sound_rabbit_mq.channel.foo_consumer');
    expect($definition->getMethodCalls())->toEqual([
        ['setExchangeOptions', [['name' => 'foo_exchange', 'type' => 'direct', 'passive' => true, 'durable' => false, 'auto_delete' => true, 'internal' => true, 'nowait' => true, 'arguments' => null, 'ticket' => null, 'declare' => true]]],
        ['setQueueOptions', [['name' => 'foo_queue', 'passive' => true, 'durable' => false, 'exclusive' => true, 'auto_delete' => true, 'nowait' => true, 'arguments' => null, 'ticket' => null, 'routing_keys' => ['android.#.upload', 'iphone.upload'], 'declare' => true]]],
        ['setCallback', [[new Reference('foo.callback'), 'execute']]],
        ['setTimeoutWait', [3]],
        ['setConsumerOptions', [['no_ack' => true]]],
    ]);
    expect($definition->getClass())->toBe('%old_sound_rabbit_mq.consumer.class%');
});

test('consumer argument aliases', function () {
    $container = buildContainer('test.yml');

    if (!method_exists($container, 'registerAliasForArgument')) {
        return;
    }

    $expectedAliases = [
        ConsumerInterface::class . ' $fooConsumer'                        => 'old_sound_rabbit_mq.foo_consumer_consumer',
        '%old_sound_rabbit_mq.consumer.class% $fooConsumer'               => 'old_sound_rabbit_mq.foo_consumer_consumer',
        ConsumerInterface::class . ' $defaultConsumer'                    => 'old_sound_rabbit_mq.default_consumer_consumer',
        '%old_sound_rabbit_mq.consumer.class% $defaultConsumer'           => 'old_sound_rabbit_mq.default_consumer_consumer',
        ConsumerInterface::class . ' $qosTestConsumer'                    => 'old_sound_rabbit_mq.qos_test_consumer_consumer',
        '%old_sound_rabbit_mq.consumer.class% $qosTestConsumer'           => 'old_sound_rabbit_mq.qos_test_consumer_consumer',
    ];

    foreach ($expectedAliases as $id => $target) {
        expect($container->hasAlias($id))->toBeTrue("Container should have $id alias for autowiring support.");
        $alias = $container->getAlias($id);
        expect((string) $alias)->toBe($target, "Autowiring for $id should use $target.");
        expect($alias->isPublic())->toBeFalse("Autowiring alias for $id should be private.");
    }
});

test('default consumer definition', function () {
    $container  = buildContainer('test.yml');
    $definition = $container->getDefinition('old_sound_rabbit_mq.default_consumer_consumer');

    expect($container->has('old_sound_rabbit_mq.default_consumer_consumer'))->toBeTrue();
    expect((string) $definition->getArgument(0))->toBe('old_sound_rabbit_mq.connection.default');
    expect((string) $definition->getArgument(1))->toBe('old_sound_rabbit_mq.channel.default_consumer');
    expect($definition->getMethodCalls())->toEqual([
        ['setExchangeOptions', [['name' => 'default_exchange', 'type' => 'direct', 'passive' => false, 'durable' => true, 'auto_delete' => false, 'internal' => false, 'nowait' => false, 'arguments' => null, 'ticket' => null, 'declare' => true]]],
        ['setQueueOptions', [['name' => 'default_queue', 'passive' => false, 'durable' => true, 'exclusive' => false, 'auto_delete' => false, 'nowait' => false, 'arguments' => null, 'ticket' => null, 'routing_keys' => [], 'declare' => true]]],
        ['setCallback', [[new Reference('default.callback'), 'execute']]],
    ]);
    expect($definition->getClass())->toBe('%old_sound_rabbit_mq.consumer.class%');
});

test('consumer with QOS options', function () {
    $container    = buildContainer('test.yml');
    $definition   = $container->getDefinition('old_sound_rabbit_mq.qos_test_consumer_consumer');
    $methodCalls  = $definition->getMethodCalls();

    $setQosParameters = null;
    foreach ($methodCalls as $methodCall) {
        if ($methodCall[0] === 'setQosOptions') {
            $setQosParameters = $methodCall[1];
        }
    }

    expect($setQosParameters)->toBeArray();
    expect($setQosParameters)->toEqual([1024, 1, true]);
});

test('multiple consumer definition', function () {
    $container  = buildContainer('test.yml');
    $definition = $container->getDefinition('old_sound_rabbit_mq.multi_test_consumer_multiple');

    expect($container->has('old_sound_rabbit_mq.multi_test_consumer_multiple'))->toBeTrue();
    expect($definition->getMethodCalls())->toEqual([
        ['setExchangeOptions', [['name' => 'foo_multiple_exchange', 'type' => 'direct', 'passive' => false, 'durable' => true, 'auto_delete' => false, 'internal' => false, 'nowait' => false, 'arguments' => null, 'ticket' => null, 'declare' => true]]],
        ['setQueues', [[
            'multi_test_1' => ['name' => 'multi_test_1', 'passive' => false, 'durable' => true, 'exclusive' => false, 'auto_delete' => false, 'nowait' => false, 'arguments' => null, 'ticket' => null, 'routing_keys' => [], 'callback' => [new Reference('foo.multiple_test1.callback'), 'execute'], 'declare' => true],
            'foo_bar_2'    => ['name' => 'foo_bar_2', 'passive' => true, 'durable' => false, 'exclusive' => true, 'auto_delete' => true, 'nowait' => true, 'arguments' => null, 'ticket' => null, 'routing_keys' => ['android.upload', 'iphone.upload'], 'callback' => [new Reference('foo.multiple_test2.callback'), 'execute'], 'declare' => true],
        ]]],
        ['setQueuesProvider', [new Reference('foo.queues_provider')]],
        ['setTimeoutWait', [3]],
        ['setConsumerOptions', [['no_ack' => true]]],
    ]);
});

test('dynamic consumer definition', function () {
    $container  = buildContainer('test.yml');
    $definition = $container->getDefinition('old_sound_rabbit_mq.foo_dyn_consumer_dynamic');

    expect($container->has('old_sound_rabbit_mq.foo_dyn_consumer_dynamic'))->toBeTrue();
    expect($container->has('old_sound_rabbit_mq.bar_dyn_consumer_dynamic'))->toBeTrue();
    expect($definition->getMethodCalls())->toEqual([
        ['setExchangeOptions', [['name' => 'foo_dynamic_exchange', 'type' => 'direct', 'passive' => false, 'durable' => true, 'auto_delete' => false, 'internal' => false, 'nowait' => false, 'declare' => true, 'arguments' => null, 'ticket' => null]]],
        ['setCallback', [[new Reference('foo.dynamic.callback'), 'execute']]],
        ['setQueueOptionsProvider', [new Reference('foo.dynamic.provider')]],
        ['setConsumerOptions', [['no_ack' => true]]],
    ]);
});

test('foo anon consumer definition', function () {
    $container  = buildContainer('test.yml');
    $definition = $container->getDefinition('old_sound_rabbit_mq.foo_anon_consumer_anon');

    expect($container->has('old_sound_rabbit_mq.foo_anon_consumer_anon'))->toBeTrue();
    expect((string) $definition->getArgument(0))->toBe('old_sound_rabbit_mq.connection.foo_connection');
    expect((string) $definition->getArgument(1))->toBe('old_sound_rabbit_mq.channel.foo_anon_consumer');
    expect($definition->getMethodCalls())->toEqual([
        ['setExchangeOptions', [['name' => 'foo_anon_exchange', 'type' => 'direct', 'passive' => true, 'durable' => false, 'auto_delete' => true, 'internal' => true, 'nowait' => true, 'arguments' => null, 'ticket' => null, 'declare' => true]]],
        ['setCallback', [[new Reference('foo_anon.callback'), 'execute']]],
        ['setConsumerOptions', [['no_ack' => true]]],
    ]);
    expect($definition->getClass())->toBe('%old_sound_rabbit_mq.anon_consumer.class%');
});

test('default anon consumer definition', function () {
    $container  = buildContainer('test.yml');
    $definition = $container->getDefinition('old_sound_rabbit_mq.default_anon_consumer_anon');

    expect($container->has('old_sound_rabbit_mq.default_anon_consumer_anon'))->toBeTrue();
    expect((string) $definition->getArgument(0))->toBe('old_sound_rabbit_mq.connection.default');
    expect((string) $definition->getArgument(1))->toBe('old_sound_rabbit_mq.channel.default_anon_consumer');
    expect($definition->getMethodCalls())->toEqual([
        ['setExchangeOptions', [['name' => 'default_anon_exchange', 'type' => 'direct', 'passive' => false, 'durable' => true, 'auto_delete' => false, 'internal' => false, 'nowait' => false, 'arguments' => null, 'ticket' => null, 'declare' => true]]],
        ['setCallback', [[new Reference('default_anon.callback'), 'execute']]],
    ]);
    expect($definition->getClass())->toBe('%old_sound_rabbit_mq.anon_consumer.class%');
});

test('foo rpc client definition', function () {
    $container  = buildContainer('rpc-clients.yml');
    $definition = $container->getDefinition('old_sound_rabbit_mq.foo_client_rpc');

    expect($container->has('old_sound_rabbit_mq.foo_client_rpc'))->toBeTrue();
    expect((string) $definition->getArgument(0))->toBe('old_sound_rabbit_mq.connection.foo_connection');
    expect((string) $definition->getArgument(1))->toBe('old_sound_rabbit_mq.channel.foo_client');
    expect($definition->getMethodCalls())->toEqual([
        ['initClient', [true]],
        ['setUnserializer', ['json_decode']],
        ['setDirectReplyTo', [true]],
    ]);
    expect($definition->getClass())->toBe('%old_sound_rabbit_mq.rpc_client.class%');
});

test('default rpc client definition is not lazy', function () {
    $container  = buildContainer('rpc-clients.yml');
    $definition = $container->getDefinition('old_sound_rabbit_mq.default_client_rpc');

    expect($container->has('old_sound_rabbit_mq.default_client_rpc'))->toBeTrue();
    expect((string) $definition->getArgument(0))->toBe('old_sound_rabbit_mq.connection.default');
    expect((string) $definition->getArgument(1))->toBe('old_sound_rabbit_mq.channel.default_client');
    expect($definition->getMethodCalls())->toEqual([
        ['initClient', [true]],
        ['setUnserializer', ['unserialize']],
        ['setDirectReplyTo', [false]],
    ]);
    expect($definition->isLazy())->toBeFalse();
    expect($definition->getClass())->toBe('%old_sound_rabbit_mq.rpc_client.class%');
});

test('lazy rpc client definition is lazy', function () {
    $container  = buildContainer('rpc-clients.yml');
    $definition = $container->getDefinition('old_sound_rabbit_mq.lazy_client_rpc');

    expect($container->has('old_sound_rabbit_mq.lazy_client_rpc'))->toBeTrue();
    expect((string) $definition->getArgument(0))->toBe('old_sound_rabbit_mq.connection.default');
    expect((string) $definition->getArgument(1))->toBe('old_sound_rabbit_mq.channel.lazy_client');
    expect($definition->getMethodCalls())->toEqual([
        ['initClient', [true]],
        ['setUnserializer', ['unserialize']],
        ['setDirectReplyTo', [false]],
    ]);
    expect($definition->isLazy())->toBeTrue();
    expect($definition->getClass())->toBe('%old_sound_rabbit_mq.rpc_client.class%');
});

test('foo rpc server definition', function () {
    $container  = buildContainer('test.yml');
    $definition = $container->getDefinition('old_sound_rabbit_mq.foo_server_server');

    expect($container->has('old_sound_rabbit_mq.foo_server_server'))->toBeTrue();
    expect((string) $definition->getArgument(0))->toBe('old_sound_rabbit_mq.connection.foo_connection');
    expect((string) $definition->getArgument(1))->toBe('old_sound_rabbit_mq.channel.foo_server');
    expect($definition->getMethodCalls())->toEqual([
        ['initServer', ['foo_server']],
        ['setCallback', [[new Reference('foo_server.callback'), 'execute']]],
        ['setSerializer', ['json_encode']],
    ]);
    expect($definition->getClass())->toBe('%old_sound_rabbit_mq.rpc_server.class%');
});

test('default rpc server definition', function () {
    $container  = buildContainer('test.yml');
    $definition = $container->getDefinition('old_sound_rabbit_mq.default_server_server');

    expect($container->has('old_sound_rabbit_mq.default_server_server'))->toBeTrue();
    expect((string) $definition->getArgument(0))->toBe('old_sound_rabbit_mq.connection.default');
    expect((string) $definition->getArgument(1))->toBe('old_sound_rabbit_mq.channel.default_server');
    expect($definition->getMethodCalls())->toEqual([
        ['initServer', ['default_server']],
        ['setCallback', [[new Reference('default_server.callback'), 'execute']]],
        ['setSerializer', ['serialize']],
    ]);
    expect($definition->getClass())->toBe('%old_sound_rabbit_mq.rpc_server.class%');
});

test('rpc server with queue options definition', function () {
    $container  = buildContainer('test.yml');
    $definition = $container->getDefinition('old_sound_rabbit_mq.server_with_queue_options_server');

    expect($container->has('old_sound_rabbit_mq.server_with_queue_options_server'))->toBeTrue();
    expect((string) $definition->getArgument(0))->toBe('old_sound_rabbit_mq.connection.default');
    expect((string) $definition->getArgument(1))->toBe('old_sound_rabbit_mq.channel.server_with_queue_options');
    expect($definition->getMethodCalls())->toEqual([
        ['initServer', ['server_with_queue_options']],
        ['setCallback', [[new Reference('server_with_queue_options.callback'), 'execute']]],
        ['setQueueOptions', [[
            'name' => 'server_with_queue_options-queue', 'passive' => false, 'durable' => true,
            'exclusive' => false, 'auto_delete' => false, 'nowait' => false, 'arguments' => null,
            'ticket' => null, 'routing_keys' => [], 'declare' => true,
        ]]],
        ['setSerializer', ['serialize']],
    ]);
    expect($definition->getClass())->toBe('%old_sound_rabbit_mq.rpc_server.class%');
});

test('rpc server with timeouts definition', function () {
    $container  = buildContainer('test.yml');
    $definition = $container->getDefinition('old_sound_rabbit_mq.server_with_timeouts_server');

    expect($container->has('old_sound_rabbit_mq.server_with_timeouts_server'))->toBeTrue();
    expect((string) $definition->getArgument(0))->toBe('old_sound_rabbit_mq.connection.default');
    expect((string) $definition->getArgument(1))->toBe('old_sound_rabbit_mq.channel.server_with_timeouts');
    expect($definition->getMethodCalls())->toEqual([
        ['initServer', ['server_with_timeouts']],
        ['setCallback', [[new Reference('server_with_timeouts.callback'), 'execute']]],
        ['setSerializer', ['serialize']],
        ['setIdleTimeout', [60]],
        ['setIdleTimeoutExitCode', [2]],
        ['setTimeoutWait', [10]],
        ['setGracefulMaxExecutionDateTimeFromSecondsInTheFuture', [1800]],
        ['setGracefulMaxExecutionTimeoutExitCode', [10]],
    ]);
    expect($definition->getClass())->toBe('%old_sound_rabbit_mq.rpc_server.class%');
});

test('rpc server with exchange options definition', function () {
    $container  = buildContainer('test.yml');
    $definition = $container->getDefinition('old_sound_rabbit_mq.server_with_exchange_options_server');

    expect($container->has('old_sound_rabbit_mq.server_with_exchange_options_server'))->toBeTrue();
    expect((string) $definition->getArgument(0))->toBe('old_sound_rabbit_mq.connection.default');
    expect((string) $definition->getArgument(1))->toBe('old_sound_rabbit_mq.channel.server_with_exchange_options');
    expect($definition->getMethodCalls())->toEqual([
        ['initServer', ['server_with_exchange_options']],
        ['setCallback', [[new Reference('server_with_exchange_options.callback'), 'execute']]],
        ['setExchangeOptions', [[
            'name' => 'exchange', 'type' => 'topic', 'passive' => false, 'durable' => true,
            'auto_delete' => false, 'internal' => null, 'nowait' => false, 'declare' => true,
            'arguments' => null, 'ticket' => null,
        ]]],
        ['setSerializer', ['serialize']],
    ]);
    expect($definition->getClass())->toBe('%old_sound_rabbit_mq.rpc_server.class%');
});

test('data collector is registered when channels exist', function () {
    $container  = buildContainer('collector.yml');
    $definition = $container->getDefinition('old_sound_rabbit_mq.data_collector');

    expect($container->has('old_sound_rabbit_mq.data_collector'))->toBeTrue();
    expect($definition->getArgument(0))->toEqual([
        new Reference('old_sound_rabbit_mq.channel.default_producer'),
        new Reference('old_sound_rabbit_mq.channel.default_consumer'),
    ]);
});

test('data collector is not registered when no channels exist', function () {
    $container = buildContainer('no_collector.yml');

    expect($container->has('old_sound_rabbit_mq.data_collector'))->toBeFalse();
});

test('data collector can be disabled', function () {
    $container = buildContainer('collector_disabled.yml');

    expect($container->has('old_sound_rabbit_mq.data_collector'))->toBeFalse();
});

test('exchange arguments are converted to array', function () {
    $container = buildContainer('exchange_arguments.yml');

    $producerDefinition = $container->getDefinition('old_sound_rabbit_mq.producer_producer');
    $producerCalls      = $producerDefinition->getMethodCalls();
    expect($producerCalls[0][0])->toBe('setExchangeOptions');
    expect($producerCalls[0][1][0]['arguments'])->toEqual(['name' => 'bar']);

    $consumerDefinition = $container->getDefinition('old_sound_rabbit_mq.consumer_consumer');
    $consumerCalls      = $consumerDefinition->getMethodCalls();
    expect($consumerCalls[0][0])->toBe('setExchangeOptions');
    expect($consumerCalls[0][1][0]['arguments'])->toEqual(['name' => 'bar']);
});

test('producer without explicit exchange options connects to AMQP default', function () {
    $container  = buildContainer('no_exchange_options.yml');
    $definition = $container->getDefinition('old_sound_rabbit_mq.producer_producer');
    $calls      = $definition->getMethodCalls();

    expect($calls[0][0])->toBe('setExchangeOptions');
    expect($calls[0][1][0]['name'])->toBe('');
    expect($calls[0][1][0]['type'])->toBe('direct');
    expect($calls[0][1][0]['declare'])->toBeFalse();
    expect($calls[0][1][0]['passive'])->toBeTrue();
});

test('producers are tagged for monolog logger when enable_logger is configured', function () {
    $container  = buildContainer('config_with_enable_logger.yml');
    $definition = $container->getDefinition('old_sound_rabbit_mq.default_consumer_consumer');

    expect($definition->hasTag('monolog.logger'))->toBeTrue();
});
