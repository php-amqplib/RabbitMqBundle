<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

use PhpAmqpLib\Connection\AMQPConnection;
use OldSound\RabbitMqBundle\RabbitMq\Producer;
use OldSound\RabbitMqBundle\RabbitMq\Consumer;

class ConfigPool
{
    /** @var AMQPConnection[] */
    protected $connections;

    /** @var AMQPConnection */
    protected $defaultConnection;

    /** @var Exchange[] */
    protected $exchanges;

    /** @var Producer[] */
    protected $producers;

    /** @var  Consumer[] */
    protected $consumers;

    /** @var  Queue[] */
    protected $queues;


    public function __construct()
    {
        $this->connections = array();
        $this->exchanges = array();
        $this->producers = array();
        $this->consumers = array();
        $this->queues = array();
    }

    /**
     * @param string   $name
     * @param Producer $producer
     *
     * @return ConfigPool Fluent interface
     */
    public function addProducer($name, Producer $producer)
    {
        $this->producers[$name] = $producer;

        return $this;
    }

    /**
     * @param string   $name
     * @param Consumer $consumer
     *
     * @return ConfigPool Fluent interface
     */
    public function addConsumer($name, Consumer $consumer)
    {
        $this->consumers[$name] = $consumer;

        return $this;
    }

    /**
     * @return Consumer[]
     */
    public function getConsumers()
    {
        return $this->consumers;
    }

    /**
     * @return Producer[]
     */
    public function getProducers()
    {
        return $this->producers;
    }

    /**
     * @param  string $name
     *
     * @return Producer
     * @throws \InvalidArgumentException
     */
    public function getProducer($name)
    {
        if (!$this->hasProducer($name)) {
            throw new \InvalidArgumentException(sprintf('Producer with name "%s" doesn\'t exist', $name));
        }

        return $this->producers[$name];
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasProducer($name)
    {
        return isset($this->producers[$name]);
    }

    /**
     * @param string         $name
     * @param AMQPConnection $connection
     *
     * @return ConfigPool Fluent interface
     */
    public function addConnection($name, AMQPConnection $connection)
    {
        $this->connections[$name] = $connection;

        return $this;
    }

    /**
     * @param string $name
     *
     * @return array
     * @throws \InvalidArgumentException
     */
    public function getConnection($name)
    {
        if (!$this->hasConnection($name)) {
            throw new \InvalidArgumentException(sprintf('Connection with name "%s" doesn\'t exist', $name));
        }

        return $this->connections[$name];
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasConnection($name)
    {
        return isset($this->connections[$name]);
    }

    /**
     * @param string   $name
     * @param Exchange $exchange
     *
     * @return ConfigPool Fluent interface
     */
    public function addExchange($name, Exchange $exchange)
    {
        $this->exchanges[$name] = $exchange;

        return $this;
    }

    /**
     * @return array|Exchange[]
     */
    public function getExchanges()
    {
        return $this->exchanges;
    }

    /**
     * @param string $name
     *
     * @return Exchange
     * @throws \InvalidArgumentException
     */
    public function getExchange($name)
    {
        if (!$this->hasExchange($name)) {
            throw new \InvalidArgumentException(sprintf('Exchange with name "%s" doesn\'t exist', $name));
        }

        return $this->exchanges[$name];
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasExchange($name)
    {
        return isset($this->exchanges[$name]);
    }

    /**
     * @param string $name
     * @param Queue  $queue
     *
     * @return ConfigPool Fluent interface
     */
    public function addQueue($name, Queue $queue)
    {
        $this->queues[$name] = $queue;

        return $this;
    }

    /**
     * @return Queue[]
     */
    public function getQueues()
    {
        return $this->queues;
    }

    /**
     * @param string $name
     *
     * @return Queue
     * @throws \InvalidArgumentException
     */
    public function getQueue($name)
    {
        if (!$this->hasQueue($name)) {
            throw new \InvalidArgumentException(sprintf('Queue with name "%s" doesn\'t exist', $name));
        }

        return $this->queues[$name];
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasQueue($name)
    {
        return isset($this->queues[$name]);
    }

    /**
     * @param AMQPConnection $defaultConnection
     *
     * @return ConfigPool Fluent interface
     */
    public function setDefaultConnection(AMQPConnection $defaultConnection)
    {
        $this->defaultConnection = $defaultConnection;

        return $this;
    }

    /**
     * @return AMQPConnection
     */
    public function getDefaultConnection()
    {
        return $this->defaultConnection;
    }
}