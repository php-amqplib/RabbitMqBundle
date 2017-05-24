<?php

namespace OldSound\RabbitMqBundle\Tests\RabbitMq;

use OldSound\RabbitMqBundle\Event\AMQPEvent;
use OldSound\RabbitMqBundle\RabbitMq\BaseAmqp;
use OldSound\RabbitMqBundle\RabbitMq\Consumer;

class BaseAmqpTest extends \PHPUnit_Framework_TestCase
{

    public function testLazyConnection()
    {
        $connection = $this->getMockBuilder('PhpAmqpLib\Connection\AbstractConnection')
            ->disableOriginalConstructor()
            ->getMock();

        $connection
            ->method('connectOnConstruct')
            ->willReturn(false);
        $connection
            ->expects(static::never())
            ->method('channel');

        new Consumer($connection, null);
    }

    public function testNotLazyConnection()
    {
        $connection = $this->getMockBuilder('PhpAmqpLib\Connection\AbstractConnection')
            ->disableOriginalConstructor()
            ->getMock();

        $connection
            ->method('connectOnConstruct')
            ->willReturn(true);
        $connection
            ->expects(static::once())
            ->method('channel');

        new Consumer($connection, null);
    }

    public function testDispatchEvent()
    {
        /** @var BaseAmqp|\PHPUnit_Framework_MockObject_MockObject $baseAmqpConsumer */
        $baseAmqpConsumer = $this->getMockBuilder('OldSound\RabbitMqBundle\RabbitMq\BaseAmqp')
            ->disableOriginalConstructor()
            ->getMock();
        $eventDispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcherInterface')
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
