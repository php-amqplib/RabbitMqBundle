<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

use PhpAmqpLib\Message\AMQPMessage;

class RpcServer extends BaseConsumer
{
    private $serializer = 'serialize';

    public function initServer($name)
    {
        $this->setExchangeOptions(['name' => $name, 'type' => 'direct']);
        $this->setQueueOptions(['name' => $name . '-queue']);
    }

    public function processMessage(AMQPMessage $msg)
    {
        try {
            $msg->ack();
            $result = call_user_func($this->callback, $msg);
            $result = call_user_func($this->serializer, $result);
            $this->sendReply($result, $msg->get('reply_to'), $msg->get('correlation_id'));
            $this->consumed++;
            $this->maybeStopConsumer();
        } catch (\Exception $e) {
            $this->sendReply('error: ' . $e->getMessage(), $msg->get('reply_to'), $msg->get('correlation_id'));
        }
    }

    protected function sendReply($result, $client, $correlationId)
    {
        $reply = new AMQPMessage($result, ['content_type' => 'text/plain', 'correlation_id' => $correlationId]);
        $this->getChannel()->basic_publish($reply, '', $client);
    }

    public function setSerializer($serializer)
    {
        $this->serializer = $serializer;
    }
}
