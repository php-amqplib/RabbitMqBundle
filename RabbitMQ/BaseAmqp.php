<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

class BaseAmqp
{
  protected $conn;
  protected $ch;
  protected $consumerTag;
  
  protected $exchangeOptions = array(
      'passive' => false,
      'durable' => true,
      'auto_delete' => false,
      'internal' => false,
      'nowait' => false,
      'arguments' => null,
      'ticket' => null
    );
  
  protected $queueOptions = array(
      'name' => '',
      'passive' => false,
      'durable' => true,
      'exclusive' => false,
      'auto_delete' => false,
      'nowait' => false,
      'arguments' => null,
      'ticket' => null
    );
    
  protected $routingKey = '';
  
  public function __construct($conn, $ch = null, $consumerTag = null)
  {
    $this->conn = $conn;
    
    $this->ch = empty($ch) ? $this->conn->channel() : $ch;
    
    $this->consumerTag = empty($consumerTag) ? sprintf("PHPPROCESS_%s_%s", gethostname(), getmypid()) : $consumerTag;
  }
  
  public function __destruct()
  {
    //TODO FIX!
    // if(!empty($this->ch) && !empty($this->conn))
    // {
    //     $this->ch->close();
    // }
    // 
    // if(!empty($this->conn))
    // {
    //     $this->conn->close();
    // }
  }
  
  public function setChannel($ch)
  {
      $this->ch = $ch;
  }
  
  public function setExchangeOptions($options)
  {
    if(empty($options['name']))
    {
      throw new InvalidArgumentException('You must provide an exchange name');
    }
    
    if(empty($options['type']))
    {
      throw new InvalidArgumentException('You must provide an exchange type');
    }
    
    $this->exchangeOptions = array_merge($this->exchangeOptions, $options);
  }
  
  public function setQueueOptions($options)
  {
    $this->queueOptions = array_merge($this->queueOptions, $options);
  }
  
  public function setRoutingKey($routingKey)
  {
    $this->routingKey = $routingKey;
  }
}

?>