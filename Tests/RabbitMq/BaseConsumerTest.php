<?php

namespace OldSound\RabbitMqBundle\Tests\RabbitMq;

use OldSound\RabbitMqBundle\RabbitMq\BaseConsumer;

class BaseConsumerTest extends \PHPUnit_Framework_TestCase
{
    /** @var BaseConsumer */
    protected $consumer;

    protected function setUp()
    {
        $amqpConnection =  $this->getMockBuilder('\PhpAmqpLib\Connection\AMQPConnection')
            ->disableOriginalConstructor()
            ->getMock();

        $this->consumer = $this->getMockBuilder('\OldSound\RabbitMqBundle\RabbitMq\BaseConsumer')
            ->setConstructorArgs(array($amqpConnection))
            ->getMockForAbstractClass();
    }

    public function testItExtendsBaseAmqpInterface()
    {
        $this->assertInstanceOf('OldSound\RabbitMqBundle\RabbitMq\BaseAmqp', $this->consumer);
    }

    public function testItImplementsDequeuerInterface()
    {
        $this->assertInstanceOf('OldSound\RabbitMqBundle\RabbitMq\DequeuerInterface', $this->consumer);
    }

    public function testItsIdleTimeoutIsMutable()
    {
        $this->assertEquals(0, $this->consumer->getIdleTimeout());
        $this->consumer->setIdleTimeout(42);
        $this->assertEquals(42, $this->consumer->getIdleTimeout());
    }

    public function testItsIdleTimeoutExitCodeIsMutable()
    {
        $this->assertEquals(0, $this->consumer->getIdleTimeoutExitCode());
        $this->consumer->setIdleTimeoutExitCode(43);
        $this->assertEquals(43, $this->consumer->getIdleTimeoutExitCode());
    }

    public function testForceStopConsumer()
    {
        $this->assertAttributeEquals(false, 'forceStop', $this->consumer);
        $this->consumer->forceStopConsumer();
        $this->assertAttributeEquals(true, 'forceStop', $this->consumer);
    }
}
