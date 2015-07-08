<?php

namespace OldSound\RabbitMqBundle\Command;

class MultipleConsumerCommand extends BaseConsumerCommand
{

    protected function configure()
    {
        parent::configure();
        
        $this->setDescription('Executes a consumer that uses multiple queues');
        $this->setName('rabbitmq:multiple-consumer');
    }

    protected function getConsumerService()
    {
        return 'old_sound_rabbit_mq.%s_multiple';
    }
}
