<?php

namespace OldSound\RabbitMqBundle\Tests\RabbitMq;

use OldSound\RabbitMqBundle\Provider\ConnectionParametersProviderInterface;
use OldSound\RabbitMqBundle\RabbitMq\AMQPConnectionFactory;
use OldSound\RabbitMqBundle\Tests\RabbitMq\Fixtures\AMQPConnection;

class AMQPConnectionFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testDefaultValues()
    {
        $factory = new AMQPConnectionFactory(
            'OldSound\RabbitMqBundle\Tests\RabbitMq\Fixtures\AMQPConnection',
            array()
        );

        /** @var AMQPConnection $instance */
        $instance = $factory->createConnection();
        $this->assertInstanceOf('OldSound\RabbitMqBundle\Tests\RabbitMq\Fixtures\AMQPConnection', $instance);
        $this->assertEquals(array(
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
        ), $instance->constructParams);
    }

    public function testStandardConnectionParameters()
    {
        $factory = new AMQPConnectionFactory(
            'OldSound\RabbitMqBundle\Tests\RabbitMq\Fixtures\AMQPConnection',
            array(
                'host' => 'foo_host',
                'port' => 123,
                'user' => 'foo_user',
                'password' => 'foo_password',
                'vhost' => '/vhost',
            )
        );

        /** @var AMQPConnection $instance */
        $instance = $factory->createConnection();
        $this->assertInstanceOf('OldSound\RabbitMqBundle\Tests\RabbitMq\Fixtures\AMQPConnection', $instance);
        $this->assertEquals(array(
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
        ), $instance->constructParams);
    }

    public function testSetConnectionParametersWithUrl()
    {
        $factory = new AMQPConnectionFactory(
            'OldSound\RabbitMqBundle\Tests\RabbitMq\Fixtures\AMQPConnection',
            array(
                'url' => 'amqp://bar_user:bar_password@bar_host:321/whost?keepalive=1&connection_timeout=6&read_write_timeout=6',
                'host' => 'foo_host',
                'port' => 123,
                'user' => 'foo_user',
                'password' => 'foo_password',
                'vhost' => '/vhost',
            )
        );

        /** @var AMQPConnection $instance */
        $instance = $factory->createConnection();
        $this->assertInstanceOf('OldSound\RabbitMqBundle\Tests\RabbitMq\Fixtures\AMQPConnection', $instance);
        $this->assertEquals(array(
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
        ), $instance->constructParams);
    }

    public function testSetConnectionParametersWithUrlEncoded()
    {
        $factory = new AMQPConnectionFactory(
            'OldSound\RabbitMqBundle\Tests\RabbitMq\Fixtures\AMQPConnection',
            array(
                'url' => 'amqp://user%61:%61pass@ho%61st:10000/v%2fhost?keepalive=1&connection_timeout=6&read_write_timeout=6',
            )
        );

        /** @var AMQPConnection $instance */
        $instance = $factory->createConnection();
        $this->assertInstanceOf('OldSound\RabbitMqBundle\Tests\RabbitMq\Fixtures\AMQPConnection', $instance);
        $this->assertEquals(array(
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
        ), $instance->constructParams);
    }

    public function testSetConnectionParametersWithUrlWithoutVhost()
    {
        $factory = new AMQPConnectionFactory(
            'OldSound\RabbitMqBundle\Tests\RabbitMq\Fixtures\AMQPConnection',
            array(
                'url' => 'amqp://user:pass@host:321/?keepalive=1&connection_timeout=6&read_write_timeout=6',
            )
        );

        /** @var AMQPConnection $instance */
        $instance = $factory->createConnection();
        $this->assertInstanceOf('OldSound\RabbitMqBundle\Tests\RabbitMq\Fixtures\AMQPConnection', $instance);
        $this->assertEquals(array(
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
        ), $instance->constructParams);
    }

    public function testSSLConnectionParameters()
    {
        $factory = new AMQPConnectionFactory(
            'OldSound\RabbitMqBundle\Tests\RabbitMq\Fixtures\AMQPConnection',
            array(
                'host' => 'ssl_host',
                'port' => 123,
                'user' => 'ssl_user',
                'password' => 'ssl_password',
                'vhost' => '/ssl',
                'ssl_context' => array(
                    'verify_peer' => false,
                ),
            )
        );

        /** @var AMQPConnection $instance */
        $instance = $factory->createConnection();
        $this->assertInstanceOf('OldSound\RabbitMqBundle\Tests\RabbitMq\Fixtures\AMQPConnection', $instance);
        $this->assertArrayHasKey(11, $instance->constructParams);
        $context = $instance->constructParams[11];
        // unset to check whole array at once later
        $instance->constructParams[11] = null;
        $this->assertInternalType('resource', $context);
        $this->assertEquals('stream-context', get_resource_type($context));
        $options = stream_context_get_options($context);
        $this->assertEquals(array('ssl' => array('verify_peer' => false)), $options);
        $this->assertEquals(array(
            'ssl_host', // host
            123,        // port
            'ssl_user', // user
            'ssl_password', // password
            '/ssl',      // vhost
            false,       // insist
            "AMQPLAIN",  // login method
            null,        // login response
            "en_US",     // locale
            3,           // connection timeout
            3,           // read write timeout
            null,        // context checked earlier
            false,       // keepalive
            0,           // heartbeat
        ), $instance->constructParams);
    }

    public function testConnectionsParametersProvider()
    {
        $connectionParametersProvider = $this->prepareConnectionParametersProvider();
        $connectionParametersProvider->expects($this->once())
            ->method('getConnectionParameters')
            ->will($this->returnValue(
                array(
                    'host' => '1.2.3.4',
                    'port' => 5678,
                    'user' => 'admin',
                    'password' => 'admin',
                    'vhost' => 'foo',
                )
            ));
        $factory = new AMQPConnectionFactory(
            'OldSound\RabbitMqBundle\Tests\RabbitMq\Fixtures\AMQPConnection',
            array(),
            $connectionParametersProvider
        );

        /** @var AMQPConnection $instance */
        $instance = $factory->createConnection();
        $this->assertInstanceOf('OldSound\RabbitMqBundle\Tests\RabbitMq\Fixtures\AMQPConnection', $instance);
        $this->assertEquals(array(
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
        ), $instance->constructParams);
    }

    /**
     * Preparing ConnectionParametersProviderInterface instance
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|ConnectionParametersProviderInterface
     */
    private function prepareConnectionParametersProvider()
    {
        return $this->getMockBuilder('OldSound\RabbitMqBundle\Provider\ConnectionParametersProviderInterface')
            ->getMock();
    }
}
