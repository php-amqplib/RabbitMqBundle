<?php

declare(strict_types = 1);

namespace Kdyby\RabbitMq\Command;

use Kdyby\RabbitMq\DI\RabbitMqExtension;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SetupFabricCommand extends \Symfony\Component\Console\Command\Command
{

	/**
	 * @inject
	 * @var \Nette\DI\Container
	 */
	public $container;

	protected function configure(): void
	{
		$this
			->setName('rabbitmq:setup-fabric')
			->setDescription('Sets up the Rabbit MQ fabric')
			->addOption('debug', 'd', InputOption::VALUE_NONE, 'Enable Debugging');
	}

	protected function execute(InputInterface $input, OutputInterface $output): void
	{
		if (\defined('AMQP_DEBUG') === FALSE) {
			\define('AMQP_DEBUG', (bool) $input->getOption('debug'));
		}

		$output->writeln('Setting up the Rabbit MQ fabric');

		foreach ([
			RabbitMqExtension::TAG_PRODUCER,
			RabbitMqExtension::TAG_CONSUMER,
			RabbitMqExtension::TAG_RPC_CLIENT,
			RabbitMqExtension::TAG_RPC_SERVER,
		] as $tag) {
			foreach (\array_keys($this->container->findByTag($tag)) as $serviceId) {
				/** @var \Kdyby\RabbitMq\AmqpMember $service */
				$service = $this->container->getService($serviceId);
				$service->setupFabric();
			}
		}
	}

}
