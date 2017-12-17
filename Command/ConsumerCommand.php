<?php

namespace OldSound\RabbitMqBundle\Command;

use OldSound\RabbitMqBundle\DependencyInjection\ServiceNameFormat;

class ConsumerCommand extends BaseConsumerCommand
{
    protected function configure()
    {
        parent::configure();
        $this->setDescription('Executes a consumer');
        $this->setName('rabbitmq:consumer');
    }

    protected function getConsumerService()
    {
        return ServiceNameFormat::CONSUMER;
    }
}
