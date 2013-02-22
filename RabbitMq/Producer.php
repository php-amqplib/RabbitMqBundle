<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

use OldSound\RabbitMqBundle\RabbitMq\BaseAmqp;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;

class Producer extends BaseAmqp
{
    protected $exchangeDeclared = false;
    protected $queueDeclared = false;
    protected $contentType = 'text/plain';
    protected $deliveryMode = 2;
    
    public function setContentType($contentType) {
        $this->contentType = $contentType;
    }
    
    public function setDeliveryMode($deliveryMode) {
        $this->deliveryMode = $deliveryMode;
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

        $this->exchangeDeclared = true;
    }

    public function queueDeclare()
    {
        if (null !== $this->queueOptions['name']) {
            list($queueName, ,) = $this->ch->queue_declare($this->queueOptions['name'], $this->queueOptions['passive'],
                                                                   $this->queueOptions['durable'], $this->queueOptions['exclusive'],
                                                                   $this->queueOptions['auto_delete'], $this->queueOptions['nowait'],
                                                                   $this->queueOptions['arguments'], $this->queueOptions['ticket']);

            $this->ch->queue_bind($queueName, $this->exchangeOptions['name'], $this->routingKey);

            $this->queueDeclared = true;
        }
    }

    public function setupProducer()
    {
        if (!$this->exchangeDeclared) {
            $this->exchangeDeclare();
        }

        if (!$this->queueDeclared) {
            $this->queueDeclare();
        }
    }

    public function publish($msgBody, $routingKey = '')
    {
        $this->setupProducer();

        $msg = new AMQPMessage($msgBody, array('content_type' => $this->contentType, 'delivery_mode' => $this->deliveryMode));
        $this->ch->basic_publish($msg, $this->exchangeOptions['name'], $routingKey);
    }
}
