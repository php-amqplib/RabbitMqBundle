<?php

namespace OldSound\RabbitMqBundle\Declarations;

use OldSound\RabbitMqBundle\Receiver\BatchReceiverInterface;

/**
 * @property BatchReceiverInterface $receiver
 */
class BatchConsumeOptions extends ConsumeOptions
{
    /** @var int */
    public $batchCount;
}