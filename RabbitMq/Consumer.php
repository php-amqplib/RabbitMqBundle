<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

use OldSound\RabbitMqBundle\RabbitMq\BaseConsumer;
use PhpAmqpLib\Message\AMQPMessage;

class Consumer extends BaseConsumer
{
    public function consume($msgAmount)
    {
        $this->target = $msgAmount;

        $this->setupConsumer();

        while (count($this->ch->callbacks))
        {
            $this->maybeStopConsumer();
            $this->ch->wait();
        }
    }

    public function processMessage(AMQPMessage $msg)
    {
        if (false === call_user_func($this->callback, $msg)) {
            // Reject and requeue message to RabbitMQ
            $msg->delivery_info['channel']->basic_reject($msg->delivery_info['delivery_tag'], true);
        }
        else {
            // Remove message from queue only if callback return not false
            $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
        }

        $this->consumed++;
        $this->maybeStopConsumer();
    }
}
