<?php

namespace OldSound\RabbitMqBundle\Event;

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
     * @param AMQPMessage $AMQPMessage
     */
    public function __construct(AMQPMessage $AMQPMessage)
    {
        $this->AMQPMessage = $AMQPMessage;
    }
}
