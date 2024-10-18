<?php

namespace OldSound\RabbitMqBundle\Event;

use OldSound\RabbitMqBundle\RabbitMq\Producer;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * Class AfterProducerPublishMessageEvent
 *
 * @package OldSound\RabbitMqBundle\Command
 */
class AfterProducerPublishMessageEvent extends AMQPEvent
{
    public const NAME = AMQPEvent::AFTER_PUBLISH_MESSAGE;

    /**
     * @var string
     */
    protected $routingKey;

    /**
     * AfterProducerPublishMessageEvent constructor.
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
     * @return string
     */
    public function getRoutingKey()
    {
        return $this->routingKey;
    }
}
