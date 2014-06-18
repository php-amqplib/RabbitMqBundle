<?php

namespace Kdyby\RabbitMq;

class AmqpPartsHolder
{

	protected $parts;



	public function __construct()
	{
		$this->parts = array();
	}



	public function addPart($type, AmqpMember $part)
	{
		$this->parts[$type][] = $part;
	}



	public function getParts($type)
	{
		return $this->parts[(string) $type];
	}
}
