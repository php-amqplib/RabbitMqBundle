<?php

declare(strict_types = 1);

namespace Kdyby\RabbitMq\Command;

use Kdyby\RabbitMq\AnonymousConsumer;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AnonConsumerCommand extends \Kdyby\RabbitMq\Command\BaseConsumerCommand
{

	protected function configure(): void
	{
		parent::configure();

		$this->setName('rabbitmq:anon-consumer');
		$this->setDescription('Starts an anonymouse configured consumer');

		$this->getDefinition()->getOption('messages')->setDefault(1);
		$this->getDefinition()->getOption('route')->setDefault('#');
	}

	protected function initialize(InputInterface $input, OutputInterface $output): void
	{
		parent::initialize($input, $output);

		if (!$this->consumer instanceof AnonymousConsumer) {
			throw new \Kdyby\RabbitMq\Exception\InvalidArgumentException(
				'Expected instance of Kdyby\RabbitMq\AnonymousConsumer, ' .
				'but consumer ' . $input->getArgument('name') . ' is ' . \get_class($this->consumer)
			);
		}
	}

}
