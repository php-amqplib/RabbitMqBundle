<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

class AMQPConnectionFactory
{
    /** @var \ReflectionClass */
    private $class;

    /** @var array */
    private $parameters = array(
        'host'               => 'localhost',
        'port'               => 5672,
        'user'               => 'guest',
        'password'           => 'guest',
        'vhost'              => '/',
        'connection_timeout' => 3,
        'read_write_timeout' => 3,
        'ssl_context'        => null,
        'keepalive'          => false,
        'heartbeat'          => 0,
    );

    public function __construct($class, array $parameters)
    {
        if (is_string($class)) {
            $class = new \ReflectionClass($class);
        }
        $this->class = $class;
        $this->parameters = $this->mergeDefaultConnectionParameters($parameters);
        if ($this->parameters['ssl_context']) {
            $this->parameters['ssl_context'] = stream_context_create(array('ssl' => $this->parameters['ssl_context']));
        }
    }

    public function createConnection()
    {
        $connection = $this->parameters;

        return $this->class->newInstance(
            $connection['host'],
            $connection['port'],
            $connection['user'],
            $connection['password'],
            $connection['vhost'],
            false,      // insist
            'AMQPLAIN', // login_method
            null,       // login_response
            'en_US',    // locale
            $connection['connection_timeout'],
            $connection['read_write_timeout'],
            $connection['ssl_context'],
            $connection['keepalive'],
            $connection['heartbeat']
        );
    }

    private function mergeDefaultConnectionParameters(array $parameters)
    {
        return array_merge($this->parameters, $parameters);
    }
}
