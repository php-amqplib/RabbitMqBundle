<?php

namespace OldSound\RabbitMqBundle\Tests\Event;

use OldSound\RabbitMqBundle\Event\AfterProcessingMessageEvent;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * Class AfterProcessingMessageEventTest
 *
 * @package OldSound\RabbitMqBundle\Tests\Event
 */
class AfterProcessingMessageEventTest extends \PHPUnit_Framework_TestCase
{
    
    public function testEvent()
    {
        $AMQPMessage = new AMQPMessage('body');
        $event = new AfterProcessingMessageEvent($AMQPMessage);
        $this->assertSame($AMQPMessage, $event->getAMQPMessage());
    }
}