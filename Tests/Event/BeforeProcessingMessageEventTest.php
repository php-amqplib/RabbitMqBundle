<?php

namespace OldSound\RabbitMqBundle\Tests\Event;

use OldSound\RabbitMqBundle\Event\BeforeProcessingMessageEvent;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * Class BeforeProcessingMessageEventTest
 *
 * @package OldSound\RabbitMqBundle\Tests\Event
 */
class BeforeProcessingMessageEventTest extends \PHPUnit_Framework_TestCase
{
    public function testEvent()
    {
        $AMQPMessage = new AMQPMessage('body');
        $event = new BeforeProcessingMessageEvent($AMQPMessage);
        $this->assertSame($AMQPMessage, $event->getAMQPMessage());
    }
}
