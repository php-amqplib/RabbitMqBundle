<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

use OldSound\RabbitMqBundle\Provider\ConnectionParametersProviderInterface;
use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Connection\AMQPSocketConnection;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

class AMQPConnectionFactory
{
    /** @var string */
    private $class;

    /** @var array */
    private $parameters = [
        'url'                => '',
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
        'hosts'              => [],
        'channel_rpc_timeout' => 0.0,
    ];

    /**
     * Constructor
     *
     * @param string                                $class              FQCN of AMQPConnection class to instantiate.
     * @param array                                 $parameters         Map containing parameters resolved by
     *                                                                  Extension.
     * @param ConnectionParametersProviderInterface $parametersProvider Optional service providing/overriding
     *                                                                  connection parameters.
     */
    public function __construct(
        $class,
        array $parameters,
        ConnectionParametersProviderInterface $parametersProvider = null
    ) {
        $this->class = $class;
        $this->parameters = array_merge($this->parameters, $parameters);
        $this->parameters = $this->parseUrl($this->parameters);

        foreach ($this->parameters['hosts'] as $key => $hostParameters) {
            if (!isset($hostParameters['url'])) {
                continue;
            }

            $this->parameters['hosts'][$key] = $this->parseUrl($hostParameters);
        }

        if ($parametersProvider) {
            $this->parameters = array_merge($this->parameters, $parametersProvider->getConnectionParameters());
        }

        if (is_array($this->parameters['ssl_context'])) {
            $this->parameters['context'] = !empty($this->parameters['ssl_context'])
                ? stream_context_create(['ssl' => $this->parameters['ssl_context']])
                : null;
        }

    }

    /**
     * Creates the appropriate connection using current parameters.
     *
     * @return AbstractConnection
     * @throws \Exception
     */
    public function createConnection()
    {
        if (isset($this->parameters['constructor_args']) && is_array($this->parameters['constructor_args'])) {
            $constructorArgs = array_values($this->parameters['constructor_args']);
            return new $this->class(...$constructorArgs);
        }

        $hosts = $this->parameters['hosts'] ?: [$this->parameters];
        $options = $this->parameters;
        unset($options['hosts']);

        if ($this->class == AMQPSocketConnection::class || is_subclass_of($this->class, AMQPSocketConnection::class)) {
            $options['read_timeout'] ??= $this->parameters['read_write_timeout'];
            $options['write_timeout'] ??= $this->parameters['read_write_timeout'];
        }

        // No need to unpack options, they will be handled inside connection classes
        return $this->class::create_connection($hosts, $options);
    }

    /**
     * Parses connection parameters from URL parameter.
     *
     * @param array $parameters
     *
     * @return array
     */
    private function parseUrl(array $parameters)
    {
        if (!$parameters['url']) {
            return $parameters;
        }

        $url = parse_url($parameters['url']);

        if ($url === false || !isset($url['scheme']) || !in_array($url['scheme'], ['amqp', 'amqps'], true)) {
            throw new InvalidConfigurationException('Malformed parameter "url".');
        }

        // See https://www.rabbitmq.com/uri-spec.html
        if (isset($url['host'])) {
            $parameters['host'] = urldecode($url['host']);
        }
        if (isset($url['port'])) {
            $parameters['port'] = (int)$url['port'];
        }
        if (isset($url['user'])) {
            $parameters['user'] = urldecode($url['user']);
        }
        if (isset($url['pass'])) {
            $parameters['password'] = urldecode($url['pass']);
        }
        if (isset($url['path'])) {
            $parameters['vhost'] = urldecode(ltrim($url['path'], '/'));
        }

        if (isset($url['query'])) {
            $query = [];
            parse_str($url['query'], $query);
            $parameters = array_merge($parameters, $query);
        }

        unset($parameters['url']);

        return $parameters;
    }
}
