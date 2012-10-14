<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

use OldSound\RabbitMqBundle\RabbitMq\BaseAmqp;

abstract class BaseConsumer extends BaseAmqp
{
    protected $target;

    protected $consumed = 0;

    protected $callback;

    public function setCallback($callback)
    {
        $this->callback = $callback;
    }

    public function start($msgAmount = 0)
    {
        $this->target = $msgAmount;

        $this->setUpConsumer();

        while (count($this->ch->callbacks))
        {
            $this->ch->wait();
        }
    }

    public function stopConsuming()
    {
        $this->ch->basic_cancel($this->getConsumerTag());
    }

    protected function setUpConsumer()
    {
        $this->ch->exchange_declare($this->exchangeOptions['name'], $this->exchangeOptions['type'],
                                    $this->exchangeOptions['passive'], $this->exchangeOptions['durable'],
                                    $this->exchangeOptions['auto_delete'], $this->exchangeOptions['internal'],
                                    $this->exchangeOptions['nowait'], $this->exchangeOptions['arguments'],
                                    $this->exchangeOptions['ticket']);

        list($queueName, ,) = $this->ch->queue_declare($this->queueOptions['name'], $this->queueOptions['passive'],
                                                       $this->queueOptions['durable'], $this->queueOptions['exclusive'],
                                                       $this->queueOptions['auto_delete'], $this->queueOptions['nowait'],
                                                       $this->queueOptions['arguments'], $this->queueOptions['ticket']);

        $this->ch->queue_bind($queueName, $this->exchangeOptions['name'], $this->routingKey);
        $this->ch->basic_consume($queueName, $this->getConsumerTag(), false, false, false, false, array($this, 'processMessage'));
    }

    protected function maybeStopConsumer()
    {
        if ($this->target == 0) {
            return;
        }

        if ($this->consumed == $this->target) {
            $this->stopConsuming();
        }
    }

    public function setConsumerTag($tag)
    {
        $this->consumerTag = $tag;
    }

    public function getConsumerTag()
    {
        return $this->consumerTag;
    }

    /**
     * Sets the qos settings for the current channel
     * Consider that prefetchSize and global do not work with rabbitMQ version <= 8.0
     *
     * @param int $prefetchSize
     * @param int $prefetchCount
     * @param bool $global
     */
    public function setQosOptions($prefetchSize = 0, $prefetchCount = 0, $global = false)
    {
        $this->ch->basic_qos($prefetchSize, $prefetchCount, $global);
    }
}
