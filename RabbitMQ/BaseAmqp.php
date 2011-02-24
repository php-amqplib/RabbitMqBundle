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