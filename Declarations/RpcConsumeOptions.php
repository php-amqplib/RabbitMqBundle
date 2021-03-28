<?php

namespace OldSound\RabbitMqBundle\Declarations;

use OldSound\RabbitMqBundle\Producer\ProducerInterface;
use OldSound\RabbitMqBundle\RPC\RpcClient;

class RpcConsumeOptions extends ConsumeOptions
{
    /** @var string */
    public $replayToProperty = RpcClient::PROPERTY_REPLAY_TO;
    /** @var string */
    public $correlationIdProperty = RpcClient::PROPERTY_CORRELATION_ID;
    /** @var array */
    public $replayMessageProperties = [
        'content_type' => 'text/plain',
        'delivery_mode' => ProducerInterface::DELIVERY_MODE_PERSISTENT
    ];
}