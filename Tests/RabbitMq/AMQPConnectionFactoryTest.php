<?php

namespace OldSound\RabbitMqBundle\Tests\RabbitMq;

use OldSound\RabbitMqBundle\Provider\ConnectionParametersProviderInterface;
use OldSound\RabbitMqBundle\RabbitMq\AMQPConnectionFactory;
use OldSound\RabbitMqBundle\Tests\RabbitMq\Fixtures\AMQPConnection;
use OldSound\RabbitMqBundle\Tests\RabbitMq\Fixtures\AMQPSocketConnection;
use OldSound\RabbitMqBundle\Tests\RabbitMq\Fixtures\AMQPSSLConnection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AMQPConnectionFactoryTest extends TestCase
{
    public function testDefaultValues()
    {
        $factory = new AMQPConnectionFactory(
            AMQPConnection::class,
            []
        );

        /** @var AMQPConnection $instance */
        $instance = $factory->createConnection();
        $this->assertInstanceOf(AMQPConnection::class, $instance);
        $this->assertEquals([
            'localhost', // host
            5672,        // port
            'guest',     // user
            'guest',     // password
            '/',         // vhost
            false,       // insist
            "AMQPLAIN",  // login method
            null,        // login response
            "en_US",     // locale
            3,           // connection timeout
            3,           // read write timeout
            null,        // context
            false,       // keepalive
            0,           // heartbeat
            0.0,         //channel_rpc_timeout
        ], $instance->constructParams);
    }

    public function testSocketConnection()
    {
        $factory = new AMQPConnectionFactory(
            AMQPSocketConnection::class,
            []
        );

        /** @var AMQPSocketConnection $instance */
        $instance = $factory->createConnection();
        $this->assertInstanceOf(AMQPSocketConnection::class, $instance);
        $this->assertEquals([
            'localhost', // host
            5672,        // port
            'guest',     // user
            'guest',     // password
            '/',         // vhost
            false,       // insist
            "AMQPLAIN",  // login method
            null,        // login response
            "en_US",     // locale
            3,           // read_timeout
            false,       // keepalive
            3,           // write_timeout
            0,           // heartbeat
            0.0,         //channel_rpc_timeout
        ], $instance->constructParams);
    }

    public function testSocketConnectionWithParams()
    {
        $factory = new AMQPConnectionFactory(
            AMQPSocketConnection::class,
            [
                'read_timeout' => 31,
                'write_timeout' => 32,
            ]
        );

        /** @var AMQPSocketConnection $instance */
        $instance = $factory->createConnection();
        $this->assertInstanceOf(AMQPSocketConnection::class, $instance);
        $this->assertEquals([
            'localhost', // host
            5672,        // port
            'guest',     // user
            'guest',     // password
            '/',         // vhost
            false,       // insist
            "AMQPLAIN",  // login method
            null,        // login response
            "en_US",     // locale
            31,           // read_timeout
            false,       // keepalive
            32,           // write_timeout
            0,           // heartbeat
            0.0,         //channel_rpc_timeout
        ], $instance->constructParams);
    }

    public function testStandardConnectionParameters()
    {
        $factory = new AMQPConnectionFactory(
            AMQPConnection::class,
            [
                'host' => 'foo_host',
                'port' => 123,
                'user' => 'foo_user',
                'password' => 'foo_password',
                'vhost' => '/vhost',
            ]
        );

        /** @var AMQPConnection $instance */
        $instance = $factory->createConnection();
        $this->assertInstanceOf(AMQPConnection::class, $instance);
        $this->assertEquals([
            'foo_host',  // host
            123,         // port
            'foo_user',  // user
            'foo_password', // password
            '/vhost',    // vhost
            false,       // insist
            "AMQPLAIN",  // login method
            null,        // login response
            "en_US",     // locale
            3,           // connection timeout
            3,           // read write timeout
            null,        // context
            false,       // keepalive
            0,           // heartbeat
            0.0,         //channel_rpc_timeout
        ], $instance->constructParams);
    }

    public function testSetConnectionParametersWithUrl()
    {
        $factory = new AMQPConnectionFactory(
            AMQPConnection::class,
            [
                'url' => 'amqp://bar_user:bar_password@bar_host:321/whost?keepalive=1&connection_timeout=6&read_write_timeout=6',
                'host' => 'foo_host',
                'port' => 123,
                'user' => 'foo_user',
                'password' => 'foo_password',
                'vhost' => '/vhost',
            ]
        );

        /** @var AMQPConnection $instance */
        $instance = $factory->createConnection();
        $this->assertInstanceOf(AMQPConnection::class, $instance);
        $this->assertEquals([
            'bar_host',  // host
            321,         // port
            'bar_user',  // user
            'bar_password', // password
            'whost',     // vhost
            false,       // insist
            "AMQPLAIN",  // login method
            null,        // login response
            "en_US",     // locale
            6,           // connection timeout
            6,           // read write timeout
            null,        // context
            true,        // keepalive
            0,           // heartbeat
            0.0,         //channel_rpc_timeout
        ], $instance->constructParams);
    }

    public function testSetConnectionParametersWithUrlEncoded()
    {
        $factory = new AMQPConnectionFactory(
            AMQPConnection::class,
            [
                'url' => 'amqp://user%61:%61pass@ho%61st:10000/v%2fhost?keepalive=1&connection_timeout=6&read_write_timeout=6',
            ]
        );

        /** @var AMQPConnection $instance */
        $instance = $factory->createConnection();
        $this->assertInstanceOf(AMQPConnection::class, $instance);
        $this->assertEquals([
            'hoast',     // host
            10000,       // port
            'usera',     // user
            'apass',     // password
            'v/host',    // vhost
            false,       // insist
            "AMQPLAIN",  // login method
            null,        // login response
            "en_US",     // locale
            6,           // connection timeout
            6,           // read write timeout
            null,        // context
            true,        // keepalive
            0,           // heartbeat
            0.0,         //channel_rpc_timeout
        ], $instance->constructParams);
    }

    public function testSetConnectionParametersWithUrlWithoutVhost()
    {
        $factory = new AMQPConnectionFactory(
            AMQPConnection::class,
            [
                'url' => 'amqp://user:pass@host:321/?keepalive=1&connection_timeout=6&read_write_timeout=6',
            ]
        );

        /** @var AMQPConnection $instance */
        $instance = $factory->createConnection();
        $this->assertInstanceOf(AMQPConnection::class, $instance);
        $this->assertEquals([
            'host',     // host
            321,        // port
            'user',     // user
            'pass',     // password
            '',         // vhost
            false,      // insist
            "AMQPLAIN", // login method
            null,       // login response
            "en_US",    // locale
            6,          // connection timeout
            6,          // read write timeout
            null,       // context
            true,       // keepalive
            0,          // heartbeat
            0.0,        //channel_rpc_timeout
        ], $instance->constructParams);
    }

    public function testSSLConnectionParameters()
    {
        $factory = new AMQPConnectionFactory(
            AMQPSSLConnection::class,
            [
                'host' => 'ssl_host',
                'port' => 123,
                'user' => 'ssl_user',
                'password' => 'ssl_password',
                'vhost' => '/ssl',
                'ssl_context' => [
                    'verify_peer' => false,
                ],
            ]
        );

        /** @var AMQPSSLConnection $instance */
        $instance = $factory->createConnection();
        $this->assertInstanceOf(AMQPSSLConnection::class, $instance);
        $this->assertArrayHasKey(6, $instance->constructParams);
        $options = $instance->constructParams[6];
        $this->assertArrayHasKey('ssl_context', $options);
        $this->assertArrayHasKey('context', $options);
        $context = $options['context'];
        // unset to check whole array at once later
        $instance->constructParams[6]['ssl_context'] = null;
        $instance->constructParams[6]['context'] = null;
        $this->assertIsResource($context);
        $this->assertEquals('stream-context', get_resource_type($context));
        $options = stream_context_get_options($context);
        $this->assertEquals(['ssl' => ['verify_peer' => false]], $options);
        $this->assertEquals([
            'ssl_host',     // host
            123,            // port
            'ssl_user',     // user
            'ssl_password', // password
            '/ssl',         // vhost,
            [],             // ssl_options
            [               // options
                'url' => '',
                'host' => 'ssl_host',
                'port' => 123,
                'user' => 'ssl_user',
                'password' => 'ssl_password',
                'vhost' => '/ssl',
                'connection_timeout' => 3,
                'read_write_timeout' => 3,
                'ssl_context' => null, // context checked earlier
                'context' => null, // context checked earlier
                'keepalive' => false,
                'heartbeat' => 0,
            ],
        ], $instance->constructParams);
    }

    public function testClusterConnectionParametersWithoutRootConnectionKeys()
    {
        $factory = new AMQPConnectionFactory(
            AMQPConnection::class,
            [
                'hosts' => [
                    [
                        'host' => 'cluster_host',
                        'port' => 123,
                        'user' => 'cluster_user',
                        'password' => 'cluster_password',
                        'vhost' => '/cluster_vhost',
                    ],
                    [
                        'url' => 'amqp://user:pass@host:321/vhost',
                    ],
                ],
            ]
        );

        /** @var AMQPConnection $instance */
        $instance = $factory->createConnection();
        $this->assertInstanceOf(AMQPConnection::class, $instance);
        $this->assertEquals([
            'cluster_host',     // host
            123,                // port
            'cluster_user',     // user
            'cluster_password', // password
            '/cluster_vhost',   // vhost
            false,              // insist
            "AMQPLAIN",         // login method
            null,               // login response
            "en_US",            // locale
            3,                  // connection timeout
            3,                  // read write timeout
            null,               // context
            false,              // keepalive
            0,                  // heartbeat
            0.0,         //channel_rpc_timeout
        ], $instance->constructParams);

        $this->assertEquals(
            [
                [
                    [
                        'host' => 'cluster_host',
                        'port' => 123,
                        'user' => 'cluster_user',
                        'password' => 'cluster_password',
                        'vhost' => '/cluster_vhost',
                    ],
                    [
                        'host' => 'host',
                        'port' => 321,
                        'user' => 'user',
                        'password' => 'pass',
                        'vhost' => 'vhost',
                    ],
                ],
                [
                    'url'                => '',
                    'host'               => 'localhost',
                    'port'               => 5672,
                    'user'               => 'guest',
                    'password'           => 'guest',
                    'vhost'              => '/',
                    'connection_timeout' => 3,
                    'read_write_timeout' => 3,
                    'ssl_context'        => null,
                    'keepalive'          => false,
                    'heartbeat'          => 0,
                ],
            ],
            $instance::$createConnectionParams
        );
    }

    public function testClusterConnectionParametersWithRootConnectionKeys()
    {
        $factory = new AMQPConnectionFactory(
            AMQPConnection::class,
            [
                'host' => 'host',
                'port' => 123,
                'user' => 'user',
                'password' => 'password',
                'vhost' => '/vhost',
                'hosts' => [
                    [
                        'host' => 'cluster_host',
                        'port' => 123,
                        'user' => 'cluster_user',
                        'password' => 'cluster_password',
                        'vhost' => '/vhost',
                    ],
                ],
            ]
        );

        /** @var AMQPConnection $instance */
        $instance = $factory->createConnection();
        $this->assertInstanceOf(AMQPConnection::class, $instance);
        $this->assertEquals([
            'cluster_host',  // host
            123,         // port
            'cluster_user',  // user
            'cluster_password', // password
            '/vhost',    // vhost
            false,       // insist
            "AMQPLAIN",  // login method
            null,        // login response
            "en_US",     // locale
            3,           // connection timeout
            3,           // read write timeout
            null,        // context
            false,       // keepalive
            0,           // heartbeat
            0.0,         //channel_rpc_timeout
        ], $instance->constructParams);

        $this->assertEquals(
            [
                [
                    [
                        'host' => 'cluster_host',
                        'port' => 123,
                        'user' => 'cluster_user',
                        'password' => 'cluster_password',
                        'vhost' => '/vhost',
                    ],
                ],
                [
                    'url'                => '',
                    'host'               => 'host',
                    'port'               => 123,
                    'user'               => 'user',
                    'password'           => 'password',
                    'vhost'              => '/vhost',
                    'connection_timeout' => 3,
                    'read_write_timeout' => 3,
                    'ssl_context'        => null,
                    'keepalive'          => false,
                    'heartbeat'          => 0,
                ],
            ],
            $instance::$createConnectionParams
        );
    }

    public function testSSLClusterConnectionParameters()
    {
        $factory = new AMQPConnectionFactory(
            AMQPSSLConnection::class,
            [
                'hosts' => [
                    [
                        'host' => 'ssl_cluster_host',
                        'port' => 123,
                        'user' => 'ssl_cluster_user',
                        'password' => 'ssl_cluster_password',
                        'vhost' => '/ssl_cluster_vhost',
                    ],
                    [
                        'url' => 'amqp://user:pass@host:321/vhost',
                    ],
                ],
                'ssl_context' => [
                    'verify_peer' => false,
                ],
            ]
        );

        /** @var AMQPSSLConnection $instance */
        $instance = $factory->createConnection();
        $this->assertInstanceOf(AMQPSSLConnection::class, $instance);

        $this->assertArrayHasKey(6, $instance->constructParams);
        $options = $instance->constructParams[6];
        $this->assertArrayHasKey('ssl_context', $options);
        $this->assertArrayHasKey('context', $options);
        $context = $options['context'];
        // unset to check whole array at once later
        $instance->constructParams[6]['ssl_context'] = null;
        $instance->constructParams[6]['context'] = null;
        $this->assertIsResource($context);
        $this->assertEquals('stream-context', get_resource_type($context));
        $options = stream_context_get_options($context);
        $this->assertEquals(['ssl' => ['verify_peer' => false]], $options);

        $this->assertArrayHasKey(1, $instance::$createConnectionParams);
        $createConnectionOptions = $instance::$createConnectionParams[1];
        $this->assertArrayHasKey('ssl_context', $createConnectionOptions);
        $createConnectionContext = $createConnectionOptions['context'];
        // unset to check whole array at once later
        $instance::$createConnectionParams[1]['ssl_context'] = null;
        $instance::$createConnectionParams[1]['context'] = null;
        $this->assertIsResource($createConnectionContext);
        $this->assertEquals('stream-context', get_resource_type($createConnectionContext));
        $createConnectionOptions = stream_context_get_options($createConnectionContext);
        $this->assertEquals(['ssl' => ['verify_peer' => false]], $createConnectionOptions);

        $this->assertEquals([
            'ssl_cluster_host',     // host
            123,                   // port
            'ssl_cluster_user',     // user
            'ssl_cluster_password', // password
            '/ssl_cluster_vhost',   // vhost,
            [],                     // ssl_options
            [                       // options
                'url' => '',
                'host' => 'localhost',
                'port' => 5672,
                'user' => 'guest',
                'password' => 'guest',
                'vhost' => '/',
                'connection_timeout' => 3,
                'read_write_timeout' => 3,
                'ssl_context' => null,
                'context' => null,
                'keepalive' => false,
                'heartbeat' => 0,
            ],
        ], $instance->constructParams);

        $this->assertEquals(
            [
                [
                    [
                        'host' => 'ssl_cluster_host',
                        'port' => 123,
                        'user' => 'ssl_cluster_user',
                        'password' => 'ssl_cluster_password',
                        'vhost' => '/ssl_cluster_vhost',
                    ],
                    [
                        'host' => 'host',
                        'port' => 321,
                        'user' => 'user',
                        'password' => 'pass',
                        'vhost' => 'vhost',
                    ],
                ],
                [
                    'url'                => '',
                    'host'               => 'localhost',
                    'port'               => 5672,
                    'user'               => 'guest',
                    'password'           => 'guest',
                    'vhost'              => '/',
                    'connection_timeout' => 3,
                    'read_write_timeout' => 3,
                    'ssl_context'        => null, // context checked earlier
                    'context'        => null, // context checked earlier
                    'keepalive'          => false,
                    'heartbeat'          => 0,
                ],
            ],
            $instance::$createConnectionParams
        );
    }

    public function testSocketClusterConnectionParameters()
    {
        $factory = new AMQPConnectionFactory(
            AMQPSocketConnection::class,
            [
                'hosts' => [
                    [
                        'host' => 'cluster_host',
                        'port' => 123,
                        'user' => 'cluster_user',
                        'password' => 'cluster_password',
                        'vhost' => '/cluster_vhost',
                    ],
                    [
                        'url' => 'amqp://user:pass@host:321/vhost',
                    ],
                ],
            ]
        );

        /** @var AMQPSocketConnection $instance */
        $instance = $factory->createConnection();
        $this->assertInstanceOf(AMQPSocketConnection::class, $instance);
        $this->assertEquals([
            'cluster_host',     // host
            123,                // port
            'cluster_user',     // user
            'cluster_password', // password
            '/cluster_vhost',   // vhost
            false,              // insist
            "AMQPLAIN",         // login method
            null,               // login response
            "en_US",            // locale
            3,                  // read_timeout
            false,              // keepalive
            3,                  // write_timeout
            0,                  // heartbeat
            0.0,         //channel_rpc_timeout
        ], $instance->constructParams);

        $this->assertEquals(
            [
                [
                    [
                        'host' => 'cluster_host',
                        'port' => 123,
                        'user' => 'cluster_user',
                        'password' => 'cluster_password',
                        'vhost' => '/cluster_vhost',
                    ],
                    [
                        'host' => 'host',
                        'port' => 321,
                        'user' => 'user',
                        'password' => 'pass',
                        'vhost' => 'vhost',
                    ],
                ],
                [
                    'url'                => '',
                    'host'               => 'localhost',
                    'port'               => 5672,
                    'user'               => 'guest',
                    'password'           => 'guest',
                    'vhost'              => '/',
                    'ssl_context'        => null,
                    'keepalive'          => false,
                    'heartbeat'          => 0,
                    'connection_timeout' => 3,
                    'read_write_timeout' => 3,
                    'read_timeout'       => 3,
                    'write_timeout'      => 3,
                ],
            ],
            $instance::$createConnectionParams
        );
    }

    public function testConnectionsParametersProviderWithConstructorArgs()
    {
        $connectionParametersProvider = $this->prepareConnectionParametersProvider();
        $connectionParametersProvider->expects($this->once())
            ->method('getConnectionParameters')
            ->will($this->returnValue(
                [
                    'constructor_args' => [1,2,3,4],
                ]
            ));
        $factory = new AMQPConnectionFactory(
            AMQPConnection::class,
            [],
            $connectionParametersProvider
        );

        /** @var AMQPConnection $instance */
        $instance = $factory->createConnection();
        $this->assertInstanceOf(AMQPConnection::class, $instance);
        $this->assertEquals([1,2,3,4], $instance->constructParams);
    }

    public function testConnectionsParametersProvider()
    {
        $connectionParametersProvider = $this->prepareConnectionParametersProvider();
        $connectionParametersProvider->expects($this->once())
            ->method('getConnectionParameters')
            ->will($this->returnValue(
                [
                    'host' => '1.2.3.4',
                    'port' => 5678,
                    'user' => 'admin',
                    'password' => 'admin',
                    'vhost' => 'foo',
                ]
            ));
        $factory = new AMQPConnectionFactory(
            AMQPConnection::class,
            [],
            $connectionParametersProvider
        );

        /** @var AMQPConnection $instance */
        $instance = $factory->createConnection();
        $this->assertInstanceOf(AMQPConnection::class, $instance);
        $this->assertEquals([
            '1.2.3.4',   // host
            5678,        // port
            'admin',     // user
            'admin',     // password
            'foo',       // vhost
            false,       // insist
            "AMQPLAIN",  // login method
            null,        // login response
            "en_US",     // locale
            3,           // connection timeout
            3,           // read write timeout
            null,        // context
            false,       // keepalive
            0,           // heartbeat
            0.0,         //channel_rpc_timeout
        ], $instance->constructParams);
    }

    /**
     * Preparing ConnectionParametersProviderInterface instance
     *
     * @return ConnectionParametersProviderInterface|MockObject
     */
    private function prepareConnectionParametersProvider()
    {
        return $this->getMockBuilder(ConnectionParametersProviderInterface::class)
            ->getMock();
    }
}
