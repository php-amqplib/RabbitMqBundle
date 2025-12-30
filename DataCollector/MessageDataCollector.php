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

    public function __construct($channels)
    {
        $this->channels = $channels;
        $this->data = [];
    }

    public function collect(Request $request, Response $response, ?\Throwable $exception = null): void
    {
        foreach ($this->channels as $channel) {
            foreach ($channel->getBasicPublishLog() as $log) {
                $this->data[] = $log;
            }
        }
    }

    public function getName(): string
    {
        return 'rabbit_mq';
    }

    public function getPublishedMessagesCount(): int
    {
        return count($this->data);
    }

    public function getPublishedMessagesLog(): array
    {
        return $this->data;
    }

    public function reset(): void
    {
        $this->data = [];
    }
}
