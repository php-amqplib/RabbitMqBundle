<?php

namespace OldSound\RabbitMqBundle\ReceiverExecutor;

use OldSound\RabbitMqBundle\Declarations\ConsumeOptions;
use OldSound\RabbitMqBundle\Declarations\RpcConsumeOptions;
use OldSound\RabbitMqBundle\RabbitMq\Utils;
use OldSound\RabbitMqBundle\Receiver\ReceiverInterface;
use OldSound\RabbitMqBundle\Receiver\ReplyReceiverInterface;
use PhpAmqpLib\Message\AMQPMessage;

class ReplyReceiverResultHandler implements ReceiverResultHandlerInterface
{
    public function handle($result, array $messages, ConsumeOptions $options): void
    {
        if (count($messages) !== 1) {
            throw new \InvalidArgumentException('Replay consumer not support batch messages execution');
        }
        if (!$options instanceof RpcConsumeOptions) {
            throw new \InvalidArgumentException('blabla');
        }

        /** @var AMQPMessage $message */
        $message = first($messages);

        if (!($message->get($options->replayToProperty) && $message->get($options->correlationIdProperty))) {
            throw new \InvalidArgumentException('todo'); // TODO
        }

        if (!is_string($result)) {
            throw new \InvalidArgumentException('replay receiver should return string');
        }
        $this->sendReply($message->getChannel(), $result, $message->get($options->replayToProperty), $message->get($options->correlationIdProperty), $options);

        Utils::handleProcessMessages($message->getChannel(), [$message->getDeliveryTag() => ReceiverInterface::MSG_ACK]);
    }

    protected function sendReply(\AMQPChannel $channel, string $reply, $replyTo, $correlationId, RpcConsumeOptions $options)
    {
        $properties = array_merge(
            ['content_type' => 'text/plain'],
            $options->replayMessageProperties,
            [$options->correlationIdProperty => $correlationId]
        );

        $message = new AMQPMessage($reply, $properties);
        $channel->basic_publish($message , '', $replyTo);
    }
}