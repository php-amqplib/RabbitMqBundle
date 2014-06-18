<?php

namespace Kdyby\RabbitMq;

class Fallback
{

	public function publish()
	{
		return false;
	}
}
