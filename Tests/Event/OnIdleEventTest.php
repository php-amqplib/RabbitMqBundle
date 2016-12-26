<?php

namespace OldSound\RabbitMqBundle\Tests\Event;

use OldSound\RabbitMqBundle\Event\OnIdleEvent;
use OldSound\RabbitMqBundle\RabbitMq\Consumer;

/**
 * Class OnIdleEventTest
 *
 * @package OldSound\RabbitMqBundle\Tests\Event
 */
class OnIdleEventTest extends \PHPUnit_Framework_TestCase
{
    protected function getConsumer()
    {
        return new Consumer(
            $this->getMockBuilder('\PhpAmqpLib\Connection\AMQPConnection')
                ->disableOriginalConstructor()
                ->getMock(),
            $this->getMockBuilder('\PhpAmqpLib\Channel\AMQPChannel')
                ->disableOriginalConstructor()
                ->getMock()
        );
    }

    public function testEvent()
    {
        $consumer = $this->getConsumer();
        $event = new OnIdleEvent($consumer);
        $this->assertSame($consumer, $event->getConsumer());
    }
}