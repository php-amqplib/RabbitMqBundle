<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

use OldSound\RabbitMqBundle\RabbitMq\BaseConsumer;
use PhpAmqpLib\Message\AMQPMessage;

class Consumer extends BaseConsumer
{
    
    protected $memoryLimit;
    
    public function consume($msgAmount, $memoryLimit = 128)
    {
        $this->target = $msgAmount;
        
        $this->memoryLimit = $memoryLimit;

        $this->setUpConsumer();

        while (count($this->ch->callbacks))
        {
            $this->maybeStopConsumer();
            $this->ch->wait();
        }
    }

    public function processMessage(AMQPMessage $msg)
    {
        if (false === call_user_func($this->callback, $msg)) {
            // Reject and requeue message to RabbitMQ
            $msg->delivery_info['channel']->basic_reject($msg->delivery_info['delivery_tag'], true);
        }
        else {
            // Remove message from queue only if callback return not false
            $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
        }

        $this->consumed++;
        $this->maybeStopConsumer();
        
        if($this->isRamAlmostOverloaded()){
            
            $this->stopConsuming();
        }
    }
    
    /**
     * Checks if memory in use is greater or equal than memory allowed for this process
     */
    protected function isRamAlmostOverloaded(){
        
        $memoryLimit = $this->memoryToBytes($this->memoryLimit - 5);
        if (memory_get_usage(true) >= $memoryLimit) {

            return true;
        }
        
        return false;
        
    }
    
    private function memoryToBytes($memory){
        
        return ($memory * 1024 * 1024);
    }
}
