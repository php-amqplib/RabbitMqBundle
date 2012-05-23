<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

use OldSound\RabbitMqBundle\RabbitMq\BaseAmqp;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;

class Producer extends BaseAmqp
{
    protected $producerExchangeOptions = array(
        'durable' => false,
        'auto_delete' => true,
        'internal' => false
    );

    protected $declared = false;

    public function __construct(AMQPConnection $conn, AMQPChannel $ch = null, $consumerTag = null)
    {
        parent::__construct($conn, $ch, $consumerTag);

    }

    public function setExchangeOptions(array $options = array())
    {
        $this->exchangeOptions = array_merge(
            $this->exchangeOptions,
            $this->producerExchangeOptions
        );

        parent::setExchangeOptions($options);
    }

    public function exchangeDeclare()
    {
        $this->ch->exchange_declare(
            $this->exchangeOptions['name'],
            $this->exchangeOptions['type'],
            $this->exchangeOptions['passive'],
            $this->exchangeOptions['durable'],
            $this->exchangeOptions['auto_delete'],
            $this->exchangeOptions['internal']);

        $this->declared = true;
    }

    public function publish($msgBody, $routingKey = '')
    {
        if (!$this->declared) {
            $this->exchangeDeclare();
        }
        $msg = new AMQPMessage($msgBody, array('content_type' => 'text/plain', 'delivery_mode' => 2));
        $this->ch->basic_publish($msg, $this->exchangeOptions['name'], $routingKey);
    }
}
