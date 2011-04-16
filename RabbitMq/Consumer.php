<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

use OldSound\RabbitMqBundle\RabbitMq\BaseConsumer;

class Consumer extends BaseConsumer
{
    public function consume($msgAmount)
    {
        $this->target = $msgAmount;

        $this->setUpConsumer();

        while(count($this->ch->callbacks))
        {
            $this->ch->wait();
        }
    }

    public function processMessage($msg)
    {
        try
        {
            call_user_func($this->callback, $msg->body);
            $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
            $this->consumed++;
            $this->maybeStopConsumer($msg);
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    protected function maybeStopConsumer($msg)
    {
        if($this->target == -1) return;
        
        if($this->consumed == $this->target)
        {
            $this->stopConsuming();
        }
    }
}