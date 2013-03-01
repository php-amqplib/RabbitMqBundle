<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

use OldSound\RabbitMqBundle\RabbitMq\BaseConsumer;
use PhpAmqpLib\Message\AMQPMessage;

class Consumer extends BaseConsumer
{
    /**
     * @var int $memoryLimit
     */
    protected $memoryLimit = null;

    /**
     * Set the memory limit
     * 
     * @param int $memoryLimit
     */
    public function setMemoryLimit($memoryLimit)
    {
        $this->memoryLimit = $memoryLimit;
    }

    /**
     * Get the memory limit
     * 
     * @return int
     */
    public function getMemoryLimit()
    {
        return $this->memoryLimit;   
    }

    /**
     * Consume the message
     * 
     * @param int $msgAmount
     */
    public function consume($msgAmount)
    {
        $this->target = $msgAmount;

        $this->setupConsumer();

        while (count($this->ch->callbacks)) {
            $this->maybeStopConsumer();
            $this->ch->wait();
        }
    }

    public function processMessage(AMQPMessage $msg)
    {
        if (false === call_user_func($this->callback, $msg)) {
            // Reject and requeue message to RabbitMQ
            $msg->delivery_info['channel']->basic_reject($msg->delivery_info['delivery_tag'], true);
        } else {
            // Remove message from queue only if callback return not false
            $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
        }

        $this->consumed++;
        $this->maybeStopConsumer();

        if (!is_null($this->getMemoryLimit()) && $this->isRamAlmostOverloaded()) {     
            $this->stopConsuming();
        }
    }

    /**
     * Checks if memory in use is greater or equal than memory allowed for this process
     *
     * @return boolean
     */
    protected function isRamAlmostOverloaded()
    {
        if (memory_get_usage(true) >= ($this->getMemoryLimit() * 1024 * 1024)) {
            return true;
        } else {
            return false;
        }
    }
}