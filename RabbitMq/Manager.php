<?php

namespace OldSound\RabbitMqBundle\RabbitMq;


class Manager
{
    /**
     * @var ConfigPool
     */
    private $configPool;

    public function __construct(ConfigPool $configPool)
    {
        $this->configPool = $configPool;
    }

    /**
     * @param  string $name
     *
     * @return Producer
     */
    public function getProducer($name)
    {
        return $this->configPool->getProducer($name);
    }

    /**
     * @param string $producer
     * @param string $msgBody
     * @param string $routingKey
     *
     * @return void
     */
    public function publish($producer, $msgBody, $routingKey = '')
    {
        $this->getProducer($producer)->publish($msgBody, $routingKey);
    }

    public function initBindings()
    {
        foreach($this->configPool->getQueues() as $queue) {
            // TODO: Improve this! Not necessary the default connection
            $ch = $this->configPool->getDefaultConnection()->channel();

            // queue
            list($queueName, ,) = $ch->queue_declare(
                $queue->getName(),
                $queue->getOptions()->get('passive'),
                $queue->getOptions()->get('durable'),
                $queue->getOptions()->get('exclusive'),
                $queue->getOptions()->get('auto_delete'),
                $queue->getOptions()->get('nowait'),
                $queue->getOptions()->get('arguments'),
                $queue->getOptions()->get('ticket')
            );

            // bindings
            if ($queue->getOptions()->has('bindings')) {
                foreach($queue->getOptions()->get('bindings') as $binding) {
                    $exchange = $this->configPool->getExchange($binding['exchange']);
                    $ch->exchange_declare(
                        $exchange->getName(),
                        $exchange->getOptions()->get('type'),
                        $exchange->getOptions()->get('passive'),
                        $exchange->getOptions()->get('durable'),
                        $exchange->getOptions()->get('auto_delete'),
                        $exchange->getOptions()->get('internal'),
                        $exchange->getOptions()->get('nowait'),
                        $exchange->getOptions()->get('arguments'),
                        $exchange->getOptions()->get('ticket')
                    );

                    $ch->queue_bind($queueName, $binding['exchange'], $binding['routing_key']);
                }
            }
        }

        foreach($this->configPool->getExchanges() as $exchange) {
            // TODO: Improve this! Not necessary the default connection
            $ch = $this->configPool->getDefaultConnection()->channel();

            $ch->exchange_declare(
                $exchange->getName(),
                $exchange->getOptions()->get('type'),
                $exchange->getOptions()->get('passive'),
                $exchange->getOptions()->get('durable'),
                $exchange->getOptions()->get('auto_delete'),
                $exchange->getOptions()->get('internal'),
                $exchange->getOptions()->get('nowait'),
                $exchange->getOptions()->get('arguments'),
                $exchange->getOptions()->get('ticket')
            );

            // bindings
            if ($exchange->getOptions()->has('bindings')) {
                foreach($exchange->getOptions()->get('bindings') as $binding) {
                    // queue
                    $queue = $this->configPool->getQueue($binding['queue']);
                    list($queueName, ,) = $ch->queue_declare(
                        $queue->getName(),
                        $queue->getOptions()->get('passive'),
                        $queue->getOptions()->get('durable'),
                        $queue->getOptions()->get('exclusive'),
                        $queue->getOptions()->get('auto_delete'),
                        $queue->getOptions()->get('nowait'),
                        $queue->getOptions()->get('arguments'),
                        $queue->getOptions()->get('ticket')
                    );

                    $ch->queue_bind($queueName, $exchange->getName(), $binding['routing_key']);
                }
            }
        }
    }

    public function purgeQueues($queue = null)
    {
        foreach($this->configPool->getQueues() as $q) {
            // TODO: Improve this! Not necessary the default connection
            $ch = $this->configPool->getDefaultConnection()->channel();
            if (null === $queue) {
                $ch->queue_purge($q->getName());
            } elseif ($queue === $q->getName()) {
                $ch->queue_purge($q->getName());
                break;
            }
        }
    }
}