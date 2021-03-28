<?php

namespace OldSound\RabbitMqBundle\Command;

use OldSound\RabbitMqBundle\Declarations\DeclarationsRegistry;
use OldSound\RabbitMqBundle\Declarations\Declarator;
use OldSound\RabbitMqBundle\RabbitMq\DynamicConsumer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class DeclareCommand extends Command
{
    use ContainerAwareTrait;

    public function __construct(
        DeclarationsRegistry $declarationsRegistry
    ) {
        parent::__construct();
        $this->declarationsRegistry = $declarationsRegistry;
    }

    protected function configure()
    {
        $this
            ->setName('rabbitmq:declare')
            ->addArgument('connection', InputArgument::OPTIONAL, 'Rabbitmq connection name', 'default')
            ->setDescription('Sets up the Rabbit MQ fabric')
            ->addOption('debug', 'd', InputOption::VALUE_NONE, 'Enable Debugging')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (defined('AMQP_DEBUG') === false) {
            define('AMQP_DEBUG', (bool) $input->getOption('debug'));
        }

        $connection = $input->getArgument('connection');
        $channelAlias = sprintf('old_sound_rabbit_mq.channel.%s', $connection);
        if(!$this->container->has($channelAlias)) {
            throw new InvalidOptionException('Connection is not exist');
        };

        $channel = $this->container->get($channelAlias);

        // TODO $output->writeln('Setting up the Rabbit MQ fabric');

        $producers = [];
        $consumers = [];
        $exchanges = [];
        foreach ($producers as $producer) {
            // TODO $exchanges[] = $producer->exchange;
        }

        foreach ($consumers as $consumer) {
            // TODO $exchanges[] = $producer->exchange;
            //$bindings[] = $producer->exchange;
            //$queues[] = $producer->exchange;
        }
        
        //$this->declarationsRegistry->exchanges

        $declarator = new Declarator($channel);
        // $declarator->declareExchanges($exchanges);
        foreach ($exchanges as $exchange) {
            $declarator->declareForExchange($exchange);
        }

        return 0;

    }
}
