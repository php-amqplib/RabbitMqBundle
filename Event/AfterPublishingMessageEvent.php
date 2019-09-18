<?php

namespace OldSound\RabbitMqBundle\Event;

use OldSound\RabbitMqBundle\RabbitMq\Producer;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * Class AfterPublishingMessageEvent
 *
 * @package OldSound\RabbitMqBundle\Command
 */
class AfterPublishingMessageEvent extends AMQPEvent
{
    const NAME = AMQPEvent::AFTER_PUBLISHING_MESSAGE;

    /**
     * AfterPublishingMessageEvent constructor.
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
