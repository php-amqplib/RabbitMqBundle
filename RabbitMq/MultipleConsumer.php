<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

use OldSound\RabbitMqBundle\Provider\QueuesProviderInterface;
use OldSound\RabbitMqBundle\RabbitMq\Exception\QueueNotFoundException;
use PhpAmqpLib\Message\AMQPMessage;

class MultipleConsumer extends Consumer
{
    protected $queues = array();

    /**
     * Queues provider
     *
     * @var QueuesProviderInterface
     */
    protected $queuesProvider = null;
    
    /**
     * Context the consumer runs in
     *
     * @var string
     */
    protected $context = null;

    /**
     * QueuesProvider setter
     *
     * @param QueuesProviderInterface $queuesProvider
     *
     * @return self
     */
    public function setQueuesProvider(QueuesProviderInterface $queuesProvider)
    {
        $this->queuesProvider = $queuesProvider;
        return $this;
    }

    public function getQueueConsumerTag($queue)
    {
        return sprintf('%s-%s', $this->getConsumerTag(), $queue);
    }

    public function setQueues(array $queues)
    {
        $this->queues = $queues;
    }
    
    public function setContext($context)
    {
        $this->context = $context;
    }

    protected function setupConsumer()
    {
        $this->mergeQueues();

        if ($this->autoSetupFabric) {
            $this->setupFabric();
        }

        foreach ($this->queues as $name => $options) {
            //PHP 5.3 Compliant
            $currentObject = $this;

            $this->getChannel()->basic_consume($name, $this->getQueueConsumerTag($name), false, false, false, false, function (AMQPMessage $msg) use($currentObject, $name) {
                $currentObject->processQueueMessage($name, $msg);
            });
        }
    }

    protected function queueDeclare()
    {
        foreach ($this->queues as $name => $options) {
            list($queueName, ,) = $this->getChannel()->queue_declare($name, $options['passive'],
                $options['durable'], $options['exclusive'],
                $options['auto_delete'], $options['nowait'],
                $options['arguments'], $options['ticket']);

            if (isset($options['routing_keys']) && count($options['routing_keys']) > 0) {
                foreach ($options['routing_keys'] as $routingKey) {
                    $this->queueBind($queueName, $this->exchangeOptions['name'], $routingKey);
                }
            } else {
                $this->queueBind($queueName, $this->exchangeOptions['name'], $this->routingKey);
            }
        }

        $this->queueDeclared = true;
    }

    public function processQueueMessage($queueName, AMQPMessage $msg)
    {
        if (!isset($this->queues[$queueName])) {
            throw new QueueNotFoundException();
        }

        $this->processMessageQueueCallback($msg, $queueName, $this->queues[$queueName]['callback']);
    }

    public function stopConsuming()
    {
        foreach ($this->queues as $name => $options) {
            $this->getChannel()->basic_cancel($this->getQueueConsumerTag($name), false, true);
        }
    }

    /**
     * Merges static and provided queues into one array
     */
    protected function mergeQueues()
    {
        if ($this->queuesProvider) {
            $this->queues = array_merge(
                $this->queues,
                $this->queuesProvider->getQueues($this->context)
            );
        }
    }
}
