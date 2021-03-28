<?php

namespace OldSound\RabbitMqBundle\EventListener;

use OldSound\RabbitMqBundle\RabbitMq\Consumer;

class ConsumedLimitListener
{
    private $consumedLimit;

    /** @var int */
    private $consumed = 0;

    public function __construct(int $consumedLimit)
    {
        $this->consumedLimit = $consumedLimit;
    }

    public function __invoke(Consumer $consumer)
    {
        if ($this->consumed >= $this->consumedLimit) {
            $consumer->stopConsuming(true);
        }
    }
}