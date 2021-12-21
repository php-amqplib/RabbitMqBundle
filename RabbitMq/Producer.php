<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

/**
 * Producer, that publishes AMQP Messages
 */
class Producer extends BaseAmqp implements ProducerInterface
{
    public const DEFAULT_CONTENT_TYPE = 'text/plain';
    protected $contentType = Producer::DEFAULT_CONTENT_TYPE;
    protected $deliveryMode = 2;
    protected $defaultRoutingKey = '';

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

    public function setDefaultRoutingKey($defaultRoutingKey)
    {
        $this->defaultRoutingKey = $defaultRoutingKey;

        return $this;
    }

    protected function getBasicProperties()
    {
        return ['content_type' => $this->contentType, 'delivery_mode' => $this->deliveryMode];
    }

    /**
     * Publishes the message and merges additional properties with basic properties
     *
     * @param string $msgBody
     * @param string $routingKey
     * @param array $additionalProperties
     * @param array $headers
     */
    public function publish($msgBody, $routingKey = null, $additionalProperties = [], array $headers = null)
    {
        if ($this->autoSetupFabric) {
            $this->setupFabric();
        }

        $msg = new AMQPMessage((string) $msgBody, array_merge($this->getBasicProperties(), $additionalProperties));

        if (!empty($headers)) {
            $headersTable = new AMQPTable($headers);
            $msg->set('application_headers', $headersTable);
        }

        $real_routingKey = $routingKey !== null ? $routingKey : $this->defaultRoutingKey;
        $this->getChannel()->basic_publish($msg, $this->exchangeOptions['name'], (string)$real_routingKey);
        $this->logger->debug('AMQP message published', [
            'amqp' => [
                'body' => $msgBody,
                'routingkeys' => $routingKey,
                'properties' => $additionalProperties,
                'headers' => $headers,
            ],
        ]);
    }
}
