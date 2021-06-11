<?php

declare(strict_types = 1);

namespace Kdyby\RabbitMq;

/**
 * Marker interface for consumers. It's constants can be used for responding on messages.
 */
interface IConsumer
{

	/**
	 * Flag for message ack
	 */
	public const MSG_ACK = 1;

	/**
	 * Flag single for message nack and requeue
	 */
	public const MSG_SINGLE_NACK_REQUEUE = 2;

	/**
	 * Flag for reject and requeue
	 */
	public const MSG_REJECT_REQUEUE = 0;

	/**
	 * Flag for reject and drop
	 */
	public const MSG_REJECT = -1;

}
