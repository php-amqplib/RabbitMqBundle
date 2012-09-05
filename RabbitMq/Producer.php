<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

use OldSound\RabbitMqBundle\RabbitMq\BaseAmqp;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * Prodcuer, that publishes AMQP Messages
 */
class Producer extends BaseAmqp
{
    protected $declared = false;

    /**
     * Delcares the exchange options
     */
    public function exchangeDeclare()
    {
        $this->ch->exchange_declare(
            $this->exchangeOptions['name'],
            $this->exchangeOptions['type'],
            $this->exchangeOptions['passive'],
            $this->exchangeOptions['durable'],
            $this->exchangeOptions['auto_delete'],
            $this->exchangeOptions['internal'],
            $this->exchangeOptions['nowait'],
            $this->exchangeOptions['arguments']
        );

        $this->declared = true;
    }
    
    /**
     * Publishes the message and merges additional properties with basic properties
     *
     * @param string $msgBody
     * @param string $routingKey
     * @param array $additionalProperties
     */
    public function publish($msgBody, $routingKey = '', $additionalProperties = array())
    {
        if (!$this->declared) {
            $this->exchangeDeclare();
        }
        $msg = new AMQPMessage((string) $msgBody, array_merge($this->basicProperties, $additionalProperties));
        $this->ch->basic_publish($msg, $this->exchangeOptions['name'], (string) $routingKey);
    }
}
