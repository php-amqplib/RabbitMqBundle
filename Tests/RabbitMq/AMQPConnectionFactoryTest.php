<?php

namespace OldSound\RabbitMqBundle\Tests\RabbitMq;

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
}
