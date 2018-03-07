<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.md that was distributed with this source code.
 */

namespace Kdyby\RabbitMq\Diagnostics;

use Kdyby\RabbitMq\Connection;
use Nette;
use Nette\Utils\Html;
use Tracy\Debugger;
use Tracy\IBarPanel;



/**
 * @author Filip Procházka <filip@prochazka.su>
 *
 * @property callable $begin
 * @property callable $failure
 * @property callable $success
 */
class Panel implements IBarPanel
{

    use Nette\SmartObject;

	/**
	 * @var array
	 */
	private $messages = [];

	/**
	 * @var array
	 */
	private $serviceMap = [];



	public function injectServiceMap(array $consumers, array $rpcServers)
	{
		$this->serviceMap = [
			'consumer' => $consumers,
			'rpcServer' => $rpcServers,
		];
	}



	/**
	 * @return string
	 */
	public function getTab()
	{
		$img = Html::el('')->addHtml(file_get_contents(__DIR__ . '/rabbitmq-logo.svg'));
		$tab = Html::el('span')->title('RabbitMq')->addHtml($img);
		$title = Html::el('span')->class('tracy-label');

		if ($this->messages) {
			$title->setText(count($this->messages) . ' message' . (count($this->messages) > 1 ? 's' : ''));
		}

		return (string) $tab->addHtml($title);
	}



	/**
	 * @return string
	 */
	public function getPanel()
	{
		$isRunning = function ($type, $name) {
			if (strncasecmp(PHP_OS, 'WIN', 3) == 0) {
				return FALSE; // sry, I don't know how to do this
			}

			$command = sprintf('ps aux |grep %s |grep %s',
				($type === 'consumer' ? 'rabbitmq:consumer' : 'rabbitmq:rpc-server'),
				escapeshellarg($name)
			);

			if (!@exec($command, $output)) {
				return FALSE;
			}

			$instances = 0;
			foreach ($output as $line) {
				if (stripos($line, '|grep') === FALSE) {
					$instances += 1;
				}
			}

			return $instances;
		};

		$workers = [];
		$runningWorkers = $configuredWorkers = 0;
		foreach ($this->serviceMap as $type => $services) {
			foreach ($services as $name => $serviceId) {
				$workers[$key = $type . '/' . $name] = $isRunning($type, $name);
				$runningWorkers += (int) $workers[$key];
				$configuredWorkers++;
			}
		}

		ob_start();
		$esc = class_exists('Nette\Templating\Helpers')
			? ['Nette\Templating\Helpers', 'escapeHtml']
			: ['Latte\Runtime\Filters', 'escapeHtml'];
		$click = class_exists('\Tracy\Dumper')
			? function ($o, $c = FALSE) { return \Tracy\Dumper::toHtml($o, ['collapse' => $c]); }
			: ['Tracy\Helpers', 'clickableDump'];

		require __DIR__ . '/panel.phtml';
		return ob_get_clean();
	}



	/**
	 * @param $message
	 * @return object
	 */
	public function published($message)
	{
		$this->messages[] = $message;
	}



	/**
	 * @param Connection $connection
	 * @return Panel
	 */
	public function register(Connection $connection)
	{
		Debugger::getBar()->addPanel($this);
		return $this;
	}

}
