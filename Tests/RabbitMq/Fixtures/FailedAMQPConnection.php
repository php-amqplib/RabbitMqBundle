<?php

namespace OldSound\RabbitMqBundle\Tests\RabbitMq\Fixtures;

class FailedAMQPConnection
{
    public $constructParams;

    public function __construct()
    {
        throw new \RuntimeException("Dummy exception");
    }
}
