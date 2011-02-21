<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

use OldSound\RabbitMqBundle\RabbitMq\BaseAmqp;

class Producer extends BaseAmqp
{
  public function publish($msgBody, $routingKey = '')
  {
    $this->ch->exchange_declare($this->exchangeOptions['name'], $this->exchangeOptions['type'], false, true, false);
    $msg = new \AMQPMessage($msgBody, array('content_type' => 'text/plain', 'delivery_mode', 2));
    $this->ch->basic_publish($msg, $this->exchangeOptions['name'], $routingKey);
  }
}