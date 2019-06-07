<?php

declare(strict_types = 1);

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.md that was distributed with this source code.
 */

namespace Kdyby\RabbitMq\Diagnostics;

use Kdyby\RabbitMq\Connection;
use Nette\Utils\Html;
use Tracy\Debugger;

/**
 * @property callable $begin
 * @property callable $failure
 * @property callable $success
 */
class Panel implements \Tracy\IBarPanel
{

	use \Nette\SmartObject;

	/**
	 * @var array
	 */
	private $messages = [];

	/**
	 * @var array
	 */
	private $serviceMap = [];

	/**
	 * @param array<mixed> $consumers
	 * @param array<mixed> $rpcServers
	 */
	public function injectServiceMap(array $consumers, array $rpcServers): void
	{
		$this->serviceMap = [
			'consumer' => $consumers,
			'rpcServer' => $rpcServers,
		];
	}

	public function getTab(): string
	{
		$img = Html::el('')->addHtml(\file_get_contents(__DIR__ . '/rabbitmq-logo.svg'));
		$tab = Html::el('span')->setAttribute('title', 'RabbitMq')->addHtml($img);
		$title = Html::el('span')->setAttribute('class', 'tracy-label');

		if ($this->messages) {
			$title->setText(\count($this->messages) . ' message' . (\count($this->messages) > 1 ? 's' : ''));
		}

		return (string) $tab->addHtml($title);
	}

	public function getPanel(): string
	{
		$isRunning = static function ($type, $name) {
			if (\strncasecmp(PHP_OS, 'WIN', 3) === 0) {
				return FALSE; // sry, I don't know how to do this
			}

			$command = \sprintf(
				'ps aux |grep %s |grep %s',
				($type === 'consumer' ? 'rabbitmq:consumer' : 'rabbitmq:rpc-server'),
				\escapeshellarg($name)
			);

			if (!@\exec($command, $output)) {
				return FALSE;
			}

			$instances = 0;
			foreach ($output as $line) {
				if (\stripos($line, '|grep') === FALSE) {
					$instances++;
				}
			}

			return $instances;
		};

		$workers = [];
		$runningWorkers = $configuredWorkers = 0;
		foreach ($this->serviceMap as $type => $services) {
			foreach (\array_keys($services) as $name) {
				$workers[$key = $type . '/' . $name] = $isRunning($type, $name);
				// phpcs:disable SlevomatCodingStandard.Variables.UnusedVariable.UnusedVariable
				$runningWorkers += (int) $workers[$key];
				// phpcs:disable SlevomatCodingStandard.Variables.UnusedVariable.UnusedVariable
				$configuredWorkers++;
			}
		}

		\ob_start();
		// phpcs:disable SlevomatCodingStandard.Variables.UnusedVariable.UnusedVariable
		$esc = \class_exists('Nette\Templating\Helpers')
			? ['Nette\Templating\Helpers', 'escapeHtml']
			: ['Latte\Runtime\Filters', 'escapeHtml'];
		// phpcs:disable SlevomatCodingStandard.Variables.UnusedVariable.UnusedVariable
		$click = \class_exists('\Tracy\Dumper')
			? static function ($o, $c = FALSE) {
				return \Tracy\Dumper::toHtml($o, ['collapse' => $c]);
			}
			: ['Tracy\Helpers', 'clickableDump'];

		require __DIR__ . '/panel.phtml';
		return \ob_get_clean();
	}

	public function published(array $message): void
	{
		$this->messages[] = $message;
	}

	public function register(Connection $connection): Panel
	{
		Debugger::getBar()->addPanel($this);
		return $this;
	}

}
