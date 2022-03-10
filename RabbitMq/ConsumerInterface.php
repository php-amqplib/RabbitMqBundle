<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

use PhpAmqpLib\Message\AMQPMessage;

interface ConsumerInterface
{
    /**
     * Flag for message ack
     */
    public const MSG_ACK = 1;

    /**
     * Flag single for message nack and requeue
     */
    public const MSG_SINGLE_NACK_REQUEUE = 2;

    /**
     * Flag for reject and requeue
     */
    public const MSG_REJECT_REQUEUE = 0;

    /**
     * Flag for reject and drop
     */
    public const MSG_REJECT = -1;

    /**
     * Flag for consumers that wants to handle ACKs on their own
     */
    public const MSG_ACK_SENT = -2;

    /**
     * @param AMQPMessage $msg The message
     * @return int|bool One of ConsumerInterface::MSG_* constants according to callback outcome, or false otherwise.
     */
    public function execute(AMQPMessage $msg);
}
