<?php

namespace OldSound\RabbitMqBundle\Event;

use OldSound\RabbitMqBundle\RabbitMq\BaseConsumer;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * Class AfterProcessingMessageEvent
 *
 * @package OldSound\RabbitMqBundle\Event
 */
class AfterProcessingMessageEvent extends AMQPEvent
{
    const NAME = AMQPEvent::AFTER_PROCESSING_MESSAGE;

    /**
     * AfterProcessingMessageEvent constructor.
     *
     * @param BaseConsumer $consumer
     * @param AMQPMessage $AMQPMessage
     */
    public function __construct(BaseConsumer $consumer, AMQPMessage $AMQPMessage)
    {
        $this->setConsumer($consumer);
        $this->setAMQPMessage($AMQPMessage);
    }
}
