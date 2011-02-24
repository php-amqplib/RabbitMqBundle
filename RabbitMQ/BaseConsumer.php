<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

use OldSound\RabbitMqBundle\RabbitMq\BaseAmqp;

class BaseConsumer extends BaseAmqp
{
  protected $callback;
  
  public function setCallback($callback)
  {
    $this->callback = $callback;
  }
  
  public function stopConsuming()
  {
      $this->ch->basic_cancel($this->getConsumerTag());
  }
  
  protected function setUpConsumer()
  {
    $this->ch->exchange_declare($this->exchangeOptions['name'], $this->exchangeOptions['type'], 
                                $this->exchangeOptions['passive'], $this->exchangeOptions['durable'],
                                $this->exchangeOptions['auto_delete'], $this->exchangeOptions['internal'],
                                $this->exchangeOptions['nowait'], $this->exchangeOptions['arguments'],
                                $this->exchangeOptions['ticket']);
    
    list($queueName,,) = $this->ch->queue_declare($this->queueOptions['name'], $this->queueOptions['passive'],
                                                  $this->queueOptions['durable'], $this->queueOptions['exclusive'],
                                                  $this->queueOptions['auto_delete'], $this->queueOptions['nowait'],
                                                  $this->queueOptions['arguments'], $this->queueOptions['ticket']);
    
    $this->ch->queue_bind($queueName, $this->exchangeOptions['name'], $this->routingKey);
    $this->ch->basic_consume($queueName, $this->getConsumerTag(), false, false, false, false, array($this, 'processMessage'));
  }
  
  public function setConsumerTag($tag)
  {
      $this->consumerTag = $tag;
  }
  
  public function getConsumerTag()
  {
      return $this->consumerTag;
  }
}

?>