<?php

namespace OldSound\RabbitMqBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
