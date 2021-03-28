<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Connection\AMQPConnection;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

class AMQPConnectionFactory
{
    private $defaultParameters = [
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
    ];

    /**
     * Creates the appropriate connection using current parameters.
     * @param string $class FQCN of AMQPConnection class to instantiate.
     * @param array $parameters Map containing parameters resolved by Extension
     */
    public function createConnection(string $class, array $parameters): AbstractConnection
    {
        $parameters = $this->parseUrl(array_merge($this->defaultParameters, $parameters));
        if (is_array($parameters['ssl_context'])) {
            $parameters['ssl_context'] = ! empty($parameters['ssl_context'])
                ? stream_context_create(array('ssl' => $parameters['ssl_context']))
                : null;
        }

        if (isset($parameters['constructor_args']) && is_array($parameters['constructor_args'])) {
            return new $class(...$parameters['constructor_args']);
        }

        $args = [
            $parameters['host'],
            $parameters['port'],
            $parameters['user'],
            $parameters['password'],
            $parameters['vhost'],
            false,      // insist
            'AMQPLAIN', // login_method
            null,       // login_response
            'en_US',    // locale
        ];

        $isSocketConnection = $class == \PhpAmqpLib\Connection\AMQPSocketConnection::class || is_subclass_of($class, \PhpAmqpLib\Connection\AMQPSocketConnection::class);
        if ($isSocketConnection) {
            $extraArgs = [
                $parameters['read_timeout'] ?? $parameters['read_write_timeout'],
                $parameters['keepalive'],
                $parameters['write_timeout'] ?? $parameters['read_write_timeout'],
                $parameters['heartbeat']
            ];
        } else {
            $extraArgs = [
                $parameters['connection_timeout'],
                $parameters['read_write_timeout'],
                $parameters['ssl_context'],
                $parameters['keepalive'],
                $parameters['heartbeat']
            ];
        }

        return new $class(...[...$args, ...$extraArgs]);
    }

    /**
     * Parses connection parameters from URL parameter.
     */
    private function parseUrl(array $parameters): array
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
            $query = array();
            parse_str($url['query'], $query);
            $parameters = array_merge($parameters, $query);
        }

        unset($parameters['url']);

        return $parameters;
    }

    // TODO move
    public static function getChannelFromConnection(AbstractConnection $connection): AMQPChannel
    {
        return $connection->channel(1);
    }
}
