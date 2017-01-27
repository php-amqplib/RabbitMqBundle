<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

class Binding extends BaseAmqp
{
    /**
     * @var string
     */
    protected $exchange;

    /**
     * @var string
     */
    protected $destination;

    /**
     * @var bool
     */
    protected $destinationIsExchange = false;

    /**
     * @var string
     */
    protected $routingKey;

    /**
     * @var bool
     */
    protected $nowait = false;

    /**
     * @var array
     */
    protected $arguments;

    /**
     * @return string
     */
    public function getExchange()
    {
        return $this->exchange;
    }

    /**
     * @param string $exchange
     */
    public function setExchange($exchange)
    {
        $this->exchange = $exchange;
    }

    /**
     * @return string
     */
    public function getDestination()
    {
        return $this->destination;
    }

    /**
     * @param bool $destination
     */
    public function setDestination($destination)
    {
        $this->destination = $destination;
    }

    /**
     * @return bool
     */
    public function getDestinationIsExchange()
    {
        return $this->destinationIsExchange;
    }

    /**
     * @param string $destinationIsExchange
     */
    public function setDestinationIsExchange($destinationIsExchange)
    {
        $this->destinationIsExchange = $destinationIsExchange;
    }

    /**
     * @return string
     */
    public function getRoutingKey()
    {
        return $this->routingKey;
    }

    /**
     * @param string $routingKey
     */
    public function setRoutingKey($routingKey)
    {
        $this->routingKey = $routingKey;
    }

    /**
     * @return boolean
     */
    public function isNowait()
    {
        return $this->nowait;
    }

    /**
     * @param boolean $nowait
     */
    public function setNowait($nowait)
    {
        $this->nowait = $nowait;
    }

    /**
     * @return array
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * @param array $arguments
     */
    public function setArguments($arguments)
    {
        $this->arguments = $arguments;
    }


    /**
     * create bindings
     *
     * @return void
     */
    public function setupFabric()
    {
        $method  = ($this->destinationIsExchange) ? 'exchange_bind' : 'queue_bind';
        $channel = $this->getChannel();
        call_user_func(
            array($channel, $method),
            $this->destination,
            $this->exchange,
            $this->routingKey,
            $this->nowait,
            $this->arguments
        );
    }
}
