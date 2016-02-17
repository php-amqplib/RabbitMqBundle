<?php

namespace Kdyby\RabbitMq\Command;

use Kdyby\RabbitMq\Consumer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;



/**
 * @author Alvaro Videla <videlalvaro@gmail.com>
 * @author Filip Procházka <filip@prochazka.su>
 */
class PurgeConsumerCommand extends Command
{

	/**
	 * @inject
	 * @var \Kdyby\RabbitMq\Connection
	 */
	public $connection;



	protected function configure()
	{
		$this
			->setName('rabbitmq:purge')
			->setDescription('Purges all messages in queue associated with given consumer')
			->addArgument('name', InputArgument::REQUIRED, 'Consumer Name')
			->addOption('no-confirmation', null, InputOption::VALUE_NONE, 'Whether it must be confirmed before purging');
	}



	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return int
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$noConfirmation = (bool) $input->getOption('no-confirmation');

		if (!$noConfirmation && $input->isInteractive()) {
			$confirmation = $this->getHelper('dialog')->askConfirmation($output, sprintf('<question>Are you sure you wish to purge "%s" queue? (y/n)</question>', $input->getArgument('name')), false);
			if (!$confirmation) {
				$output->writeln('<error>Purging cancelled!</error>');

				return 1;
			}
		}

		/** @var Consumer $consumer */
		$consumer = $this->connection->getConsumer($input->getArgument('name'));
		$consumer->purge();

		return 0;
	}

}
