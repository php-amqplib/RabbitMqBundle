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
use Tracy\Bar;
use Tracy\BlueScreen;
use Tracy\Debugger;
use Tracy\IBarPanel;



if (!class_exists('Tracy\Debugger')) {
	class_alias('Nette\Diagnostics\Debugger', 'Tracy\Debugger');
}

if (!class_exists('Tracy\Bar')) {
	class_alias('Nette\Diagnostics\Bar', 'Tracy\Bar');
	class_alias('Nette\Diagnostics\BlueScreen', 'Tracy\BlueScreen');
	class_alias('Nette\Diagnostics\Helpers', 'Tracy\Helpers');
	class_alias('Nette\Diagnostics\IBarPanel', 'Tracy\IBarPanel');
}

if (!class_exists('Tracy\Dumper')) {
	class_alias('Nette\Diagnostics\Dumper', 'Tracy\Dumper');
}

/**
 * @author Filip Procházka <filip@prochazka.su>
 *
 * @property callable $begin
 * @property callable $failure
 * @property callable $success
 */
class Panel extends Nette\Object implements IBarPanel
{

	/**
	 * @var array
	 */
	private $messages = array();



	/**
	 * @return string
	 */
	public function getTab()
	{
		$img = Html::el('img', array(
			'height' => '16px',
			'src' => 'data:image/png;base64,' . base64_encode(file_get_contents(__DIR__ . '/logo.png'))
		));

		$tab = Html::el('span', array('title' => 'RabbitMq'))->add($img);
		$title = Html::el()->setText('RabbitMq');

		if ($this->messages) {
			$title->setText(count($this->messages) . ' message' . (count($this->messages) > 1 ? 's' : ''));
		}

		return (string)$tab->add($title);
	}



	/**
	 * @return string
	 */
	public function getPanel()
	{
		if (!$this->messages) {
			return NULL;
		}

		ob_start();
		$esc = callback('Nette\Templating\Helpers::escapeHtml');
		$click = class_exists('\Tracy\Dumper')
			? function ($o, $c = FALSE) { return \Tracy\Dumper::toHtml($o, array('collapse' => $c)); }
			: callback('\Tracy\Helpers::clickableDump');

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
		self::getDebuggerBar()->addPanel($this);
		return $this;
	}



	/**
	 * @return Bar
	 */
	private static function getDebuggerBar()
	{
		return method_exists('Tracy\Debugger', 'getBar') ? Debugger::getBar() : Debugger::$bar;
	}



	/**
	 * @return BlueScreen
	 */
	private static function getDebuggerBlueScreen()
	{
		return method_exists('Tracy\Debugger', 'getBlueScreen') ? Debugger::getBlueScreen() : Debugger::$blueScreen;
	}

}
