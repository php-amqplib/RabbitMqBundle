<?php

namespace OldSound\RabbitMqBundle\Event;

use OldSound\RabbitMqBundle\Declarations\ConsumeOptions;
use OldSound\RabbitMqBundle\RabbitMq\Consumer;
use PhpAmqpLib\Message\AMQPMessage;

class ReceiverArgumentsEvent extends AbstractAMQPEvent
{
    const NAME = 'old_sound_rabbit_mq.before_processing';

    protected $arguments;
    /** @var ConsumeOptions */
    protected $options;

    public function __construct(array $arguments, ConsumeOptions $options)
    {
        $this->arguments = $arguments;
        $this->options = $options;
    }
    
    public function getArguments()
    {
        return $this->arguments;
    }
    
    public function setArguments(array $arguments)
    {
        $this->arguments = $arguments;
    }
    
    public function getOptions()
    {
        return $this->options;
    }
    
    public function setReceiver(callable $receiver)
    {
        $this->options->receiver = $receiver;
    }
}
