<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

/**
 * Producer, that publishes AMQP Messages in bulk
 */
class BatchProducer extends Producer implements BatchProducerInterface
{
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
        $msg = new AMQPMessage((string) $msgBody, array_merge($this->getBasicProperties(), $additionalProperties));

        if (!empty($headers)) {
            $headersTable = new AMQPTable($headers);
            $msg->set('application_headers', $headersTable);
        }

        $this->getChannel()->batch_basic_publish($msg, $this->exchangeOptions['name'], (string)$routingKey);

        $this->logger->debug('AMQP message published', array(
            'amqp' => array(
                'body' => $msgBody,
                'routingkeys' => $routingKey,
                'properties' => $additionalProperties,
                'headers' => $headers
            )
        ));
    }

    public function send()
    {
        if ($this->autoSetupFabric) {
            $this->setupFabric();
        }

        $this->getChannel()->publish_batch();
    }
}
