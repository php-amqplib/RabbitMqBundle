<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

/**
 * Producer, that publishes AMQP Messages
 */
class Producer extends BaseAmqp implements ProducerInterface
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
        $msg = $this->createAMQPMessage($msgBody, $additionalProperties, $headers);

        $this->getChannel()->basic_publish($msg, $this->exchangeOptions['name'], (string)$routingKey);
        $this->logger->debug('AMQP message published', array(
            'amqp' => array(
                'body' => $msgBody,
                'routingkeys' => $routingKey,
                'properties' => $additionalProperties,
                'headers' => $headers
            )
        ));
    }

    /**
     * Adds a message to an array to be batch published later on
     * Merges additional properties with basic properties
     *
     * @param string $msgBody
     * @param string $routingKey
     * @param array $additionalProperties
     * @param array $headers
     */
    public function addToBatch($msgBody, $routingKey = '', $additionalProperties = array(), array $headers = null)
    {
        $msg = $this->createAMQPMessage($msgBody, $additionalProperties, $headers);

        $this->getChannel()->batch_basic_publish($msg, $this->exchangeOptions['name'], (string)$routingKey);
        $this->logger->debug('AMQP message added to batch', array(
            'amqp' => array(
                'body' => $msgBody,
                'routingkeys' => $routingKey,
                'properties' => $additionalProperties,
                'headers' => $headers
            )
        ));
    }

    /**
     * Publish the messages that are in the batch
     */
    public function publishBatch()
    {
        $this->getChannel()->publish_batch();
        $this->logger->debug('AMQP published batch');
    }

    /**
     * @param string $msgBody
     * @param array $additionalProperties
     * @param array $headers
     *
     * @return AMQPMessage
     */
    private function createAMQPMessage($msgBody, $additionalProperties = array(), array $headers = null)
    {
        if ($this->autoSetupFabric) {
            $this->setupFabric();
        }

        $msg = new AMQPMessage((string)$msgBody, array_merge($this->getBasicProperties(), $additionalProperties));

        if (!empty($headers)) {
            $headersTable = new AMQPTable($headers);
            $msg->set('application_headers', $headersTable);
        }

        return $msg;
    }
}
