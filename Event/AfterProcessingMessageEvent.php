<?php

namespace OldSound\RabbitMqBundle\Event;

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
     * @param AMQPMessage $AMQPMessage
     */
    public function __construct(AMQPMessage $AMQPMessage)
    {
        $this->AMQPMessage = $AMQPMessage;
    }
}
