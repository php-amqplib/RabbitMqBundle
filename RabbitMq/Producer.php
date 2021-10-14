<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

use OldSound\RabbitMqBundle\RabbitMq\Exception\ValidationException;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use OldSound\RabbitMqBundle\RabbitMq\JsonValidator;
use OldSound\RabbitMqBundle\RabbitMq\XmlValidator;

/**
 * Producer, that publishes AMQP Messages
 */
class Producer extends BaseAmqp implements ProducerInterface
{
    protected $contentType = 'text/plain';
    protected $deliveryMode = 2;
    protected $defaultRoutingKey = '';
    public $validator = null;

    public function setValidator($validator)
    {
        $this->validator = $validator;
    }

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
        return array('content_type' => $this->contentType, 'delivery_mode' => $this->deliveryMode);
    }

    public function validateMessage($msg)
    {
        if ($this->contentType != $this->validator->getContentType()) {
            throw new ValidationException($this->contentType . " message verification failed");
        }
        
        $error = $this->validator->isValid($msg, $this->contentType);
        if ($error != null){
            throw new ValidationException($this->contentType . " message verification failed. Error was: " . $error);
        }
    }

    /**
     * Publishes the message and merges additional properties with basic properties
     *
     * @param string $msgBody
     * @param string $routingKey
     * @param array $additionalProperties
     * @param array $headers
     */
    public function publish($msgBody, $routingKey = null, $additionalProperties = array(), array $headers = null)
    {
        if ($this->validator != null){
            $this->validateMessage($msgBody);
        }

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
