<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

use OldSound\RabbitMqBundle\Provider\ConnectionParametersProviderInterface;
use PhpAmqpLib\Connection\AbstractConnection;
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
        'hosts'              => null,
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
        if (isset($this->parameters['constructor_args']) && is_array($this->parameters['constructor_args'])) {
            $ref = new \ReflectionClass($this->class);
            return $ref->newInstanceArgs($this->parameters['constructor_args']);
        }

        $readWriteTimeout = $this->getParameter('read_write_timeout');
        if (!$readWriteTimeout) {
            $readWriteTimeout = $this->getParameter(
                'read_timeout',
                $this->getParameter('write_timeout')
            );
        }
        $options = array(
            'ssl_options' => $this->parameters['ssl_context'],
            'keepalive' => $this->parameters['keepalive'],
            'read_timeout' => isset($this->parameters['read_timeout']) ? $this->parameters['read_timeout'] : $this->parameters['read_write_timeout'],
            'write_timeout' => isset($this->parameters['write_timeout']) ? $this->parameters['write_timeout'] : $this->parameters['read_write_timeout'],
            'read_write_timeout' => $readWriteTimeout,
            'heartbeat' => $this->parameters['heartbeat'],
        );
        $hosts = isset($this->parameters['hosts']) ? $this->parameters['hosts'] : null;
        if (!$hosts) {
            $hosts = array(
                array(
                    'host' => $this->parameters['host'],
                    'port' => $this->parameters['port'],
                    'user' => $this->parameters['user'],
                    'password' => $this->parameters['password'],
                    'vhost' => $this->parameters['vhost'],
                )
            );
        }
        return call_user_func(array($this->class, 'create_connection'), $hosts, $options);
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

    /**
     * Try to get value from parameters.
     * @param $name
     * @param null $default
     * @return mixed|null
     */
    private function getParameter($name, $default = null)
    {
        return isset($this->parameters[$name]) ? $this->parameters[$name] : $default;
    }
}
