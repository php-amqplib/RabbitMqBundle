<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

use OldSound\RabbitMqBundle\RabbitMq\BaseAmqp;
use PhpAmqpLib\Message\AMQPMessage;

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

    public function publish($msgBody, $routingKey = '')
    {
        if ($this->autoSetupFabric) {
            $this->setupFabric();
        }

        $msg = new AMQPMessage($msgBody, array('content_type' => $this->contentType, 'delivery_mode' => $this->deliveryMode));
        $this->getChannel()->basic_publish($msg, $this->exchangeOptions['name'], $routingKey);
    }
}
