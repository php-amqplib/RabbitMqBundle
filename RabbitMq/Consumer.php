<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

use OldSound\RabbitMqBundle\Event\AfterProcessingMessageEvent;
use OldSound\RabbitMqBundle\Event\BeforeProcessingMessageEvent;
use OldSound\RabbitMqBundle\Event\OnConsumeEvent;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PhpAmqpLib\Message\AMQPMessage;

class Consumer extends BaseConsumer
{
    /**
     * @var int $memoryLimit
     */
    protected $memoryLimit = null;

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
     * Consume the message
     *
     * @param int $msgAmount
     */
    public function consume($msgAmount)
    {
        $this->target = $msgAmount;

        $this->setupConsumer();

        while (count($this->getChannel()->callbacks)) {
            $this->dispatchEvent(OnConsumeEvent::NAME, new OnConsumeEvent($this));
            $this->maybeStopConsumer();
            if (!$this->forceStop) {
                try {
                    $this->getChannel()->wait(null, false, $this->getIdleTimeout());
                } catch (AMQPTimeoutException $e) {
                    if (null !== $this->getIdleTimeoutExitCode()) {
                        return $this->getIdleTimeoutExitCode();
                    } else {
                        throw $e;
                    }
                }
            }
        }
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
        } else {
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
        if (memory_get_usage(true) >= ($this->getMemoryLimit() * 1024 * 1024)) {
            return true;
        } else {
            return false;
        }
    }
}
