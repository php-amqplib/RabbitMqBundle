<?php

namespace OldSound\RabbitMqBundle\RPC;

use OldSound\RabbitMqBundle\Declarations\BindingDeclaration;
use OldSound\RabbitMqBundle\Declarations\Declarator;
use OldSound\RabbitMqBundle\Declarations\ConsumeOptions;
use OldSound\RabbitMqBundle\Declarations\QueueDeclaration;
use OldSound\RabbitMqBundle\ExecuteCallbackStrategy\BatchExecuteCallbackStrategy;
use OldSound\RabbitMqBundle\Producer\ProducerInterface;
use OldSound\RabbitMqBundle\RabbitMq\Exception\RpcResponseException;
use OldSound\RabbitMqBundle\Serializer\JsonMessageSerializer;
use OldSound\RabbitMqBundle\Serializer\MessageSerializerInterface;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Serializer\Serializer;

class RpcClient implements BatchReceiverInterface
{
    const PROPERTY_REPLAY_TO = 'reply_to';
    const PROPERTY_CORRELATION_ID = 'correlation_id';

    /** @var AMQPChannel */
    private $channel;
    /** @var int */
    private $expiration;

    /** @var QueueDeclaration */
    private $anonRepliesQueue;
    /** @var MessageSerializerInterface[] */
    private $serializers;

    /** @var int */
    private $requests = 0;
    /** @var AMQPMessage[] */
    private $messages;
    private $replies = [];

    public function __construct(
        AMQPChannel $channel,
        int $expiration = 10000
    ) {
        $this->channel = $channel;
        $this->serializer = $serializer ?? new JsonMessageSerializer();
        $this->expiration = $expiration;
    }

    public function declareRepliesQueue($repliesQueueName = null): RpcClient
    {
        $this->anonRepliesQueue = QueueDeclaration::createAnonymous();
        $this->anonRepliesQueue->name = $repliesQueueName;
        $declarator = new Declarator($this->channel);
        [$queueName] = $declarator->declareQueues([$this->anonRepliesQueue]);
        $this->anonRepliesQueue->name = $queueName;

        return $this;
    }

    public function addRequest($msgBody, $rpcQueue, MessageSerializerInterface $serializer = null)
    {
        if (!$this->anonRepliesQueue) {
            throw new \LogicException('no init anonRepliesQueue');
        }

        $correlationId = $this->requests;
        $this->serializers[$correlationId] = $serializer;

        $serializer = $serializer ?? new JsonMessageSerializer();

        $replyToQueue = $this->anonRepliesQueue->name; // 'amq.rabbitmq.reply-to';
        $msg = new AMQPMessage($serializer->serialize($msgBody, 'json'), [
            self::PROPERTY_REPLAY_TO => $replyToQueue,
            self::PROPERTY_CORRELATION_ID => $correlationId,
            'content_type' => 'text/plain',
            'delivery_mode' => ProducerInterface::DELIVERY_MODE_NON_PERSISTENT,
            'expiration' => $this->expiration,
        ]);

        $this->channel->basic_publish($msg, '', $rpcQueue);
        $this->requests++;
    }

    public function batchExecute(array $messages)
    {
        if ($this->messages !== null) {
            throw new \LogicException('Rpc client consming should be called once by batch count limit');
        }
        $this->messages = $messages;
    }

    /**
     * @param $name
     * @param MessageSerializerInterface $serializer
     * @return array|AMQPMessage[]
     */
    public function getReplies($name): array
    {
        if (0 === $this->requests) {
            throw new \LogicException('request empty');
        }

        $consumer = new Consumer($this->channel);
        $consuming = new ConsumeOptions();
        $consuming->exclusive = true;
        $consuming->qosPrefetchCount = $this->requests;
        $consuming->queue = $this->anonRepliesQueue->name;
        $consuming->receiver = $this;
        $consumer->consumeQueue($consuming, new BatchExecuteCallbackStrategy($this->requests));

        try {
            $consumer->consume($this->requests);
        } finally {
            // TODO $this->getChannel()->basic_cancel($consumer_tag);
        }

        $replices = [];
        foreach($this->messages as $message) {
            /** @var AMQPMessage $message */
            if (!$message->has('correlation_id')) {
                $this->logger->error('unexpected message. rpc replies have no correlation_id ');
                continue;
            }

            $correlationId = $message->get('correlation_id');
            $serializer = $this->serializers[$correlationId];
            $reply = $serializer ? $serializer->deserialize($message->body) : $message;
            $replices[$correlationId] = $reply;
        }
        ksort($replices);
        return $replices;
    }
}
