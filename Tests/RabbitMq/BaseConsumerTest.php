<?php

namespace OldSound\RabbitMqBundle\Tests\RabbitMq;

use OldSound\RabbitMqBundle\RabbitMq\BaseConsumer;
use PHPUnit\Framework\TestCase;

class BaseConsumerTest extends TestCase
{
    /** @var BaseConsumer */
    protected $consumer;

    protected function setUp(): void
    {
        $amqpConnection = $this->getMockBuilder('\PhpAmqpLib\Connection\AMQPStreamConnection')
            ->disableOriginalConstructor()
            ->getMock();

        $this->consumer = $this->getMockBuilder('\OldSound\RabbitMqBundle\RabbitMq\BaseConsumer')
            ->setConstructorArgs([$amqpConnection])
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
}
