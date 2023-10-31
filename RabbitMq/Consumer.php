<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

use OldSound\RabbitMqBundle\Event\AfterProcessingMessageEvent;
use OldSound\RabbitMqBundle\Event\BeforeProcessingMessageEvent;
use OldSound\RabbitMqBundle\Event\OnConsumeEvent;
use OldSound\RabbitMqBundle\Event\OnIdleEvent;
use OldSound\RabbitMqBundle\MemoryChecker\MemoryConsumptionChecker;
use OldSound\RabbitMqBundle\MemoryChecker\NativeMemoryUsageProvider;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PhpAmqpLib\Message\AMQPMessage;

class Consumer extends BaseConsumer
{
    /**
     * @var int|null $memoryLimit
     */
    protected $memoryLimit = null;

    /**
     * @var \DateTime|null DateTime after which the consumer will gracefully exit. "Gracefully" means, that
     *      any currently running consumption will not be interrupted.
     */
    protected $gracefulMaxExecutionDateTime;

    /**
     * @var int Exit code used, when consumer is closed by the Graceful Max Execution Timeout feature.
     */
    protected $gracefulMaxExecutionTimeoutExitCode = 0;

    /**
     * @var int|null
     */
    protected $timeoutWait;

    /**
     * @var \DateTime|null
     */
    protected $lastActivityDateTime;

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
     * @return int|null
     */
    public function getMemoryLimit()
    {
        return $this->memoryLimit;
    }

    /**
     * Consume the message
     *
     * @param   int     $msgAmount
     *
     * @return  int
     *
     * @throws  AMQPTimeoutException
     */
    public function consume($msgAmount)
    {
        $this->target = $msgAmount;

        $this->setupConsumer();

        $this->setLastActivityDateTime(new \DateTime());
        while ($this->getChannel()->is_consuming()) {
            $this->dispatchEvent(OnConsumeEvent::NAME, new OnConsumeEvent($this));
            $this->maybeStopConsumer();

            /*
             * Be careful not to trigger ::wait() with 0 or less seconds, when
             * graceful max execution timeout is being used.
             */

            $waitTimeout = $this->chooseWaitTimeout();
            if ($this->gracefulMaxExecutionDateTime
                && $waitTimeout < 1
            ) {
                return $this->gracefulMaxExecutionTimeoutExitCode;
            }

            if (!$this->forceStop) {
                try {
                    $this->getChannel()->wait(null, false, $waitTimeout);
                    $this->setLastActivityDateTime(new \DateTime());
                } catch (AMQPTimeoutException $e) {
                    $now = time();

                    if ($this->gracefulMaxExecutionDateTime
                        && $this->gracefulMaxExecutionDateTime <= new \DateTime("@$now")
                    ) {
                        return $this->gracefulMaxExecutionTimeoutExitCode;
                    } elseif ($this->getIdleTimeout()
                        && ($this->getLastActivityDateTime()->getTimestamp() + $this->getIdleTimeout() <= $now)
                    ) {
                        $idleEvent = new OnIdleEvent($this);
                        $this->dispatchEvent(OnIdleEvent::NAME, $idleEvent);

                        if ($idleEvent->isForceStop()) {
                            if (null !== $this->getIdleTimeoutExitCode()) {
                                return $this->getIdleTimeoutExitCode();
                            } else {
                                throw $e;
                            }
                        }
                    }
                }
            }
        }

        return 0;
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

    protected function processMessageQueueCallback(AMQPMessage $msg, $queueName, $callback)
    {
        $this->dispatchEvent(
            BeforeProcessingMessageEvent::NAME,
            new BeforeProcessingMessageEvent($this, $msg)
        );
        try {
            $processFlag = call_user_func($callback, $msg);
            $this->handleProcessMessage($msg, $processFlag);
            $this->dispatchEvent(
                AfterProcessingMessageEvent::NAME,
                new AfterProcessingMessageEvent($this, $msg)
            );
            $this->logger->debug('Queue message processed', [
                'amqp' => [
                    'queue' => $queueName,
                    'message' => $msg,
                    'return_code' => $processFlag,
                ],
            ]);
        } catch (Exception\StopConsumerException $e) {
            $this->logger->info('Consumer requested stop', [
                'amqp' => [
                    'queue' => $queueName,
                    'message' => $msg,
                    'stacktrace' => $e->getTraceAsString(),
                ],
            ]);
            $this->handleProcessMessage($msg, $e->getHandleCode());
            $this->stopConsuming();
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), [
                'amqp' => [
                    'queue' => $queueName,
                    'message' => $msg,
                    'stacktrace' => $e->getTraceAsString(),
                ],
            ]);
            throw $e;
        } catch (\Error $e) {
            $this->logger->error($e->getMessage(), [
                'amqp' => [
                    'queue' => $queueName,
                    'message' => $msg,
                    'stacktrace' => $e->getTraceAsString(),
                ],
            ]);
            throw $e;
        }
    }

    public function processMessage(AMQPMessage $msg)
    {
        $this->processMessageQueueCallback($msg, $this->queueOptions['name'], $this->callback);
    }

    protected function handleProcessMessage(AMQPMessage $msg, $processFlag)
    {
        if ($processFlag === ConsumerInterface::MSG_REJECT_REQUEUE || false === $processFlag) {
            // Reject and requeue message to RabbitMQ
            $msg->reject();
        } elseif ($processFlag === ConsumerInterface::MSG_SINGLE_NACK_REQUEUE) {
            // NACK and requeue message to RabbitMQ
            $msg->nack(true);
        } elseif ($processFlag === ConsumerInterface::MSG_REJECT) {
            // Reject and drop
            $msg->reject(false);
        } elseif ($processFlag !== ConsumerInterface::MSG_ACK_SENT) {
            // Remove message from queue only if callback return not false
            $msg->ack();
        }

        $this->consumed++;
        $this->maybeStopConsumer();

        if (!is_null($this->getMemoryLimit()) && $this->isRamAlmostOverloaded()) {
            $this->stopConsuming();
        }
    }

    /**
     * Checks if memory in use is greater or equal than memory allowed for this process
     *
     * @return boolean
     */
    protected function isRamAlmostOverloaded()
    {
        $memoryManager = new MemoryConsumptionChecker(new NativeMemoryUsageProvider());

        return $memoryManager->isRamAlmostOverloaded($this->getMemoryLimit().'M', '5M');
    }

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
     * @param int $exitCode
     */
    public function setGracefulMaxExecutionTimeoutExitCode($exitCode)
    {
        $this->gracefulMaxExecutionTimeoutExitCode = $exitCode;
    }

    public function setTimeoutWait(int $timeoutWait): void
    {
        $this->timeoutWait = $timeoutWait;
    }

    /**
     * @return \DateTime|null
     */
    public function getGracefulMaxExecutionDateTime()
    {
        return $this->gracefulMaxExecutionDateTime;
    }

    /**
     * @return int
     */
    public function getGracefulMaxExecutionTimeoutExitCode()
    {
        return $this->gracefulMaxExecutionTimeoutExitCode;
    }

    public function getTimeoutWait(): ?int
    {
        return $this->timeoutWait;
    }

    /**
     * Choose the timeout wait (in seconds) to use for the $this->getChannel()->wait() method.
     */
    private function chooseWaitTimeout(): int
    {
        if ($this->gracefulMaxExecutionDateTime) {
            $allowedExecutionDateInterval = $this->gracefulMaxExecutionDateTime->diff(new \DateTime());
            $allowedExecutionSeconds = $allowedExecutionDateInterval->days * 86400
                + $allowedExecutionDateInterval->h * 3600
                + $allowedExecutionDateInterval->i * 60
                + $allowedExecutionDateInterval->s;

            if (!$allowedExecutionDateInterval->invert) {
                $allowedExecutionSeconds *= -1;
            }

            /*
             * Respect the idle timeout if it's set and if it's less than
             * the remaining allowed execution.
             */
            if ($this->getIdleTimeout()
                && $this->getIdleTimeout() < $allowedExecutionSeconds
            ) {
                $waitTimeout = $this->getIdleTimeout();
            } else {
                $waitTimeout = $allowedExecutionSeconds;
            }
        } else {
            $waitTimeout = $this->getIdleTimeout();
        }

        if (!is_null($this->getTimeoutWait()) && $this->getTimeoutWait() > 0) {
            $waitTimeout = min($waitTimeout, $this->getTimeoutWait());
        }
        return $waitTimeout;
    }

    public function setLastActivityDateTime(\DateTime $dateTime)
    {
        $this->lastActivityDateTime = $dateTime;
    }

    protected function getLastActivityDateTime(): ?\DateTime
    {
        return $this->lastActivityDateTime;
    }
}
