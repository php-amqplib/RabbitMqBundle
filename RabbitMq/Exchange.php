<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

use Symfony\Component\HttpFoundation\ParameterBag;

class Exchange
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var array
     */
    protected $defaultOptions = array(
        'passive' => false,
        'durable' => true,
        'auto_delete' => false,
        'internal' => false,
        'nowait' => false,
        'arguments' => null,
        'ticket' => null
    );

    /**
     * @var ParameterBag
     */
    protected $options;


    public function __construct($name, array $options = array())
    {
        $this->name = $name;
        $this->setOptions($options);
    }

    public function getName()
    {
        return $this->name;
    }

    /**
     * @return ParameterBag
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param array $options
     */
    public function setOptions(array $options)
    {
        $this->options = new ParameterBag(array_merge($this->defaultOptions, $options));
    }
}