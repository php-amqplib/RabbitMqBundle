<?php

declare(strict_types = 1);

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\RabbitMq\DI;

interface IProducersProvider
{

	/**
	 * Returns array of name => array config.
	 *
	 * @return array<string, array<mixed>>
	 */
	public function getRabbitProducers(): array;

}
