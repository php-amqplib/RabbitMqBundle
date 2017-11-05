<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

/**
 * Producer, that publishes AMQP Messages
 * Channel is set in confirm mode
 */
class PublisherConfirmsProducer extends Producer
{
    protected $publisherConfirmsEnabled = false;
    protected $publisherConfirmsTimeout = 3;

    public function getPublisherConfirmsTimeout()
    {
        return $this->publisherConfirmsTimeout;
    }

    public function setPublisherConfirmsTimeout($publisherConfirmsTimeout)
    {
        $this->publisherConfirmsTimeout = $publisherConfirmsTimeout;

        return $this;
    }

    protected function beforePublish()
    {
        if (!$this->publisherConfirmsEnabled) {
            $this->getChannel()->confirm_select();
            /**
             * To avoid having to call confirm_select more than once.
             */
            $this->publisherConfirmsEnabled = true;
        }
    }

    protected function afterPublishBeforeLogPublished()
    {
        $this->getChannel()->wait_for_pending_acks_returns($this->publisherConfirmsTimeout);
    }
}
