<?php

namespace OldSound\RabbitMqBundle\Command;

use Symfony\Component\Console\Input\InputArgument;

class RpcServerCommand extends BaseConsumerCommand
{
    protected function configure(): void
    {
        parent::configure();

        $this
            ->setName('rabbitmq:rpc-server')
            ->setDescription('Start an RPC server')
        ;

        // Restore an RPC-specific description for the "name" argument, inherited
        // from BaseConsumerCommand as "Consumer Name".
        $this->getDefinition()->setArguments([
            new InputArgument('name', InputArgument::REQUIRED, 'Server Name'),
        ]);
    }

    protected function getConsumerService()
    {
        return 'old_sound_rabbit_mq.%s_server';
    }
}
