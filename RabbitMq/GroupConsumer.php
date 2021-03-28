<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

use PhpAmqpLib\Connection\AbstractConnection;
use Symfony\Component\Console\Exception\InvalidArgumentException;

class GroupConsumer extends Consumer
{
    /** @var iterable|Consumer[] */
    protected $consumers = array();

    public function addConsumers(iterable $consumers)
    {
        foreach ($consumers as $consumer) {
            $consumer->setChannel($this->ch);
            $consumer->setConsumerTag(sprintf("PHPPROCESS_%s_%s_%s", gethostname(), getmypid(), count($this->consumers)));
            $this->consumers[] = $consumer;
        }
    }

    public function setupConsumer()
    {
        foreach ($this->consumers as $consumer) {
            $consumer->setupConsumer();
        }
    }

    public function stopConsuming()
    {
        foreach ($this->consumers as $consumer) {
            $consumer->stopConsuming();
        }
    }

    public function purge()
    {
        foreach ($this->consumers as $consumer) {
            $consumer->purge();
        }
    }

    public function delete()
    {
        foreach ($this->consumers as $consumer) {
            $consumer->delete();
        }
    }

    public function disableAutoSetupFabric()
    {
        foreach ($this->consumers as $consumer) {
            $consumer->disableAutoSetupFabric();
        }
    }
}