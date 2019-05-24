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

    public static function create_connection($hosts, $options)
    {
        $options = array_merge($hosts[0], $options);
        return new self(
            $hosts[0]['host'],
            $hosts[0]['port'],
            $hosts[0]['user'],
            $hosts[0]['password'],
            $hosts[0]['vhost'],
            false,
            'AMQPLAIN',
            null,
            'en_US',
            $options['read_timeout'],
            $options['write_timeout'],
            $options['ssl_options'],
            $options['keepalive'],
            $options['heartbeat']
        );
    }
}
