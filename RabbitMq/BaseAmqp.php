<?php

namespace OldSound\RabbitMqBundle\RabbitMq;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPConnection;

abstract class BaseAmqp
{
    protected $conn;
    protected $ch;
    protected $consumerTag;
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
     * @param string $routingKey
     * @return void
     */
    public function setRoutingKey($routingKey)
    {
        $this->routingKey = $routingKey;
    }
}
