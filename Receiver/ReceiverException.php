<?php

namespace OldSound\RabbitMqBundle\Receiver;

class ReceiverException extends \Exception
{
    /**
     * @see ReceiverInterface::MSG_ACK
     * @see ReceiverInterface::MSG_ACK_SENT
     * @see ReceiverInterface::MSG_REJECT
     * @see ReceiverInterface::MSG_REJECT_REQUEUE
     * @see ReceiverInterface::MSG_ACK_SENT
     */
    public function __construct(
        $code = ReceiverInterface::MSG_SINGLE_NACK_REQUEUE,
        $message = "",
        Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}