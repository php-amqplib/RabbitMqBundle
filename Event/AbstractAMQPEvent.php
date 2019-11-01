<?php

namespace OldSound\RabbitMqBundle\Event;

use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\EventDispatcher\Event as ContractsBaseEvent;
use Symfony\Component\EventDispatcher\Event as BaseEvent;
use Symfony\Component\EventDispatcher\EventDispatcher;

if (is_subclass_of('EventDispatcher', 'EventDispatcherInterface')) {

    abstract class AbstractAMQPEvent extends ContractsBaseEvent
    {

    }
} else {

    abstract class AbstractAMQPEvent extends BaseEvent
    {

    }
}