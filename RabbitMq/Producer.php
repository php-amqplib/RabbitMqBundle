<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

use OldSound\RabbitMqBundle\Event\AfterProducerPublishMessageEvent;
use OldSound\RabbitMqBundle\Event\BeforeProducerPublishMessageEvent;
use PhpAmqpLib\Channel\AMQPChannel;
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

    /**
     * @var bool Whether the producer's channel is put into publisher-confirm mode.
     */
    protected $confirmSelect = false;

    /**
     * @var float Seconds to wait for a publisher confirmation. 0 waits indefinitely.
     */
    protected $confirmationTimeout = 10.0;

    /**
     * @var bool Outcome of the most recent publish() while confirms are enabled.
     */
    private $acknowledged = true;

    /**
     * @var AMQPChannel|null The channel confirm mode has already been enabled on.
     */
    private $confirmSelectChannel = null;

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

    /**
     * @param bool $confirmSelect
     *
     * @return $this
     */
    public function setConfirmSelect(bool $confirmSelect)
    {
        $this->confirmSelect = $confirmSelect;

        return $this;
    }

    public function isConfirmSelect(): bool
    {
        return $this->confirmSelect;
    }

    /**
     * @param float $confirmationTimeout Seconds to wait for a confirmation. 0 waits indefinitely.
     *
     * @return $this
     */
    public function setConfirmationTimeout(float $confirmationTimeout)
    {
        $this->confirmationTimeout = $confirmationTimeout;

        return $this;
    }

    public function getConfirmationTimeout(): float
    {
        return $this->confirmationTimeout;
    }

    protected function getBasicProperties()
    {
        return ['content_type' => $this->contentType, 'delivery_mode' => $this->deliveryMode];
    }

    /**
     * {@inheritdoc}
     *
     * Puts the channel into publisher-confirm mode the first time it is handed out, and again
     * whenever the underlying channel object has been replaced - by a reconnect, by the parent
     * recreating a closed channel, or by an explicit setChannel().
     */
    public function getChannel()
    {
        $channel = parent::getChannel();

        if ($this->confirmSelect && $channel !== $this->confirmSelectChannel) {
            $this->enableConfirmSelect($channel);
        }

        return $channel;
    }

    private function enableConfirmSelect(AMQPChannel $channel): void
    {
        $channel->confirm_select();

        $channel->set_ack_handler(function (AMQPMessage $message) {
            $this->acknowledged = true;
        });

        $channel->set_nack_handler(function (AMQPMessage $message) {
            $this->acknowledged = false;
        });

        $this->confirmSelectChannel = $channel;
    }

    /**
     * Publishes the message and merges additional properties with basic properties
     *
     * @param string $msgBody
     * @param string $routingKey
     * @param array $additionalProperties
     * @param array $headers
     *
     * @return bool False only when the broker explicitly nacked the message. Always true when
     *              publisher confirms are disabled for this producer.
     *
     * @throws \PhpAmqpLib\Exception\AMQPTimeoutException When confirms are enabled and no
     *                                                    confirmation arrived within
     *                                                    confirm_timeout seconds.
     */
    public function publish($msgBody, $routingKey = null, $additionalProperties = [], ?array $headers = null)
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

        $this->dispatchEvent(
            BeforeProducerPublishMessageEvent::NAME,
            new BeforeProducerPublishMessageEvent($this, $msg, $real_routingKey)
        );

        $channel = $this->getChannel();

        $this->acknowledged = true;
        $channel->basic_publish($msg, $this->exchangeOptions['name'], (string)$real_routingKey);

        if ($this->confirmSelect) {
            $channel->wait_for_pending_acks($this->confirmationTimeout);
        }

        $this->logger->debug('AMQP message published', [
            'amqp' => [
                'body' => $msgBody,
                'routingkey' => $real_routingKey,
                'properties' => $additionalProperties,
                'headers' => $headers,
            ],
        ]);

        $this->dispatchEvent(
            AfterProducerPublishMessageEvent::NAME,
            new AfterProducerPublishMessageEvent($this, $msg, $real_routingKey)
        );

        return $this->acknowledged;
    }
}
