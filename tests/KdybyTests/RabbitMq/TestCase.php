<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace KdybyTests\RabbitMq;

use Kdyby;
use Mockery;
use Nette;
use Tester;



require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/mocks.php';

/**
 * @author Filip Procházka <filip@prochazka.su>
 */
abstract class TestCase extends Tester\TestCase
{

	/**
	 * @var Mockery\Container
	 */
	private $mockery;



	/**
	 * @param string $class
	 * @throws \Mockery\Exception\RuntimeException
	 * @return Mockery\Container|Mockery\Mock
	 */
	protected function getMockery($class = NULL)
	{
		if (!$this->mockery) {
			$this->mockery = new Mockery\Container(Mockery::getDefaultGenerator(), Mockery::getDefaultLoader());
		}

		if ($class !== NULL) {
			return $this->mockery->mock($class);
		}

		return $this->mockery;
	}



	protected function tearDown()
	{
		if ($this->mockery) {
			$this->mockery->mockery_close();
		}

		parent::tearDown();
	}

}
