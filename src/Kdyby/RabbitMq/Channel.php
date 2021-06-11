<?php

declare(strict_types = 1);

namespace Kdyby\RabbitMq;

class Channel extends \PhpAmqpLib\Channel\AMQPChannel
{

	/**
	 * @var \Kdyby\RabbitMq\Diagnostics\Panel
	 */
	private $panel;

	public function injectPanel(Diagnostics\Panel $panel): void
	{
		$this->panel = $panel;
	}

	// phpcs:disable SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingReturnTypeHint,SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint,PSR1.Methods.CamelCapsMethodName.NotCamelCaps
	public function basic_publish($msg, $exchange = '', $routingKey = '', $mandatory = FALSE, $immediate = FALSE, $ticket = NULL)
	{
		if ($this->panel) {
			$this->panel->published(\get_defined_vars()); // all args
		}

		parent::basic_publish($msg, $exchange, $routingKey, $mandatory, $immediate, $ticket);
	}

}
