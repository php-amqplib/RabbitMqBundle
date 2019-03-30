<?php

declare(strict_types = 1);

namespace Kdyby\RabbitMq\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class StdInProducerCommand extends \Symfony\Component\Console\Command\Command
{

	/**
	 * @inject
	 * @var \Kdyby\RabbitMq\Connection
	 */
	public $connection;

	protected function configure(): void
	{
		$this
			->setName('rabbitmq:stdin-producer')
			->setDescription('Creates message from given STDIN and passes it to configured producer')
			->addArgument('name', InputArgument::REQUIRED, 'Producer Name')
			->addOption('debug', 'd', InputOption::VALUE_OPTIONAL, 'Enable Debugging', FALSE);
	}

	/**
	 * Executes the current command.
	 *
	 * @param \Symfony\Component\Console\Input\InputInterface $input An InputInterface instance
	 * @param \Symfony\Component\Console\Output\OutputInterface $output An OutputInterface instance
	 */
	protected function execute(InputInterface $input, OutputInterface $output): void
	{
		\define('AMQP_DEBUG', (bool) $input->getOption('debug'));

		$producer = $this->connection->getProducer($input->getArgument('name'));

		$data = '';
		while (!\feof(STDIN)) {
			$data .= \fread(STDIN, 8192);
		}

		$producer->publish(\serialize($data));
	}

}
