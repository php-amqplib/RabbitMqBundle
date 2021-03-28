<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

use OldSound\RabbitMqBundle\Receiver\ReceiverInterface;
use PhpAmqpLib\Channel\AMQPChannel;

class Utils
{
    public static function handleReceiverMessages(AMQPChannel $channel, array $flags, $multiAck = true)
    {
        $ack = !array_search(fn ($reply) => $reply !== ReceiverInterface::MSG_ACK, $flags, true);
        if ($multiAck && count($flags) > 1 && $ack) {
            $lastDeliveryTag = array_key_last($flags);

            $channel->basic_ack($lastDeliveryTag, true);
        } else {
            foreach ($flags as $deliveryTag => $flag) {
                if ($flag === ReceiverInterface::MSG_REJECT_REQUEUE) {
                    $channel->basic_reject($deliveryTag, true); // Reject and requeue message to RabbitMQ
                } else if ($flag === ReceiverInterface::MSG_SINGLE_NACK_REQUEUE) {
                    $channel->basic_nack($deliveryTag, false, true); // NACK and requeue message to RabbitMQ
                } else if ($flag === ReceiverInterface::MSG_REJECT) {
                    $channel->basic_reject($deliveryTag, false); // Reject and drop
                } else if ($flag !== ReceiverInterface::MSG_ACK_SENT) {
                    $channel->basic_ack($deliveryTag); // Remove message from queue only if callback return not false
                } else {
                    // TODO throw..
                }
            }
        }
    }
}