<?php

namespace OldSound\RabbitMqBundle\Producer;

use OldSound\RabbitMqBundle\Declarations\DeclarationsRegistry;
use OldSound\RabbitMqBundle\Declarations\Declarator;
use OldSound\RabbitMqBundle\EventDispatcherAwareTrait;
use OldSound\RabbitMqBundle\RabbitMq\AMQPConnectionFactory;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

class Producer
{
    const DELIVERY_MODE_NON_PERSISTENT = 1;
    const DELIVERY_MODE_PERSISTENT = 2;

    /** @var AbstractConnection */
    protected $connection;
    /** @var string */
    protected $exchange;
    /** @var DeclarationsRegistry|null */
    protected $declarationsRegistry;

    protected $additionalProperties = [
        'content_type' => 'text/plain',
        'delivery_mode' => self::DELIVERY_MODE_PERSISTENT
    ];

    public function __construct(AbstractConnection $connection, string $exchange)
    {
        $this->connection = $connection;
        $this->exchange = $exchange;
    }

    public function setAdditionalProperties(array $additionalProperties): Producer
    {
        $this->additionalProperties = array_merge($this->additionalProperties, $additionalProperties);
        return $this;
    }

    /**
     * Enable auto declare
     */
    public function setRegisterDeclare(DeclarationsRegistry $declarationsRegistry = null): Producer
    {
        $this->declarationsRegistry = $declarationsRegistry;
        return $this;
    }

    public function publish(string $body, string $routingKey = '', array $additionalProperties = [], array $headers = null): void
    {
        $channel = AMQPConnectionFactory::getChannelFromConnection($this->connection);
        if ($this->declarationsRegistry) {
            // TODO (new Declarator($channel))->declareForExchange($this->exchange, $this->declarationsRegistry);
            //$this->declarationsRegistry = null; // stop autodeclare
        }

        $msg = new AMQPMessage($body, array_merge($this->additionalProperties, $additionalProperties));

        if (null !== $headers) {
            $headersTable = new AMQPTable($headers);
            $msg->set('application_headers', $headersTable);
        }

        $channel->basic_publish($msg, $this->exchange, $routingKey);
    }
}
