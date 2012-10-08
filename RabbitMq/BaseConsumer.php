<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

use OldSound\RabbitMqBundle\RabbitMq\BaseAmqp;
use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Channel\AMQPChannel;

abstract class BaseConsumer extends BaseAmqp
{
    protected $target;

    protected $consumed = 0;

    protected $callback;

    /**
     * @var Exchange
     */
    protected $exchange;

    /**
     * @var Queue
     */
    protected $queue;

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

    protected function setUpConsumer($consume = true)
    {
        var_dump($this->routingKey);

//        $this->ch->exchange_declare(
//            $this->exchange->getName(),
//            $this->exchange->getOptions()->get('type'),
//            $this->exchange->getOptions()->get('passive'),
//            $this->exchange->getOptions()->get('durable'),
//            $this->exchange->getOptions()->get('auto_delete'),
//            $this->exchange->getOptions()->get('internal'),
//            $this->exchange->getOptions()->get('nowait'),
//            $this->exchange->getOptions()->get('arguments'),
//            $this->exchange->getOptions()->get('ticket')
//        );
//
//        list($queueName, ,) = $this->ch->queue_declare(
//            $this->queue->getName(),
//            $this->queue->getOptions()->get('passive'),
//            $this->queue->getOptions()->get('durable'),
//            $this->queue->getOptions()->get('exclusive'),
//            $this->queue->getOptions()->get('auto_delete'),
//            $this->queue->getOptions()->get('nowait'),
//            $this->queue->getOptions()->get('arguments'),
//            $this->queue->getOptions()->get('ticket')
//        );
//
//        $this->ch->queue_bind($queueName, $this->exchange->getName(), $this->routingKey);
//        if ($consume) {
//            $this->ch->basic_consume($queueName, $this->getConsumerTag(), false, false, false, false, array($this, 'processMessage'));
//        }
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

    public function setExchange(Exchange $exchange)
    {
        $this->exchange = $exchange;
    }

    public function getExchange()
    {
        return $this->exchange;
    }

    /**
     * @param Queue $queue
     */
    public function setQueue(Queue $queue)
    {
        $this->queue = $queue;
    }

    /**
     * @return Queue
     */
    public function getQueue()
    {
        return $this->queue;
    }
}
