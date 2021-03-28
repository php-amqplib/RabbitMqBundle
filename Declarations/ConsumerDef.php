<?php

namespace OldSound\RabbitMqBundle\Declarations;

use PhpAmqpLib\Connection\AbstractConnection;

class ConsumerDef
{
    /** @var string */
    public $name;
    /** @var AbstractConnection */
    public $connection;
    /** @var int */
    public $timeoutWait;
    /** @var ConsumeOptions[] */
    public $consumeOptions = [];
}