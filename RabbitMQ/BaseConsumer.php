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
}

?>