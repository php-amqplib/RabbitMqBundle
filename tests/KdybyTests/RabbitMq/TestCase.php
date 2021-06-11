<?php

declare(strict_types = 1);

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace KdybyTests\RabbitMq;

use Mockery;

abstract class TestCase extends \Tester\TestCase
{

	/**
	 * @var \Mockery\Container
	 */
	private $mockery;

	/**
	 * @param string $class
	 * @throws \Mockery\Exception\RuntimeException
	 * @return \Mockery\Container|\Mockery\Mock
	 */
	protected function getMockery(?string $class = NULL)
	{
		if (!$this->mockery) {
			$this->mockery = new Mockery\Container(Mockery::getDefaultGenerator(), Mockery::getDefaultLoader());
		}

		if ($class !== NULL) {
			return $this->mockery->mock($class);
		}

		return $this->mockery;
	}

	protected function tearDown(): void
	{
		if ($this->mockery) {
			$this->mockery->mockery_close();
		}

		parent::tearDown();
	}

}
