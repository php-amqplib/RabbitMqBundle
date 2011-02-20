<?php

namespace OldSound\RabbitmqBundle\Rabbitmq;

use OldSound\RabbitmqBundle\Rabbitmq\BaseAmqp;

class BaseConsumer extends BaseAmqp
{
  protected $callback;
  
  public function setCallback($callback)
  {
    $this->callback = $callback;
  }
}

?>