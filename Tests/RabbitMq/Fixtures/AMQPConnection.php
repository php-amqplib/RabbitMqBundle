<?php

namespace OldSound\RabbitMqBundle\Tests\RabbitMq\Fixtures;

class AMQPConnection
{
    public $constructParams;

    public function __construct()
    {
        // save params for direct access in tests
        $this->constructParams = func_get_args();
    }
}
