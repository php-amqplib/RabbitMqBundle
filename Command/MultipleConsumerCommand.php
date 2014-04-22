<?php

namespace OldSound\RabbitMqBundle\Command;

class MultipleConsumerCommand extends BaseConsumerCommand
{

    protected function configure()
    {
        parent::configure();

        $this->setName('rabbitmq:multiple-consumer');
    }

    protected function getConsumerService()
    {
        return 'old_sound_rabbit_mq.%s_multiple';
    }
}
