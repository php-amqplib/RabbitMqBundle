<?php

namespace OldSound\RabbitMqBundle\Command;

class GroupConsumerCommand extends BaseConsumerCommand
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('rabbitmq:group:consumer')
            ->setDescription('Synchronous execute grouped consumers')
        ;
    }

    protected function getConsumerService()
    {
        return 'old_sound_rabbit_mq.%s_group';
    }
}