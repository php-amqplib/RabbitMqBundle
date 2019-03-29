<?php declare(strict_types = 1);

namespace KdybyTests\RabbitMq\Mock;


class Callback
{

	public static $accepted = [];


	public function __invoke($message)
	{
		self::$accepted[] = func_get_args();
	}


	public function process($message)
	{
		self::$accepted[] = func_get_args();
	}


	public static function staticProcess($message)
	{
		self::$accepted[] = func_get_args();
	}

}