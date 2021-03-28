<?php


namespace OldSound\RabbitMqBundle\EventListener;

use OldSound\RabbitMqBundle\Event\AfterProcessingMessagesEvent;
use OldSound\RabbitMqBundle\Event\OnConsumeEvent;
use OldSound\RabbitMqBundle\RabbitMq\Consumer;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PcntlSignalDispatchSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            OnConsumeEvent::NAME => 'onConsume',
            AfterProcessingMessagesEvent::NAME => 'afterProcessingMessages'
        ];
    }

    public function __construct(Consumer $consumer)
    {
        $stopConsumer = function () use ($consumer) {
            // Process current message, then halt consumer
            $consumer->forceStopConsumer();
            // Halt consumer if waiting for a new message from the queue
            try {
                $consumer->stopConsuming(true);
            } catch (AMQPTimeoutException $e) {}
        };

        pcntl_signal(SIGTERM, $stopConsumer);
        pcntl_signal(SIGINT, $stopConsumer);
        // TODO pcntl_signal(SIGHUP, $restartConsumer);
    }

    public function onConsume(OnConsumeEvent $event)
    {
        pcntl_signal_dispatch();
    }

    public function afterProcessingMessages(AfterProcessingMessagesEvent $event)
    {
        pcntl_signal_dispatch();
    }
}