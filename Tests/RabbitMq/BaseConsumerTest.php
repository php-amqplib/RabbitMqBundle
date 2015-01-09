<?php

namespace OldSound\RabbitMqBundle\Tests\RabbitMq;

use OldSound\RabbitMqBundle\RabbitMq\BaseConsumer;

class BaseConsumerTest extends \PHPUnit_Framework_TestCase
{
    public function testMayBeStopConsumerShouldNotCallStopConsumingWithNotStalledConsumer()
    {
        $amqpConnection = $this->getMockBuilder('\PhpAmqpLib\Connection\AMQPConnection')
            ->disableOriginalConstructor()
            ->getMock();

        $amqpChannel = $this->getMockBuilder('\PhpAmqpLib\Channel\AMQPChannel')
            ->disableOriginalConstructor()
            ->getMock();

        $baseConsumer = $this->getMockBuilder('OldSound\\RabbitMqBundle\\RabbitMq\\BaseConsumer')
            ->setConstructorArgs(array($amqpConnection, $amqpChannel))
            ->setMethods(array('stopConsuming'))
            ->getMockForAbstractClass();

        $baseConsumer->expects($this->never())
            ->method('stopConsuming');

        $consumer = $this->getMock('OldSound\\RabbitMqBundle\\RabbitMq\\StallableConsumerInterface');
        $consumer->expects($this->once())
            ->method('isStalled')
            ->will($this->returnValue(false));

        /** @type BaseConsumer $baseConsumer */
        $baseConsumer->setCallback($consumer);

        $method = new \ReflectionMethod(get_class($baseConsumer), 'maybeStopConsumer');
        $method->setAccessible(true);
        $method->invoke($baseConsumer);
    }

    public function testMayBeStopConsumerShouldCallStopConsumingWithStalledConsumer()
    {
        $amqpConnection = $this->getMockBuilder('\PhpAmqpLib\Connection\AMQPConnection')
            ->disableOriginalConstructor()
            ->getMock();

        $amqpChannel = $this->getMockBuilder('\PhpAmqpLib\Channel\AMQPChannel')
            ->disableOriginalConstructor()
            ->getMock();

        $baseConsumer = $this->getMockBuilder('OldSound\\RabbitMqBundle\\RabbitMq\\BaseConsumer')
            ->setConstructorArgs(array($amqpConnection, $amqpChannel))
            ->setMethods(array('stopConsuming'))
            ->getMockForAbstractClass();

        $baseConsumer->expects($this->once())
            ->method('stopConsuming');

        $consumer = $this->getMock('OldSound\\RabbitMqBundle\\RabbitMq\\StallableConsumerInterface');
        $consumer->expects($this->once())
            ->method('isStalled')
            ->will($this->returnValue(true));

        /** @type BaseConsumer $baseConsumer */
        $baseConsumer->setCallback($consumer);

        $method = new \ReflectionMethod(get_class($baseConsumer), 'maybeStopConsumer');
        $method->setAccessible(true);
        $method->invoke($baseConsumer);
    }
}
