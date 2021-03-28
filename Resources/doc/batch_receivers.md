### Batch Consumers ###

In some cases you will want to get a batch of messages and then do some processing on all of them. Batch consumers will allow you to define logic for this type of processing.

e.g: Imagine that you have a queue where you receive a message for inserting some information in the database, and you realize that if you do a batch insert is much better then by inserting one by one.

Specify `batchCount` for `consumeQueues` item
# TODO

*Note*: If the `keep_alive` option is set to `true`, `idle_timeout_exit_code` will be ignored and the consumer process continues.

You can implement a batch consumer that will acknowledge all messages in one return or you can have control on what message to acknoledge.

```php
namespace App\Consumer;

use OldSound\RabbitMqBundle\RabbitMq\BatchReceiverInterface;
use OldSound\RabbitMqBundle\RabbitMq\ReceiverInterface;

class BatchLogsConsumer implements BatchReceiverInterface
{
    /**
     * @inheritDoc
     */
    public function batchExecute(array $messages)
    {
        foreach ($messages as $message) {
            $this->executeSomeLogicPerMessage($message);
        }
        $this->persist($messages);
        // you ack all messages got in batch
        return ReceiverInterface::MSG_ACK;
    }
}
```

```php
namespace AppBundle\Service;

use OldSound\RabbitMqBundle\RabbitMq\BatchReceiverInterface;
use PhpAmqpLib\Message\AMQPMessage;

class DevckBasicConsumer implements BatchReceiverInterface
{
    /**
     * @inheritDoc
     */
    public function batchExecute(array $messages)
    {
        echo sprintf('Doing batch execution%s', PHP_EOL);
        $result = [];
        /** @var AMQPMessage $message */
        foreach ($messages as $message) {
            $result[(int)$message->delivery_info['delivery_tag']] = $this->executeSomeLogicPerMessage($message);
        }

        // you ack only some messages that have return true
        // e.g:
        // $return = [
        //      1 => true,
        //      2 => true,
        //      3 => false,
        //      4 => true,
        //      5 => -1,
        //      6 => 2,
        //  ];
        // The following will happen:
        //  * ack: 1,2,4
        //  * reject and requeq: 3
        //  * nack and requeue: 6
        //  * reject and drop: 5
        return $result;
    }
}
```