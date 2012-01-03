<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

use OldSound\RabbitMqBundle\RabbitMq\BaseConsumer;
use PhpAmqpLib\Message\AMQPMessage;

class Consumer extends BaseConsumer
{
    protected $target;

    protected $consumed = 0;

    public function consume($msgAmount)
    {
        $this->target = $msgAmount;

        $this->setUpConsumer();

        while (count($this->ch->callbacks))
        {
            $this->ch->wait();
        }
    }

    public function processMessage(AMQPMessage $msg)
    {
        try
        {
            //TODO pass the whole message and document the usage.
            call_user_func($this->callback, $msg);
            $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
            $this->consumed++;
            $this->maybeStopConsumer($msg);
        }
        catch (\Exception $e)
        {
            throw $e;
        }
    }

    protected function maybeStopConsumer(AMQPMessage $msg)
    {
        if ($this->target == 0) {
            return;
        }

        if ($this->consumed == $this->target) {
            $this->stopConsuming();
        }
    }
}