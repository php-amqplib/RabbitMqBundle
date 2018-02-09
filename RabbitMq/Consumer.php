<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

use OldSound\RabbitMqBundle\Event\AfterProcessingMessageEvent;
use OldSound\RabbitMqBundle\Event\BeforeProcessingMessageEvent;
use PhpAmqpLib\Message\AMQPMessage;

class Consumer extends BaseConsumer
{

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


}
