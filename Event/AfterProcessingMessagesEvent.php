<?php

namespace OldSound\RabbitMqBundle\Event;

use PhpAmqpLib\Message\AMQPMessage;

/**
 * Class AfterProcessingMessageEvent
 *
 * @package OldSound\RabbitMqBundle\Event
 */
class AfterProcessingMessagesEvent extends AbstractAMQPEvent
{
    const NAME = 'old_sound_rabbit_mq.after_processing';

    /**
     * AfterProcessingMessageEvent constructor.
     *
     * @param AMQPMessage $AMQPMessage
     */
    public function __construct(array $messages)
    {
        $this->messages = $messages;
    }
}
