<?php

namespace OldSound\RabbitMqBundle\Command;

use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InitBindingsCommand extends BaseRabbitMqCommand
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('rabbitmq:init:bindings')
            ->setDescription('Generates exchanges, queues and bindings between them.')
            ->setHelp(<<<EOT
The <info>rabbitmq:init:bindings</info> command generates exchanges, queues automatically.

<info>php app/console rabbitmq:init:bindings</info>
EOT
            )
        ;
    }

    /**
     * Executes the current command.
     *
     * @param InputInterface  $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     *
     * @return integer 0 if everything went fine, or an error code
     *
     * @throws \LogicException When this abstract class is not implemented
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getManager()->initBindings();
    }

    /**
     * @return \OldSound\RabbitMqBundle\RabbitMq\ConfigPool
     */
    public function getConfigPool()
    {
        return $this->getContainer()->get('old_sound_rabbit_mq.config_pool');
    }

    /**
     * @return \OldSound\RabbitMqBundle\RabbitMq\Manager
     */
    public function getManager()
    {
        return $this->getContainer()->get('old_sound_rabbit_mq.manager');
    }
}