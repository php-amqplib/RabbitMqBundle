<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

use OldSound\RabbitMqBundle\RabbitMq\BaseAmqp;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;

class Producer extends BaseAmqp
{
    protected $declared = false;

    /**
     * @var Exchange
     */
    protected $exchange;

    public function exchangeDeclare()
    {
        $this->ch->exchange_declare(
            $this->exchange->getName(),
            $this->exchange->getOptions()->get('type'),
            $this->exchange->getOptions()->get('passive'),
            $this->exchange->getOptions()->get('durable'),
            $this->exchange->getOptions()->get('auto_delete'),
            $this->exchange->getOptions()->get('internal')
        );

        $this->declared = true;
    }

    public function publish($msgBody, $routingKey = '')
    {
        if (!$this->declared) {
            $this->exchangeDeclare();
        }
        $msg = new AMQPMessage($msgBody, array('content_type' => 'text/plain', 'delivery_mode' => 2));
        $this->ch->basic_publish($msg, $this->exchange->getName(), $routingKey);
    }

    /**
     * @param Exchange $exchange
     */
    public function setExchange(Exchange $exchange)
    {
        $this->exchange = $exchange;
    }

    /**
     * @return Exchange
     */
    public function getExchange()
    {
        return $this->exchange;
    }
}
