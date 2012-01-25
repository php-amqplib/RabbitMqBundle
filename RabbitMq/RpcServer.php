<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

use OldSound\RabbitMqBundle\RabbitMq\BaseAmqp;
use PhpAmqpLib\Message\AMQPMessage;

class RpcServer extends BaseConsumer
{
    protected $target;

    protected $consumed = 0;

    public function initServer($name)
    {
        $this->setExchangeOptions(array('name' => $name, 'type' => 'direct'));
        $this->setQueueOptions(array('name' => $name . '-queue'));
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

    public function processMessage(AMQPMessage $msg)
    {
        try
        {
            $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
            $result = call_user_func($this->callback, $msg);
            $this->sendReply(serialize($result), $msg->get('reply_to'), $msg->get('correlation_id'));
            $this->consumed++;
            $this->maybeStopConsumer();
        }
        catch (\Exception $e)
        {
            $this->sendReply('error: ' . $e->getMessage(), $msg->get('reply_to'), $msg->get('correlation_id'));
        }
    }

    protected function sendReply($result, $client, $correlationId)
    {
        $reply = new AMQPMessage($result, array('content_type' => 'text/plain', 'correlation_id' => $correlationId));
        $this->ch->basic_publish($reply, '', $client);
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
}