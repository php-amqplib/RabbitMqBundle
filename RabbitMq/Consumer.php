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
    const TIMEOUT_TYPE_IDLE = 'idle';
    const TIMEOUT_TYPE_GRACEFUL_MAX_EXECUTION = 'graceful-max-execution';

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

        while (count($this->getChannel()->callbacks)) {
            $this->dispatchEvent(OnConsumeEvent::NAME, new OnConsumeEvent($this));
            $this->maybeStopConsumer();

            /*
             * Be careful not to trigger ::wait() with 0 or less seconds, when
             * graceful max execution timeout is being used.
             */
            $waitTimeout = $this->chooseWaitTimeout();
            if (
                $waitTimeout['timeoutType'] === self::TIMEOUT_TYPE_GRACEFUL_MAX_EXECUTION
                && $waitTimeout['seconds'] < 1
            ) {
                return $this->gracefulMaxExecutionTimeoutExitCode;
            }

            if (!$this->forceStop) {
                try {
                    $this->getChannel()->wait(null, false, $waitTimeout['seconds']);
                } catch (AMQPTimeoutException $e) {
                    if (self::TIMEOUT_TYPE_GRACEFUL_MAX_EXECUTION === $waitTimeout['timeoutType']) {
                        return $this->gracefulMaxExecutionTimeoutExitCode;
                    }

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
        $this->dispatchEvent(BeforeProcessingMessageEvent::NAME,
            new BeforeProcessingMessageEvent($this, $msg)
        );
        try {
            $processFlag = call_user_func($callback, $msg);
            $this->handleProcessMessage($msg, $processFlag);
            $this->dispatchEvent(
                AfterProcessingMessageEvent::NAME,
                new AfterProcessingMessageEvent($this, $msg)
            );
            $this->logger->debug('Queue message processed', array(
                'amqp' => array(
                    'queue' => $queueName,
                    'message' => $msg,
                    'return_code' => $processFlag
                )
            ));
        } catch (Exception\StopConsumerException $e) {
            $this->logger->info('Consumer requested restart', array(
                'amqp' => array(
                    'queue' => $queueName,
                    'message' => $msg,
                    'stacktrace' => $e->getTraceAsString()
                )
            ));
            $this->handleProcessMessage($msg, $e->getHandleCode());
            $this->stopConsuming();
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), array(
                'amqp' => array(
                    'queue' => $queueName,
                    'message' => $msg,
                    'stacktrace' => $e->getTraceAsString()
                )
            ));
            throw $e;
        } catch (\Error $e) {
            $this->logger->error($e->getMessage(), array(
                'amqp' => array(
                    'queue' => $queueName,
                    'message' => $msg,
                    'stacktrace' => $e->getTraceAsString()
                )
            ));
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
            $msg->delivery_info['channel']->basic_reject($msg->delivery_info['delivery_tag'], true);
        } else if ($processFlag === ConsumerInterface::MSG_SINGLE_NACK_REQUEUE) {
            // NACK and requeue message to RabbitMQ
            $msg->delivery_info['channel']->basic_nack($msg->delivery_info['delivery_tag'], false, true);
        } else if ($processFlag === ConsumerInterface::MSG_REJECT) {
            // Reject and drop
            $msg->delivery_info['channel']->basic_reject($msg->delivery_info['delivery_tag'], false);
        } else if ($processFlag !== ConsumerInterface::MSG_ACK_SENT) {
            // Remove message from queue only if callback return not false
            $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
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

    /**
     * Choose the timeout to use for the $this->getChannel()->wait() method.
     *
     * @return array Of structure
     *  {
     *      timeoutType: string; // one of self::TIMEOUT_TYPE_*
     *      seconds: int;
     *  }
     */
    private function chooseWaitTimeout()
    {
        if ($this->gracefulMaxExecutionDateTime) {
            $allowedExecutionDateInterval = $this->gracefulMaxExecutionDateTime->diff(new \DateTime());
            $allowedExecutionSeconds =  $allowedExecutionDateInterval->days * 86400
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
            if (
                $this->getIdleTimeout()
                && $this->getIdleTimeout() < $allowedExecutionSeconds
            ) {
                return array(
                    'timeoutType' => self::TIMEOUT_TYPE_IDLE,
                    'seconds' => $this->getIdleTimeout(),
                );
            }

            return array(
                'timeoutType' => self::TIMEOUT_TYPE_GRACEFUL_MAX_EXECUTION,
                'seconds' => $allowedExecutionSeconds,
            );
        }

        return array(
            'timeoutType' => self::TIMEOUT_TYPE_IDLE,
            'seconds' => $this->getIdleTimeout(),
        );
    }
}
