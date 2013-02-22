<?php

namespace OldSound\RabbitMqBundle\Command;

class ConsumerCommand extends BaseConsumerCommand
{

    protected function configure()
    {
        parent::configure();

        $this->setName('rabbitmq:consumer');
    }

    protected function getConsumerService()
    {
        return 'old_sound_rabbit_mq.%s_consumer';
    }
}
