<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

class AmqpPartsHolder
{
	protected $parts;

	public function __construct()
	{
		$this->parts = array();
	}

	public function addPart($type, BaseAmqp $part)
	{
		$this->parts[$type][] = $part;
	}

	public function getParts($type)
	{
		return $this->parts[(string) $type];
	}
}
