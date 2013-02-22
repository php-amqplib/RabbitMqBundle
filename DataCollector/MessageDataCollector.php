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
        $this->data = array();
    }

    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        foreach ($this->channels as $channel) {
            foreach ($channel->getBasicPublishLog() as $log) {
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
}
