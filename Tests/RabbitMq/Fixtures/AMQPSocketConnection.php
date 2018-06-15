<?php

namespace OldSound\RabbitMqBundle\Tests\RabbitMq\Fixtures;

use PhpAmqpLib\Connection\AMQPSocketConnection as BaseAMQPSocketConnection;

class AMQPSocketConnection extends BaseAMQPSocketConnection
{
    public $constructParams;

    public function __construct()
    {
        // save params for direct access in tests
        $this->constructParams = func_get_args();
    }
}
