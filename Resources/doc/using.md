## Using ##

Add the `config/packages/old_sound_rabbit_mq.yaml` file which contains:

```yaml
old_sound_rabbit_mq:
  connections:
    default:
      url: 'amqp://user:user@rabbitmq:5672?lazy=1&connection_timeout=10'

  declarations:
    exchanges:
      - { name: order, type: topic }
    queues:
      - { name: order }
    bindings:
      - { exchange: order, destination: high_order, routing_key: 'high' }
      # Can specify destination to another exchange and multiple keys
      - { exchange: order, destination: order_notifications, destination_is_exchange: true, routing_keys: ['high', 'middle'] }
  
  producers:
    order:
      #connection: default
      exchange: order
      #auto_declare: "@=kernel.environment !== 'prod'"

  # A consumer will connect to the server and start a loop waiting for incoming messages to process.
  # Depending on the specified callback for such queue will be the behavior it will have.
  # "hight_order" key would be available as argument for "bin/console rabbitmq:consumer" command
  consumers:
    high_order:
      # connection: default
      consumeQueues: [{ queue: high_order, callback: App\Consumer\HighOrderConsumer }]

      # Allow consume multiple queues for consumer
      consumerQueues:
        - { queue: high_order, callback: App\Consumer\HighOrderConsumer }
        - { queue: low_order, callback: App\Consumer\LowOrderConsumer }
```

Define bindings alternative way in `exchanges` selction
```yaml
old_sound_rabbit_mq:
  declarations:
    exchanges:
      - name: order
        type: topic
        bindings:
          - { destination: high_order, routing_key: 'high' }
```

Or `queues`
```yaml
old_sound_rabbit_mq:
  declarations:
    queues:
      - name: high_order
        bindings:
          - { exchange: order, routing_key: 'high' }
```

### Callbacks ###

Here's an example callback:

As you can see, this is as simple as implementing one method: __ConsumerInterface::execute__.

Keep in mind that your callbacks _need to be registered_ as normal Symfony services. There you can inject the service container, the database service, the Symfony logger, and so on.

See [https://github.com/php-amqplib/php-amqplib/blob/master/doc/AMQPMessage.md](https://github.com/php-amqplib/php-amqplib/blob/master/doc/AMQPMessage.md) for more details of what's part of a message instance.

```php
<?php
namespace App\Consumer;

use OldSound\RabbitMqBundle\RabbitMq\ReceiverInterface;
use PhpAmqpLib\Message\AMQPMessage;

class HighOrderConsumer implements ReceiverInterface
{
    public function execute(AMQPMessage $msg)
    {
        $msg->getBody();
        // process order...
        return ReceiverInterface::MSG_ACK;
    }
}
```

Type `bin:console debug:container old_sound_rabbit_mq.producer` and `bin:console debug:container old_sound_rabbit_mq.consumer` for looking which services is defined
Here we configure the connection service and the message endpoints that our application will have.
In this example your service container will contain the service `old_sound_rabbit_mq.producer.order` and `old_sound_rabbit_mq.consumer.high_order`.

# Producer #

A producer will be used to send messages to the server.
Now let's say that you want to process new order in the background. After you move the picture to its final location, you will publish a message to server with the following information:

```php
class OrderContoller extends \Symfony\Bundle\FrameworkBundle\Controller\AbstractController
{
    public function newAction(\Symfony\Component\HttpFoundation\Request $request)
    {
        $order = ['order_id' => $request->get('id'), 'items' => $request->get('items')];
        $this->get('old_sound_rabbit_mq.producer.order')->publish(json_encode($order), 'high');
    }
}
```

In production ensure you declared exhchanges before. You can setup declarations by command for `default` connection
```bash
bin/console rabbitmq:declare -c default
```

# Consume #

```bash
bin/console rabbitmq:consumer high_order -vvv
```

Produce message via cli command
```shell
echo '{"id": 3, "items": [{"id": 23213}]}' | bin/console rabbitmq:stdin-producer order -f raw -r alert
```



As you can see, if in your configuration you have a producer called __upload\_picture__, then in the service container you will have a service called __old_sound_rabbit_mq.upload\_picture\_producer__.

Besides the message itself, the `OldSound\RabbitMqBundle\RabbitMq\Producer#publish()` method also accepts an optiona l routing key parameter and an optional array of additional properties. The array of additional properties allows you to alter the properties with which an `PhpAmqpLib\Message\AMQPMessage` object gets constructed by default. This way, for example, you can change the application headers.

You can use __setContentType__ and __setDeliveryMode__ methods in order to set the message content type and the message
delivery mode respectively. Default values are __text/plain__ for content type and __2__ for delivery mode.

```php
$this->get('old_sound_rabbit_mq.upload_picture_producer')->setContentType('application/json');
```

As we see there, the __callback__ option has a reference to an __upload\_picture\_service__. When the consumer gets a message from the server it will execute such callback. If for testing or debugging purposes you need to specify a different callback, then you can change it there.

Apart from the callback we also specify the connection to use, the same way as we do with a __producer__. The remaining options are the __exchange\_options__ and the __queue\_options__. The __exchange\_options__ should be the same ones as those used for the __producer__. In the __queue\_options__ we will provide a __queue name__. Why?

As we said, messages in AMQP are published to an __exchange__. This doesn't mean the message has reached a __queue__. For this to happen, first we need to create such __queue__ and then bind it to the __exchange__. The cool thing about this is that you can bind several __queues__ to one __exchange__, in that way one message can arrive to several destinations. The advantage of this approach is the __decoupling__ from the producer and the consumer. The producer does not care about how many consumers will process his messages. All it needs is that his message arrives to the server. In this way we can expand the actions we perform every time a picture is uploaded without the need to change code in our controller.

Now, how to run a consumer? There's a command for it that can be executed like this:

```bash
$ ./app/console rabbitmq:consumer -m 50 upload_picture
```

What does this mean? We are executing the __upload\_picture__ consumer telling it to consume only 50 messages. Every time the consumer receives a message from the server, it will execute the configured callback passing the AMQP message as an instance of the `PhpAmqpLib\Message\AMQPMessage` class. The message body can be obtained by calling `$msg->body`. By default the consumer will process messages in an __endless loop__ for some definition of _endless_.

If you want to be sure that consumer will finish executing instantly on Unix signal, you can run command with flag `-w`.

```bash
$ ./app/console rabbitmq:consumer -w upload_picture
```

Then the consumer will finish executing instantly.

For using command with this flag you need to install PHP with [PCNTL extension](http://www.php.net/manual/en/book.pcntl.php).

If you want to establish a consumer memory limit, you can do it by using flag `-l`. In the following example, this flag adds 256 MB memory limit. Consumer will be stopped five MB before reaching 256MB in order to avoid a PHP Allowed memory size error.

```bash
$ ./app/console rabbitmq:consumer -l 256
```



#### Fair dispatching ####

> You might have noticed that the dispatching still doesn't work exactly as we want. For example in a situation with two workers, when all odd messages are heavy and even messages are light, one worker will be constantly busy and the other one will do hardly any work. Well, RabbitMQ doesn't know anything about that and will still dispatch messages evenly.

> This happens because RabbitMQ just dispatches a message when the message enters the queue. It doesn't look at the number of unacknowledged messages for a consumer. It just blindly dispatches every n-th message to the n-th consumer.

> In order to defeat that we can use the basic.qos method with the prefetch_count=1 setting. This tells RabbitMQ not to give more than one message to a worker at a time. Or, in other words, don't dispatch a new message to a worker until it has processed and acknowledged the previous one. Instead, it will dispatch it to the next worker that is not still busy.

From: http://www.rabbitmq.com/tutorials/tutorial-two-python.html

Be careful as implementing the fair dispatching introduce a latency that will hurt performance (see [this blogpost](http://www.rabbitmq.com/blog/2012/05/11/some-queuing-theory-throughput-latency-and-bandwidth/)). But implemeting it allow you to scale horizontally dynamically as the queue is increasing.
You should evaluate, as the blogpost recommends, the right value of prefetch_size accordingly with the time taken to process each message and your network performance.
