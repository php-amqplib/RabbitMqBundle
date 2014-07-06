<?php

namespace Kdyby\RabbitMq;

use PhpAmqpLib\Exception\AMQPExceptionInterface;
use PhpAmqpLib\Exception\AMQPRuntimeException;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PhpAmqpLib\Message\AMQPMessage;



/**
 * @author Alvaro Videla <videlalvaro@gmail.com>
 * @author Filip Procházka <filip@prochazka.su>
 *
 * @method onStart(RpcServer $self)
 * @method onConsume(RpcServer $self, AMQPMessage $msg)
 * @method onReply(RpcServer $self, $result)
 * @method onError(RpcServer $self, AMQPExceptionInterface $e)
 */
class RpcServer extends BaseConsumer
{

	/**
	 * @var array
	 */
	public $onConsume = array();

	/**
	 * @var array
	 */
	public $onReply = array();

	/**
	 * @var array
	 */
	public $onStart = array();

	/**
	 * @var array
	 */
	public $onStop = array();

	/**
	 * @var array
	 */
	public $onError = array();



	public function initServer($name)
	{
		$this->setExchangeOptions(array('name' => $name, 'type' => 'direct'));
		$this->setQueueOptions(array('name' => $name . '-queue'));
	}



	public function start($msgAmount = 0)
	{
		$this->target = $msgAmount;
		$this->setupConsumer();
		$this->onStart($this);

		try {
			while (count($this->getChannel()->callbacks)) {
				$this->maybeStopConsumer();

				try {
					$this->getChannel()->wait(NULL, FALSE, $this->getIdleTimeout());
				} catch (AMQPTimeoutException $e) {
					// nothing bad happened, right?
				}
			}

		} catch (AMQPRuntimeException $e) {
			// sending kill signal to the consumer causes the stream_select to return false
			// the reader doesn't like the false value, so it throws AMQPRuntimeException
			$this->maybeStopConsumer();
			if ( ! $this->forceStop) {
				$this->onError($this, $e);
				throw $e;
			}

		} catch (AMQPExceptionInterface $e) {
			$this->onError($this, $e);
			throw $e;
		}
	}



	public function processMessage(AMQPMessage $msg)
	{
		try {
			$msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
			$this->onConsume($this, $msg);

			$result = call_user_func($this->callback, $msg);
			$this->onReply($this, $result);
			$this->sendReply(serialize($result), $msg->get('reply_to'), $msg->get('correlation_id'));

			$this->consumed++;
			$this->maybeStopConsumer();

		} catch (\Exception $e) {
			$this->sendReply('error: ' . $e->getMessage(), $msg->get('reply_to'), $msg->get('correlation_id'));
		}
	}



	protected function sendReply($result, $client, $correlationId)
	{
		$this->getChannel()->basic_publish(
			new AMQPMessage($result, array(
				'content_type' => 'text/plain',
				'correlation_id' => $correlationId
			)),
			$exchange = '',
			$client
		);
	}

}
