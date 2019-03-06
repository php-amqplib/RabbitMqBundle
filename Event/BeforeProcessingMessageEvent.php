<?php

namespace OldSound\RabbitMqBundle\Event;

use OldSound\RabbitMqBundle\RabbitMq\BaseConsumer;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * Class BeforeProcessingMessageEvent
 *
 * @package OldSound\RabbitMqBundle\Command
 */
class BeforeProcessingMessageEvent extends AMQPEvent
{
    const NAME = AMQPEvent::BEFORE_PROCESSING_MESSAGE;

    /**
     * BeforeProcessingMessageEvent constructor.
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
