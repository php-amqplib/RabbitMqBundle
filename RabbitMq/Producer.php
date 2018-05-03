<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

/**
 * Producer, that publishes AMQP Messages
 */
class Producer extends BaseAmqp implements ProducerInterface
{
    protected $contentType = 'text/plain';
    protected $deliveryMode = \Interop\Amqp\AmqpMessage::DELIVERY_MODE_PERSISTENT;
    protected $deliveryDelay = null;
    protected $timeToLive = null;
    protected $priority = null;

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
     * @return null
     */
    public function getDeliveryDelay()
    {
        return $this->deliveryDelay;
    }

    /**
     * @param null $deliveryDelay
     */
    public function setDeliveryDelay($deliveryDelay)
    {
        $this->deliveryDelay = $deliveryDelay;
    }

    /**
     * @return null
     */
    public function getTimeToLive()
    {
        return $this->timeToLive;
    }

    /**
     * @param null $timeToLive
     */
    public function setTimeToLive($timeToLive)
    {
        $this->timeToLive = $timeToLive;
    }

    /**
     * @return null
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @param null $priority
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;
    }

    protected function getBasicProperties()
    {
        return array('content_type' => $this->contentType, 'delivery_mode' => $this->deliveryMode);
    }

    /**
     * Publishes the message and merges additional properties with basic properties
     *
     * @param string $msgBody
     * @param string $routingKey
     * @param array $additionalProperties
     * @param array $headers
     */
    public function publish($msgBody, $routingKey = '', $additionalProperties = array(), array $headers = null)
    {
        if ($this->autoSetupFabric) {
            $this->setupFabric();
        }

        $context = $this->getContext();

        $topic = $context->createTopic($this->exchangeOptions['name']);

        $message = $context->createMessage((string) $msgBody, [], array_merge($this->getBasicProperties(), $additionalProperties));
        $message->setRoutingKey($routingKey);

        if (!empty($headers)) {
            $message->setHeaders($headers);
        }

        $producer = $context->createProducer();

        if (null !== $this->deliveryDelay) {
            $producer->setDeliveryDelay($this->deliveryDelay);
        }
        if (null !== $this->timeToLive) {
            $producer->setTimeToLive($this->timeToLive);
        }
        if (null !== $this->priority) {
            $producer->setPriority($this->priority);
        }

        $producer->send($topic, $message);

        $this->logger->debug('AMQP message published', array(
            'amqp' => array(
                'body' => $msgBody,
                'routingkeys' => $routingKey,
                'properties' => $additionalProperties,
                'headers' => $headers
            )
        ));
    }
}
