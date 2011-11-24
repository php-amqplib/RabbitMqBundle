<?php

namespace OldSound\RabbitMqBundle\RabbitMq;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPConnection;

abstract class BaseAmqp
{
    protected $conn;
    protected $ch;
    protected $consumerTag;

    protected $exchangeOptions = array(
        'passive' => false,
        'durable' => true,
        'auto_delete' => false,
        'internal' => false,
        'nowait' => false,
        'arguments' => null,
        'ticket' => null
    );

    protected $queueOptions = array(
        'name' => '',
        'passive' => false,
        'durable' => true,
        'exclusive' => false,
        'auto_delete' => false,
        'nowait' => false,
        'arguments' => null,
        'ticket' => null
    );

    protected $routingKey = '';

    /**
     * @param AMQPConnection $conn
     * @param AMQPChannel|null $ch
     * @param null $consumerTag
     */
    public function __construct(AMQPConnection $conn, AMQPChannel $ch = null, $consumerTag = null)
    {
        $this->conn = $conn;

        $this->ch = empty($ch) ? $this->conn->channel() : $ch;

        $this->consumerTag = empty($consumerTag) ? sprintf("PHPPROCESS_%s_%s", gethostname(), getmypid()) : $consumerTag;
    }

    public function __destruct()
    {
        //TODO FIX!
        // if (!empty($this->ch) && !empty($this->conn))
        // {
        //     $this->ch->close();
        // }
        //
        // if (!empty($this->conn))
        // {
        //     $this->conn->close();
        // }
    }

    /**
     * @param AMQPChannel $ch
     * @return void
     */
    public function setChannel(AMQPChannel $ch)
    {
        $this->ch = $ch;
    }

    /**
     * @throws \InvalidArgumentException
     * @param array $options
     * @return void
     */
    public function setExchangeOptions(array $options = array())
    {
        if (empty($options['name'])) {
            throw new \InvalidArgumentException('You must provide an exchange name');
        }

        if (empty($options['type'])) {
            throw new \InvalidArgumentException('You must provide an exchange type');
        }

        $this->exchangeOptions = array_merge($this->exchangeOptions, $options);
    }

    /**
     * @param array $options
     * @return void
     */
    public function setQueueOptions(array $options = array())
    {
        $this->queueOptions = array_merge($this->queueOptions, $options);
    }

    /**
     * @param string $routingKey
     * @return void
     */
    public function setRoutingKey($routingKey)
    {
        $this->routingKey = $routingKey;
    }
}
