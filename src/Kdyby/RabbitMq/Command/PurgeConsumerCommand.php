<?php

declare(strict_types = 1);

namespace Kdyby\RabbitMq\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PurgeConsumerCommand extends \Symfony\Component\Console\Command\Command
{

	/**
	 * @inject
	 * @var \Kdyby\RabbitMq\Connection
	 */
	public $connection;

	protected function configure(): void
	{
		$this
			->setName('rabbitmq:purge')
			->setDescription('Purges all messages in queue associated with given consumer')
			->addArgument('name', InputArgument::REQUIRED, 'Consumer Name')
			->addOption('no-confirmation', NULL, InputOption::VALUE_NONE, 'Whether it must be confirmed before purging');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$noConfirmation = (bool) $input->getOption('no-confirmation');

		if (!$noConfirmation && $input->isInteractive()) {
			$confirmation = $this->getHelper('dialog')->askConfirmation($output, \sprintf('<question>Are you sure you wish to purge "%s" queue? (y/n)</question>', $input->getArgument('name')), FALSE);
			if (!$confirmation) {
				$output->writeln('<error>Purging cancelled!</error>');

				return 1;
			}
		}

		/** @var \Kdyby\RabbitMq\Consumer $consumer */
		$consumer = $this->connection->getConsumer($input->getArgument('name'));
		$consumer->purge();

		return 0;
	}

}
