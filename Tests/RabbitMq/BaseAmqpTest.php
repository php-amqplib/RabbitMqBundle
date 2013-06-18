<?php

namespace OldSound\RabbitMqBundle\Tests\RabbitMq;

use OldSound\RabbitMqBundle\RabbitMq\Consumer;
use PhpAmqpLib\Connection\AMQPLazyConnection;
use PhpAmqpLib\Exception\AMQPRuntimeException;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class BaseAmqpTest extends \PHPUnit_Framework_TestCase
{
    public function testLazyConnection()
    {
        $amqpLazyConnection = new AMQPLazyConnection('localhost', 123, 'lazy_user', 'lazy_password');

        $consumer = new Consumer($amqpLazyConnection, null);

        try {
            \PHPUnit_Framework_Assert::assertSame(null, $consumer->getChannel());
            \PHPUnit_Framework_Assert::fail('AMQPRuntimeException not thrown!');
        } catch (AMQPRuntimeException $e) {}
    }
}
