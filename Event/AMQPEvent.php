<?php

namespace OldSound\RabbitMqBundle\Event;

use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class AMQPEvent
 *
 * @package OldSound\RabbitMqBundle\Event
 */
class AMQPEvent extends Event
{
    const ON_CONSUME                = 'on_consume';
    const BEFORE_PROCESSING_MESSAGE = 'before_processing';
    const AFTER_PROCESSING_MESSAGE  = 'after_processing';

    /**
     * @var AMQPMessage
     */
    protected $AMQPMessage;
    
    /**
     * @return AMQPMessage
     */
    public function getAMQPMessage()
    {
        return $this->AMQPMessage;
    }
}
