<?php

namespace OldSound\RabbitMqBundle\Tests\RabbitMq;

use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface as ContractsEventDispatcherInterface;
use OldSound\RabbitMqBundle\Event\AMQPEvent;
use OldSound\RabbitMqBundle\RabbitMq\BaseAmqp;
use OldSound\RabbitMqBundle\RabbitMq\Consumer;
use PHPUnit\Framework\TestCase;

class BaseAmqpTest extends TestCase
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
        /** @var BaseAmqp|MockObject $baseAmqpConsumer */
        $baseAmqpConsumer = $this->getMockBuilder('OldSound\RabbitMqBundle\RabbitMq\BaseAmqp')
            ->disableOriginalConstructor()
            ->getMock();
        if (is_subclass_of('AMQPEvent', 'ContractsBaseEvent')) {
            $eventDispatcher = $this->getMockBuilder('Symfony\Contracts\EventDispatcher\EventDispatcherInterface')
                ->disableOriginalConstructor()
                ->getMock();
        } else {
            $eventDispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcherInterface')
                ->disableOriginalConstructor()
                ->getMock();
        }
        $baseAmqpConsumer->expects($this->atLeastOnce())
            ->method('getEventDispatcher')
            ->willReturn($eventDispatcher);
        if ($eventDispatcher instanceof ContractsEventDispatcherInterface) {
            $eventDispatcher->expects($this->once())
                ->method('dispatch')
                ->with(new AMQPEvent(), AMQPEvent::ON_CONSUME)
                ->willReturn(new AMQPEvent());
        } else {
            $eventDispatcher->expects($this->once())
                ->method('dispatch')
                ->with(AMQPEvent::ON_CONSUME, new AMQPEvent())
                ->willReturn(true);
        }
        $this->invokeMethod('dispatchEvent', $baseAmqpConsumer, array(AMQPEvent::ON_CONSUME, new AMQPEvent()));
    }

    /**
     * @param string $name
     * @param MockObject $obj
     * @param array $params
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
