<?php

namespace OldSound\RabbitMqBundle;

use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

trait EventDispatcherAwareTrait
{
    /** @var EventDispatcherInterface|null */
    protected $eventDispatcher;

    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function getEventDispatcher(): ?EventDispatcherInterface
    {
        return $this->eventDispatcher;
    }


    protected function dispatchEvent(object $event, string $eventName = null)
    {
        if (null !== $this->eventDispatcher) {
            $this->eventDispatcher->dispatch($event, $eventName);
        }
    }
}