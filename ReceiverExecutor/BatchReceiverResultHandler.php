<?php


namespace OldSound\RabbitMqBundle\ReceiverExecutor;

use OldSound\RabbitMqBundle\Declarations\ConsumeOptions;
use OldSound\RabbitMqBundle\RabbitMq\Utils;
use OldSound\RabbitMqBundle\Receiver\ReceiverInterface;
use PhpAmqpLib\Message\AMQPMessage;

class BatchReceiverResultHandler implements ReceiverResultHandlerInterface
{
    public function handle($result, array $messages, ConsumeOptions $options): void
    {
        if ($result === true) {
            $flags = ReceiverInterface::MSG_ACK;
        } else if ($result === false) {
            $flags = ReceiverInterface::MSG_REJECT;
        } else {
            $flags = $result;
        }

        if (!is_array($flags)) { // flat flag for each delivery tag
            $flag = $flags;
            $flags = [];
            foreach ($messages as $message) {
                $flags[$message->getDeliveryTag()] = $flag;
            }
        } else if (count($flags) !== count($messages)) {
            throw new AMQPRuntimeException(// TODO
                'Method batchExecute() should return an array with elements equal with the number of messages processed'
            );
        }

        /** @var AMQPMessage $message */
        $message = reset($messages);

        Utils::handleProcessMessages($message->getChannel(), [$message->getDeliveryTag() => $result]);
    }
}