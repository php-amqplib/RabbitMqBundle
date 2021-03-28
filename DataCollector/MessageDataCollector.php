<?php

namespace OldSound\RabbitMqBundle\DataCollector;

use OldSound\RabbitMqBundle\RabbitMq\TraceableAMQPChannel;
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
    /** @var TraceableAMQPChannel[] */
    private $channels;

    public function __construct(iterable $channels)
    {
        $this->channels = $channels;
    }

    public function collect(Request $request, Response $response, \Throwable $exception = null)
    {
        foreach ($this->channels as $channel) {
            foreach ($channel->getTracedPublications() as $log) {
                $this->data[] = $log;
            }
        }
    }

    public function getName()
    {
        return 'rabbit_mq';
    }

    public function getPublishedMessagesCount()
    {
        return count($this->data);
    }

    public function getPublishedMessagesLog()
    {
        return $this->data;
    }

    public function reset()
    {
        $this->data = [];
    }
}
