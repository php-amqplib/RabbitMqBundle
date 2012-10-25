<?php

namespace OldSound\RabbitMqBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use OldSound\RabbitMqBundle\RabbitMq\Consumer;

class RabbitMqEvent extends Event
{
    protected $consumer;

    public function __construct(Consumer $consumer)
    {
        $this->consumer = $consumer;
    }

    public function getConsumer()
    {
        return $this->consumer;
    }
}