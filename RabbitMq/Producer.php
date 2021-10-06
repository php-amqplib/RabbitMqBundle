<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use Swaggest\JsonSchema\Schema;
use Swaggest\JsonSchema\Exception;

/**
 * Producer, that publishes AMQP Messages
 */
class Producer extends BaseAmqp implements ProducerInterface
{
    protected $contentType = 'text/plain';
    protected $deliveryMode = 2;
    protected $defaultRoutingKey = '';
    public $jsonSchemaCheck = false;
    public $jsonSchemaFile = "";

    public function setJsonSchemaFile($jsonSchemaFile){
        $this->jsonSchemaFile = $jsonSchemaFile;
    }

    public function setJsonSchemaCheck($jsonSchemaCheck)
    {
        $this->jsonSchemaCheck = $jsonSchemaCheck;
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

    public function validateJsonMessage($msg){
        try{
            $schema = Schema::import(json_decode(file_get_contents($this->jsonSchemaFile, true)));
            $schema->in($msg);
        }catch (Exception $e){
            throw new Exception($e->getMessage());
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
        if ($this->contentType == 'application/json' && $this->jsonSchemaCheck){
            $this->validateJsonMessage($msgBody);
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
