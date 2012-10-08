<?php

namespace OldSound\RabbitMqBundle\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InitPurgeQueueCommand extends BaseRabbitMqCommand
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('rabbitmq:init:purge-queue')
            ->addOption('queue', null, InputOption::VALUE_OPTIONAL, 'Purges messages only for that queue')
            ->setDescription('Purges queues messages')
            ->setHelp(<<<EOT
The <info>rabbitmq:init:purge-queue</info> purges queues messages.

Careful, by default the command will purges all queues messages.
In order to purge only messages of one specific queue, use the --queue option.

<info>php app/console rabbitmq:init:purge-queue --queue=my_queue</info>
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
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->isInteractive()) {
            $dialog = $this->getHelperSet()->get('dialog');
            if (!$dialog->askConfirmation($output, '<question>Careful, all non-processed messages will be purged. Do you want to continue Y/N ?</question>', false)) {
                return;
            }
        }

        if ($input->hasOption('queue')) {
            $this->getManager()->purgeQueues($input->getOption('queue'));
        } else {
            $this->getManager()->purgeQueues();
        }
    }

    /**
     * @return \OldSound\RabbitMqBundle\RabbitMq\Manager
     */
    public function getManager()
    {
        return $this->getContainer()->get('old_sound_rabbit_mq.manager');
    }
}