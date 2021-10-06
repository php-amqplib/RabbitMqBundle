<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

/**
 * Producer, that publishes AMQP Messages
 */
class Producer extends BaseAmqp implements ProducerInterface
{
    protected $contentType = 'text/plain';
    protected $deliveryMode = 2;
    protected $defaultRoutingKey = '';
    protected $acknowledged = true;
    protected $confirmationTimeout = 0;
    protected $confirmSelect = false;

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

    /**
     * @param null $confirmationTimeout
     */
    public function setConfirmationTimeout($confirmationTimeout): void
    {
        $this->confirmationTimeout = intval($confirmationTimeout);
    }

    /**
     * @param AbstractConnection $conn
     * @param AMQPChannel|null $ch
     * @param null $consumerTag
     * @param bool $confirmSelect
     */
    public function __construct(AbstractConnection $conn, AMQPChannel $ch = null, $consumerTag = null, bool $confirmSelect = false)
    {
        parent::__construct($conn, $ch, $consumerTag);
        $this->confirmSelect = $confirmSelect;
        $this->initializeProducer();
    }

    /**
     * Publishes the message and merges additional properties with basic properties
     *
     * @param string $msgBody
     * @param string $routingKey
     * @param array $additionalProperties
     * @param array|null $headers
     * @return bool
     */
    public function publish($msgBody, $routingKey = '', $additionalProperties = array(), array $headers = null): bool
    {
        if ($this->autoSetupFabric) {
            $this->setupFabric();
        }

        $msg = new AMQPMessage((string) $msgBody, array_merge($this->getBasicProperties(), $additionalProperties));

        if (!empty($headers)) {
            $headersTable = new AMQPTable($headers);
            $msg->set('application_headers', $headersTable);
        }

        $real_routingKey = !empty($routingKey) ? $routingKey : $this->defaultRoutingKey;
        $this->getChannel()->basic_publish($msg, $this->exchangeOptions['name'], (string)$real_routingKey);
        $this->getChannel()->wait_for_pending_acks($this->confirmationTimeout);
        $this->logger->debug('AMQP message published', array(
            'amqp' => array(
                'body' => $msgBody,
                'routingkeys' => $routingKey,
                'properties' => $additionalProperties,
                'headers' => $headers
            )
        ));
        return $this->acknowledged;
    }

    public function reconnect()
    {
        parent::reconnect();
        $this->initializeProducer();
    }

    /**
     */
    protected function initializeProducer(): void
    {
        if ($this->confirmSelect) {
            $this->getChannel()->confirm_select();
            $this->getChannel()->set_ack_handler(
                function (AMQPMessage $message) {
                    $this->acknowledged = true;
                }
            );

            $this->getChannel()->set_nack_handler(
                function (AMQPMessage $message) {
                    $this->acknowledged = false;
                }
            );
        }
    }
}
