<?php

namespace OldSound\RabbitMqBundle\DependencyInjection;

use OldSound\RabbitMqBundle\RabbitMq\Consumer;

class ConsumerBag
{
    /** @var Consumer[] */
    private $consumers = [];

    public function addConsumer($key, $consumer) : void
    {
        $this->consumers[$key] = $consumer;
    }

    /**
     * @return Consumer[]
     */
    public function getConsumers() : array
    {
        return $this->consumers;
    }

    public function findConsumerByKey(string $name)
    {
        return $this->consumers[$name] ?? null;
    }
}
