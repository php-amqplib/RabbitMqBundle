<?php

namespace OldSound\RabbitMqBundle\Event;

use OldSound\RabbitMqBundle\RabbitMq\BaseConsumer;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class AMQPEvent
 *
 * @package OldSound\RabbitMqBundle\Event
 * @codeCoverageIgnore
 */
class AMQPEvent extends Event
{
    const ON_CONSUME                = 'on_consume';
    const ON_IDLE                   = 'on_idle';
    const BEFORE_PROCESSING_MESSAGE = 'before_processing';
    const AFTER_PROCESSING_MESSAGE  = 'after_processing';

    /**
     * @var AMQPMessage
     */
    protected $AMQPMessage;

    /**
     * @var BaseConsumer
     */
    protected $consumer;

    /**
     * @return AMQPMessage
     */
    public function getAMQPMessage()
    {
        return $this->AMQPMessage;
    }

    /**
     * @param AMQPMessage $AMQPMessage
     *
     * @return AMQPEvent
     */
    public function setAMQPMessage(AMQPMessage $AMQPMessage)
    {
        $this->AMQPMessage = $AMQPMessage;

        return $this;
    }

    /**
     * @return BaseConsumer
     */
    public function getConsumer()
    {
        return $this->consumer;
    }

    /**
     * @param BaseConsumer $consumer
     *
     * @return AMQPEvent
     */
    public function setConsumer(BaseConsumer $consumer)
    {
        $this->consumer = $consumer;

        return $this;
    }
}
