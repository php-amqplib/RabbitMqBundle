<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

use OldSound\RabbitMqBundle\RabbitMq\BaseConsumer;
use PhpAmqpLib\Message\AMQPMessage;

class Consumer extends BaseConsumer
{
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
            call_user_func($this->callback, $msg);
            $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
            $this->consumed++;
            $this->maybeStopConsumer();
        }
        catch (\Exception $e)
        {
            throw $e;
        }
    }

}