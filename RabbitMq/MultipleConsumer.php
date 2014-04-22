<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

use OldSound\RabbitMqBundle\RabbitMq\Exception\QueueNotFoundException;
use PhpAmqpLib\Message\AMQPMessage;

class MultipleConsumer extends Consumer
{
    protected $queues = array();

    public function getQueueConsumerTag($queue)
    {
        return sprintf('%s-%s', $this->getConsumerTag(), $queue);
    }

    public function setQueues(array $queues)
    {
        $this->queues = $queues;
    }

    protected function setupConsumer()
    {
        if ($this->autoSetupFabric) {
            $this->setupFabric();
        }

        foreach ($this->queues as $name => $options) {
            //PHP 5.3 Compliant
            $currentObject = $this;

            $this->getChannel()->basic_consume($name, $this->getQueueConsumerTag($name), false, false, false, false, function (AMQPMessage $msg) use($currentObject, $name) {
                $this->processQueueMessage($name, $msg);
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
                    $this->getChannel()->queue_bind($queueName, $this->exchangeOptions['name'], $routingKey);
                }
            } else {
                $this->getChannel()->queue_bind($queueName, $this->exchangeOptions['name'], $this->routingKey);
            }
        }

        $this->queueDeclared = true;
    }

    public function processQueueMessage($queueName, AMQPMessage $msg)
    {
        if (!isset($this->queues[$queueName])) {
            throw new QueueNotFoundException();
        }

        $processFlag = call_user_func($this->queues[$queueName]['callback'], $msg);

        $this->handleProcessMessage($msg, $processFlag);
    }
}