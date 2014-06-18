<?php

namespace OldSound\RabbitMqBundle\Tests\RabbitMq;

use OldSound\RabbitMqBundle\RabbitMq\Consumer;
use PhpAmqpLib\Connection\AMQPLazyConnection;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class BaseAmqpTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \PhpAmqpLib\Exception\AMQPRuntimeException
     */
    public function testLazyConnection()
    {
        $amqpLazyConnection = new AMQPLazyConnection('localhost', 123, 'lazy_user', 'lazy_password');

        $consumer = new Consumer($amqpLazyConnection, null);
        $consumer->getChannel();
    }
}
