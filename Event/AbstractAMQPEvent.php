<?php

namespace OldSound\RabbitMqBundle\Event;

use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Contracts\EventDispatcher\Event as ContractsBaseEvent;

abstract class AbstractAMQPEvent extends ContractsBaseEvent
{
    /** @var AMQPMessage[] */
    protected $messages;

    /** @var bool */
    private $forceStop = false;

    public function isForceStop(): bool
    {
        return $this->forceStop;
    }

    public function setForceStop(bool $forceStop)
    {
        $this->forceStop = $forceStop;
    }
}
