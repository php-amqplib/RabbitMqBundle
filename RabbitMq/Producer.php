<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

use OldSound\RabbitMqBundle\RabbitMq\BaseAmqp;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * Prodcuer, that publishes AMQP Messages
 */
class Producer extends BaseAmqp
{
    protected $contentType = 'text/plain';
    protected $deliveryMode = 2;

    public function setContentType($contentType)
    {
        $this->contentType = $contentType;

        return $this;
    }

    public function setDeliveryMode($deliveryMode)
    {
        $this->deliveryMode = $deliveryMode;

        return $this;
    }
    
    /**
     * Publishes the message and merges additional properties with basic properties
     *
     * @param string $msgBody
     * @param string $routingKey
     * @param array $additionalProperties
     */
    public function publish($msgBody, $routingKey = '', $additionalProperties = array())
    {
        if ($this->autoSetupFabric) {
            $this->setupFabric();
        }

        $msg = new AMQPMessage((string) $msgBody, array_merge($this->basicProperties, $additionalProperties));
        $this->ch->basic_publish($msg, $this->exchangeOptions['name'], (string) $routingKey);
    }
}
