<?php

namespace OldSound\RabbitMqBundle\EventListener;

use OldSound\RabbitMqBundle\Event\AfterProcessingMessagesEvent;
use OldSound\RabbitMqBundle\Event\OnConsumeEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\DependencyInjection\ServicesResetter;

class ServicesResetterSubscriber implements EventSubscriberInterface
{
    /** @var ServicesResetter */
    private $servicesResetter;

    public function __construct(ServicesResetter $servicesResetter)
    {
        $this->servicesResetter = $servicesResetter;
    }

    public static function getSubscribedEvents()
    {
        return [
            AfterProcessingMessagesEvent::NAME => 'afterProcessingMessages'
        ];
    }

    public function afterProcessingMessages()
    {
        $this->servicesResetter->reset();
    }
}