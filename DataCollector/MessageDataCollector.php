<?php

namespace OldSound\RabbitMqBundle\DataCollector;

use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * MessageDataCollector
 *
 * @author Marc Weistroff <marc.weistroff@sensio.com>
 */
class MessageDataCollector extends DataCollector
{
    private $channels;

    /** @var array */
    private $messages;

    public function __construct($channels)
    {
        $this->channels = $channels;
        $this->messages = [];
    }

    public function collect(Request $request, Response $response, ?\Throwable $exception = null): void
    {
        foreach ($this->channels as $channel) {
            foreach ($channel->getBasicPublishLog() as $log) {
                $this->messages[] = $log;
            }
        }
    }

    public function getName(): string
    {
        return 'rabbit_mq';
    }

    public function getPublishedMessagesCount(): int
    {
        return count($this->messages);
    }

    public function getPublishedMessagesLog(): array
    {
        return $this->messages;
    }

    public function reset(): void
    {
        $this->messages = [];
    }
}
