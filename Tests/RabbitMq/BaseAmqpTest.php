<?php

namespace OldSound\RabbitMqBundle\Tests\RabbitMq;

use OldSound\RabbitMqBundle\Event\AMQPEvent;
use OldSound\RabbitMqBundle\RabbitMq\BaseAmqp;
use OldSound\RabbitMqBundle\RabbitMq\Consumer;
use PhpAmqpLib\Connection\AMQPLazyConnection;

class BaseAmqpTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \ErrorException
     */
    public function testLazyConnection()
    {
        $amqpLazyConnection = new AMQPLazyConnection('localhost', 123, 'lazy_user', 'lazy_password');

        $consumer = new Consumer($amqpLazyConnection, null);
        $consumer->getChannel();
    }

    public function testDispatchEvent()
    {
        /** @var BaseAmqp|\PHPUnit_Framework_MockObject_MockObject $baseAmqpConsumer */
        $baseAmqpConsumer = $this->getMockBuilder('OldSound\RabbitMqBundle\RabbitMq\BaseAmqp')
            ->disableOriginalConstructor()
            ->getMock();
        $eventDispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcher')
            ->disableOriginalConstructor()
            ->getMock();
        $baseAmqpConsumer->expects($this->atLeastOnce())
            ->method('getEventDispatcher')
            ->willReturn($eventDispatcher);

        $eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(AMQPEvent::ON_CONSUME, new AMQPEvent())
            ->willReturn(true);
        $this->invokeMethod('dispatchEvent', $baseAmqpConsumer, array(AMQPEvent::ON_CONSUME, new AMQPEvent()));
    }

    /**
     * @param $name
     * @param $obj
     * @param $params
     *
     * @return mixed
     */
    protected function invokeMethod($name, $obj, $params)
    {
        $class = new \ReflectionClass(get_class($obj));
        $method = $class->getMethod($name);
        $method->setAccessible(true);

        return $method->invokeArgs($obj, $params);
    }
}
