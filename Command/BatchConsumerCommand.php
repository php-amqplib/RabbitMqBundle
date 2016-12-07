<?php

namespace OldSound\RabbitMqBundle\Command;

class BatchConsumerCommand extends BaseConsumerCommand
{
    protected function configure()
    {
        parent::configure();
        $this
            ->setName('rabbitmq:batch-consumer')
            ->setDescription('Executes a batch consumer')
        ;
    }

    protected function getConsumerService()
    {
        return 'old_sound_rabbit_mq.%s_batch';
    }
}