<?php

namespace OldSound\RabbitMqBundle\Event;

use OldSound\RabbitMqBundle\RabbitMq\Producer;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * Class BeforePublishingMessageEvent
 *
 * @package OldSound\RabbitMqBundle\Command
 */
class BeforePublishingMessageEvent extends AMQPEvent
{
    const NAME = AMQPEvent::BEFORE_PUBLISHING_MESSAGE;

    /**
     * BeforePublishingMessageEvent constructor.
     *
     * @param Producer $producer
     * @param AMQPMessage $AMQPMessage
     */
    public function __construct(Producer $producer, AMQPMessage $AMQPMessage)
    {
        $this->setProducer($producer);
        $this->setAMQPMessage($AMQPMessage);
    }
}
