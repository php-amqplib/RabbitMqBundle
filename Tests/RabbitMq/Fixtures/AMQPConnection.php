<?php

namespace OldSound\RabbitMqBundle\Tests\RabbitMq\Fixtures;

use PhpAmqpLib\Connection\AMQPStreamConnection;

class AMQPConnection extends AMQPStreamConnection
{
    public $constructParams;

    public static $createConnectionParams;

    public function __construct()
    {
        // save params for direct access in tests
        $this->constructParams = func_get_args();
    }

    public static function create_connection($hosts, $options = [])
    {
        // save params for direct access in tests
        self::$createConnectionParams = func_get_args();

        return parent::create_connection($hosts, $options);
    }
}
