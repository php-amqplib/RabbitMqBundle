<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

use OldSound\RabbitMqBundle\RabbitMq\BaseAmqp;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;

class Producer extends BaseAmqp
{
    protected $declared = false;

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

        if (('' != $this->queueOptions['name']) && (null !== $this->queueOptions['name'])) {
            list($queueName, ,) = $this->ch->queue_declare($this->queueOptions['name'], $this->queueOptions['passive'],
                                                                   $this->queueOptions['durable'], $this->queueOptions['exclusive'],
                                                                   $this->queueOptions['auto_delete'], $this->queueOptions['nowait'],
                                                                   $this->queueOptions['arguments'], $this->queueOptions['ticket']);

            $this->ch->queue_bind($queueName, $this->exchangeOptions['name'], $this->routingKey);
        }
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
