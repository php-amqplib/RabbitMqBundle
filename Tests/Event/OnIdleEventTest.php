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

    public function testShouldAllowGetConsumerSetInConstructor()
    {
        $consumer = $this->getConsumer();
        $event = new OnIdleEvent($consumer);

        $this->assertSame($consumer, $event->getConsumer());
    }

    public function testShouldSetForceStopToTrueInConstructor()
    {
        $consumer = $this->getConsumer();
        $event = new OnIdleEvent($consumer);

        $this->assertTrue($event->isForceStop());
    }

    public function testShouldReturnPreviouslySetForceStop()
    {
        $consumer = $this->getConsumer();
        $event = new OnIdleEvent($consumer);

        //guard
        $this->assertTrue($event->isForceStop());

        $event->setForceStop(false);
        $this->assertFalse($event->isForceStop());
    }
}
