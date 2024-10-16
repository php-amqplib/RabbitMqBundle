<?php

namespace OldSound\RabbitMqBundle\Event;

use OldSound\RabbitMqBundle\RabbitMq\Producer;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * Class BeforeProducerPublishMessageEvent
 *
 * @package OldSound\RabbitMqBundle\Command
 */
class BeforeProducerPublishMessageEvent extends AMQPEvent
{
    public const NAME = AMQPEvent::BEFORE_PROCESSING_MESSAGE;

    /**
     * @var string
     */
    protected $routingKey;

    /**
     * BeforeProducerPublishMessageEvent constructor.
     *
     * @param AMQPMessage $AMQPMessage
     */
    public function __construct(Producer $producer, AMQPMessage $AMQPMessage, string $routingKey)
    {
        $this->setProducer($producer);
        $this->setAMQPMessage($AMQPMessage);
        $this->routingKey = $routingKey;
    }

    /**
     * @return AMQPMessage
     */
    public function getRoutingKey()
    {
        return $this->routingKey;
    }
}
