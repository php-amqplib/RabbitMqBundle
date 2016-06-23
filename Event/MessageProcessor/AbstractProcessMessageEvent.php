<?php

namespace OldSound\RabbitMqBundle\Event\MessageProcessor;

use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class AbstractProcessMessageEvent
 *
 * @package OldSound\RabbitMqBundle\Command
 */
abstract class AbstractProcessMessageEvent extends Event
{
    /**
     * @var AMQPMessage
     */
    protected $AMQPMessage;

    public function __construct(AMQPMessage $AMQPMessage)
    {
        $this->AMQPMessage = $AMQPMessage;
    }

    /**
     * Event name
     *
     * @return string
     */
    abstract public function getName();

    /**
     * @return AMQPMessage
     */
    public function getAMQPMessage()
    {
        return $this->AMQPMessage;
    }
}
