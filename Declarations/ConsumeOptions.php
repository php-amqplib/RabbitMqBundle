<?php

namespace OldSound\RabbitMqBundle\Declarations;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 */
class ConsumeOptions
{
    /** @var string */
    public $queue;
    /** @var string|null */
    public $consumerTag;
    /** @var bool */
    public $noLocal = false;
    /** @var bool */
    public $noack = false;
    /** @var bool */
    public $exclusive = false;
    /** @var bool */
    public $noAck = false;
    /** @var int */
    public $qosPrefetchCount = 0;
    /** @var int */
    public $qosPrefetchSize = 0;
    /** @var callable */
    public $receiver;
}