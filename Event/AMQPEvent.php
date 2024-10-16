<?php

namespace OldSound\RabbitMqBundle\Event;

use OldSound\RabbitMqBundle\RabbitMq\Consumer;
use OldSound\RabbitMqBundle\RabbitMq\Producer;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * Class AMQPEvent
 *
 * @package OldSound\RabbitMqBundle\Event
 * @codeCoverageIgnore
 */
class AMQPEvent extends AbstractAMQPEvent
{
    public const ON_CONSUME                = 'on_consume';
    public const ON_IDLE                   = 'on_idle';
    public const BEFORE_PROCESSING_MESSAGE = 'before_processing';
    public const AFTER_PROCESSING_MESSAGE  = 'after_processing';
    public const BEFORE_PUBLISH_MESSAGE = 'before_publishing';
    public const AFTER_PUBLISH_MESSAGE  = 'after_publishing';

    /**
     * @var AMQPMessage
     */
    protected $AMQPMessage;

    /**
     * @var Consumer
     */
    protected $consumer;

    /**
     * @var Producer
     */
    protected $producer;

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
     * @return Consumer
     */
    public function getConsumer()
    {
        return $this->consumer;
    }

    /**
     * @param Consumer $consumer
     *
     * @return AMQPEvent
     */
    public function setConsumer(Consumer $consumer)
    {
        $this->consumer = $consumer;

        return $this;
    }

    /**
     * @return Producer
     */
    public function getProducer()
    {
        return $this->producer;
    }

    /**
     * @param Producer $producer
     *
     * @return AMQPEvent
     */
    public function setProducer(Producer $producer)
    {
        $this->producer = $producer;

        return $this;
    }
}
