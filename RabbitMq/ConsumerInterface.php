<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

use PhpAmqpLib\Message\AMQPMessage;

interface ConsumerInterface
{
    /**
     * Flag for message ack
     */
    const MSG_ACK = 1;

    /**
     * Flag for reject and requeue
     */
    const MSG_REJECT_REQUEUE = 0;

    /**
     * Flag for reject and drop
     */
    const MSG_REJECT = -1;


    public function execute(AMQPMessage $msg);
}
