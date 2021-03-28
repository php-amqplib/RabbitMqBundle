<?php

namespace OldSound\RabbitMqBundle\Receiver;

use OldSound\RabbitMqBundle\RabbitMq\Exception\RpcResponseException;
use PhpAmqpLib\Message\AMQPMessage;

interface ReceiverInterface
{
    /** Flag for message ack */
    const MSG_ACK = 1;

    /** Flag single for message nack and requeue */
    const MSG_SINGLE_NACK_REQUEUE = 2;

    /** Flag for reject and requeue */
    const MSG_REJECT_REQUEUE = 0;

    /** Flag for reject and drop */
    const MSG_REJECT = -1;

    /** Flag for consumers that wants to handle ACKs on their own*/
    const MSG_ACK_SENT = -2;
}
