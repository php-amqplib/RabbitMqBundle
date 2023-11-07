<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Exception\AMQPRuntimeException;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PhpAmqpLib\Message\AMQPMessage;

class BatchConsumer extends BaseAmqp implements DequeuerInterface
{
    /**
     * @var \Closure|callable
     */
    protected $callback;

    /**
     * @var bool
     */
    protected $forceStop = false;

    /**
     * @var int
     */
    protected $idleTimeout = 0;

    /**
     * @var bool
     */
    private $keepAlive = false;

    /**
     * @var int
     */
    protected $idleTimeoutExitCode;

    /**
     * @var int
     */
    protected $memoryLimit = null;

    /**
     * @var int
     */
    protected $prefetchCount;

    /**
     * @var int
     */
    protected $timeoutWait = 3;

    /**
     * @var array
     */
    protected $messages = [];

    /**
     * @var int
     */
    protected $batchCounter = 0;

    /**
     * @var int
     */
    protected $batchAmount = 0;

    /**
     * @var int
     */
    protected $consumed = 0;

    /**
     * @var \DateTime|null DateTime after which the consumer will gracefully exit. "Gracefully" means, that
     *      any currently running consumption will not be interrupted.
     */
    protected $gracefulMaxExecutionDateTime;

    /** @var int */
    private $batchAmountTarget;

    /**
     * @param \DateTime|null $dateTime
     */
    public function setGracefulMaxExecutionDateTime(\DateTime $dateTime = null)
    {
        $this->gracefulMaxExecutionDateTime = $dateTime;
    }

    /**
     * @param int $secondsInTheFuture
     */
    public function setGracefulMaxExecutionDateTimeFromSecondsInTheFuture($secondsInTheFuture)
    {
        $this->setGracefulMaxExecutionDateTime(new \DateTime("+{$secondsInTheFuture} seconds"));
    }

    /**
     * @param   \Closure|callable    $callback
     *
     * @return  $this
     */
    public function setCallback($callback)
    {
        $this->callback = $callback;

        return $this;
    }

    public function consume(int $batchAmountTarget = 0)
    {
        $this->batchAmountTarget = $batchAmountTarget;

        $this->setupConsumer();

        while ($this->getChannel()->is_consuming()) {
            if ($this->isCompleteBatch()) {
                $this->batchConsume();
            }

            $this->checkGracefulMaxExecutionDateTime();
            $this->maybeStopConsumer();

            $timeout = $this->isEmptyBatch() ? $this->getIdleTimeout() : $this->getTimeoutWait();

            try {
                $this->getChannel()->wait(null, false, $timeout);
            } catch (AMQPTimeoutException $e) {
                if (!$this->isEmptyBatch()) {
                    $this->batchConsume();
                    $this->maybeStopConsumer();
                } elseif ($this->keepAlive === true) {
                    continue;
                } elseif (null !== $this->getIdleTimeoutExitCode()) {
                    return $this->getIdleTimeoutExitCode();
                } else {
                    throw $e;
                }
            }
        }

        return 0;
    }

    private function batchConsume()
    {
        try {
            $processFlags = call_user_func($this->callback, $this->messages);
            $this->handleProcessMessages($processFlags);
            $this->logger->debug('Queue message processed', [
                'amqp' => [
                    'queue' => $this->queueOptions['name'],
                    'messages' => $this->messages,
                    'return_codes' => $processFlags,
                ],
            ]);
        } catch (Exception\StopConsumerException $e) {
            $this->logger->info('Consumer requested stop', [
                'amqp' => [
                    'queue' => $this->queueOptions['name'],
                    'message' => $this->messages,
                    'stacktrace' => $e->getTraceAsString(),
                ],
            ]);
            $this->handleProcessMessages($e->getHandleCode());
            $this->resetBatch();
            $this->stopConsuming();
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), [
                'amqp' => [
                    'queue' => $this->queueOptions['name'],
                    'message' => $this->messages,
                    'stacktrace' => $e->getTraceAsString(),
                ],
            ]);
            $this->resetBatch();
            throw $e;
        } catch (\Error $e) {
            $this->logger->error($e->getMessage(), [
                'amqp' => [
                    'queue' => $this->queueOptions['name'],
                    'message' => $this->messages,
                    'stacktrace' => $e->getTraceAsString(),
                ],
            ]);
            $this->resetBatch();
            throw $e;
        }

        $this->batchAmount++;
        $this->resetBatch();
    }

    /**
     * @param   mixed   $processFlags
     *
     * @return  void
     */
    protected function handleProcessMessages($processFlags = null)
    {
        $processFlags = $this->analyzeProcessFlags($processFlags);
        foreach ($processFlags as $deliveryTag => $processFlag) {
            $this->handleProcessFlag($deliveryTag, $processFlag);
        }
    }

    /**
     * @param   string|int     $deliveryTag
     * @param   mixed          $processFlag
     *
     * @return  void
     */
    private function handleProcessFlag($deliveryTag, $processFlag)
    {
        if ($processFlag === ConsumerInterface::MSG_REJECT_REQUEUE || false === $processFlag) {
            // Reject and requeue message to RabbitMQ
            $this->getMessageChannel($deliveryTag)->basic_reject($deliveryTag, true);
        } elseif ($processFlag === ConsumerInterface::MSG_SINGLE_NACK_REQUEUE) {
            // NACK and requeue message to RabbitMQ
            $this->getMessageChannel($deliveryTag)->basic_nack($deliveryTag, false, true);
        } elseif ($processFlag === ConsumerInterface::MSG_REJECT) {
            // Reject and drop
            $this->getMessageChannel($deliveryTag)->basic_reject($deliveryTag, false);
        } elseif ($processFlag === ConsumerInterface::MSG_ACK_SENT) {
            // do nothing, ACK should be already sent
        } else {
            // Remove message from queue only if callback return not false
            $this->getMessageChannel($deliveryTag)->basic_ack($deliveryTag);
        }
    }

    /**
     * @return  bool
     */
    protected function isCompleteBatch()
    {
        return $this->batchCounter === $this->prefetchCount;
    }

    /**
     * @return  bool
     */
    protected function isEmptyBatch()
    {
        return $this->batchCounter === 0;
    }

    /**
     * @param   AMQPMessage     $msg
     *
     * @return  void
     *
     * @throws  \Error
     * @throws  \Exception
     */
    public function processMessage(AMQPMessage $msg)
    {
        $this->addMessage($msg);

        $this->maybeStopConsumer();
    }

    /**
     * @param   mixed   $processFlags
     *
     * @return  array
     */
    private function analyzeProcessFlags($processFlags = null)
    {
        if (is_array($processFlags)) {
            if (count($processFlags) !== $this->batchCounter) {
                throw new AMQPRuntimeException(
                    'Method batchExecute() should return an array with elements equal with the number of messages processed'
                );
            }

            return $processFlags;
        }

        $response = [];
        foreach ($this->messages as $deliveryTag => $message) {
            $response[$deliveryTag] = $processFlags;
        }

        return $response;
    }


    /**
     * @return  void
     */
    private function resetBatch()
    {
        $this->messages = [];
        $this->batchCounter = 0;
    }

    /**
     * @param   AMQPMessage $message
     *
     * @return  void
     */
    private function addMessage(AMQPMessage $message)
    {
        $this->batchCounter++;
        $this->messages[$message->getDeliveryTag()] = $message;
    }

    /**
     * @param   int     $deliveryTag
     *
     * @return  AMQPMessage|null
     */
    private function getMessage($deliveryTag)
    {
        return $this->messages[$deliveryTag]
            ?? null
        ;
    }

    /**
     * @param   int     $deliveryTag
     *
     * @return  AMQPChannel
     *
     * @throws  AMQPRuntimeException
     */
    private function getMessageChannel($deliveryTag)
    {
        $message = $this->getMessage($deliveryTag);
        if ($message === null) {
            throw new AMQPRuntimeException(sprintf('Unknown delivery_tag %d!', $deliveryTag));
        }

        return $message->getChannel();
    }

    /**
     * @return  void
     */
    public function stopConsuming()
    {
        if (!$this->isEmptyBatch()) {
            $this->batchConsume();
        }

        $this->getChannel()->basic_cancel($this->getConsumerTag(), false, true);
    }

    /**
     * @return  void
     */
    protected function setupConsumer()
    {
        if ($this->autoSetupFabric) {
            $this->setupFabric();
        }

        $this->getChannel()->basic_consume($this->queueOptions['name'], $this->getConsumerTag(), false, $this->consumerOptions['no_ack'], false, false, [$this, 'processMessage']);
    }

    /**
     * @return  void
     *
     * @throws \BadFunctionCallException
     */
    protected function maybeStopConsumer()
    {
        if (extension_loaded('pcntl') && (defined('AMQP_WITHOUT_SIGNALS') ? !AMQP_WITHOUT_SIGNALS : true)) {
            if (!function_exists('pcntl_signal_dispatch')) {
                throw new \BadFunctionCallException("Function 'pcntl_signal_dispatch' is referenced in the php.ini 'disable_functions' and can't be called.");
            }

            pcntl_signal_dispatch();
        }

        if ($this->forceStop || ($this->batchAmount == $this->batchAmountTarget && $this->batchAmountTarget > 0)) {
            $this->stopConsuming();
        }

        if (null !== $this->getMemoryLimit() && $this->isRamAlmostOverloaded()) {
            $this->stopConsuming();
        }
    }

    /**
     * @param   string  $tag
     *
     * @return  $this
     */
    public function setConsumerTag($tag)
    {
        $this->consumerTag = $tag;

        return $this;
    }

    /**
     * @return  string
     */
    public function getConsumerTag()
    {
        return $this->consumerTag;
    }

    /**
     * @return  void
     */
    public function forceStopConsumer()
    {
        $this->forceStop = true;
    }

    /**
     * Sets the qos settings for the current channel
     * Consider that prefetchSize and global do not work with rabbitMQ version <= 8.0
     *
     * @param int $prefetchSize
     * @param int $prefetchCount
     * @param bool $global
     */
    public function setQosOptions($prefetchSize = 0, $prefetchCount = 0, $global = false)
    {
        $this->prefetchCount = $prefetchCount;
        $this->getChannel()->basic_qos($prefetchSize, $prefetchCount, $global);
    }

    /**
     * @param   int     $idleTimeout
     *
     * @return  $this
     */
    public function setIdleTimeout($idleTimeout)
    {
        $this->idleTimeout = $idleTimeout;

        return $this;
    }

    /**
     * Set exit code to be returned when there is a timeout exception
     *
     * @param   int     $idleTimeoutExitCode
     *
     * @return  $this
     */
    public function setIdleTimeoutExitCode($idleTimeoutExitCode)
    {
        $this->idleTimeoutExitCode = $idleTimeoutExitCode;

        return $this;
    }

    /**
     * keepAlive
     *
     * @return $this
     */
    public function keepAlive()
    {
        $this->keepAlive = true;

        return $this;
    }

    /**
     * Purge the queue
     */
    public function purge()
    {
        $this->getChannel()->queue_purge($this->queueOptions['name'], true);
    }

    /**
     * Delete the queue
     */
    public function delete()
    {
        $this->getChannel()->queue_delete($this->queueOptions['name'], true);
    }

    /**
     * Checks if memory in use is greater or equal than memory allowed for this process
     *
     * @return boolean
     */
    protected function isRamAlmostOverloaded()
    {
        return (memory_get_usage(true) >= ($this->getMemoryLimit() * 1048576));
    }

    /**
     * @return  int
     */
    public function getIdleTimeout()
    {
        return $this->idleTimeout;
    }

    /**
     * Get exit code to be returned when there is a timeout exception
     *
     * @return  int|null
     */
    public function getIdleTimeoutExitCode()
    {
        return $this->idleTimeoutExitCode;
    }

    /**
     * Resets the consumed property.
     * Use when you want to call start() or consume() multiple times.
     */
    public function resetConsumed()
    {
        $this->consumed = 0;
    }

    /**
     * @param   int     $timeout
     *
     * @return  $this
     */
    public function setTimeoutWait($timeout)
    {
        $this->timeoutWait = $timeout;

        return $this;
    }

    /**
     * @param   int $amount
     *
     * @return  $this
     */
    public function setPrefetchCount($amount)
    {
        $this->prefetchCount = $amount;

        return $this;
    }

    /**
     * @return int
     */
    public function getTimeoutWait()
    {
        return $this->timeoutWait;
    }

    /**
     * @return int
     */
    public function getPrefetchCount()
    {
        return $this->prefetchCount;
    }

    /**
     * Set the memory limit
     *
     * @param int $memoryLimit
     */
    public function setMemoryLimit($memoryLimit)
    {
        $this->memoryLimit = $memoryLimit;
    }

    /**
     * Get the memory limit
     *
     * @return int
     */
    public function getMemoryLimit()
    {
        return $this->memoryLimit;
    }

    /**
     * Check graceful max execution date time and stop if limit is reached
     *
     * @return void
     */
    private function checkGracefulMaxExecutionDateTime()
    {
        if (!$this->gracefulMaxExecutionDateTime) {
            return;
        }

        $now = new \DateTime();

        if ($this->gracefulMaxExecutionDateTime > $now) {
            return;
        }

        $this->forceStopConsumer();
    }
}
