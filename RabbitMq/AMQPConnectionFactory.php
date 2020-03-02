<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

use OldSound\RabbitMqBundle\Provider\ConnectionParametersProviderInterface;
use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Connection\AMQPSocketConnection;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

class AMQPConnectionFactory
{
    /** @var \ReflectionClass */
    private $class;

    /** @var array */
    private $parameters = array(
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
    );

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
        if (is_array($this->parameters['ssl_context'])) {
            $this->parameters['ssl_context'] = ! empty($this->parameters['ssl_context'])
                ? stream_context_create(array('ssl' => $this->parameters['ssl_context']))
                : null;
        }
        if ($parametersProvider) {
            $this->parameters = array_merge($this->parameters, $parametersProvider->getConnectionParameters());
        }
    }

    /**
     * Creates the appropriate connection using current parameters.
     *
     * @return AbstractConnection
     */
    public function createConnection()
    {
        $ref = new \ReflectionClass($this->class);

        if (isset($this->parameters['constructor_args']) && is_array($this->parameters['constructor_args'])) {
            return $ref->newInstanceArgs($this->parameters['constructor_args']);
        }

        if ($this->class == 'PhpAmqpLib\Connection\AMQPSocketConnection' || is_subclass_of($this->class, 'PhpAmqpLib\Connection\AMQPSocketConnection')) {
            return $ref->newInstanceArgs([
                    $this->parameters['host'],
                    $this->parameters['port'],
                    $this->parameters['user'],
                    $this->parameters['password'],
                    $this->parameters['vhost'],
                    false,      // insist
                    'AMQPLAIN', // login_method
                    null,       // login_response
                    'en_US',    // locale
                    isset($this->parameters['read_timeout']) ? $this->parameters['read_timeout'] : $this->parameters['read_write_timeout'],
                    $this->parameters['keepalive'],
                    isset($this->parameters['write_timeout']) ? $this->parameters['write_timeout'] : $this->parameters['read_write_timeout'],
                    $this->parameters['heartbeat']
                ]
            );
        } else {
            return $ref->newInstanceArgs([
                $this->parameters['host'],
                $this->parameters['port'],
                $this->parameters['user'],
                $this->parameters['password'],
                $this->parameters['vhost'],
                false,      // insist
                'AMQPLAIN', // login_method
                null,       // login_response
                'en_US',    // locale
                $this->parameters['connection_timeout'],
                $this->parameters['read_write_timeout'],
                $this->parameters['ssl_context'],
                $this->parameters['keepalive'],
                $this->parameters['heartbeat']
            ]);
        }
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

        if ($url === false || !isset($url['scheme']) || $url['scheme'] !== 'amqp') {
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
            $query = array();
            parse_str($url['query'], $query);
            $parameters = array_merge($parameters, $query);
        }

        unset($parameters['url']);

        return $parameters;
    }
}
