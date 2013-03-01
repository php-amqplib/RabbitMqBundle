# RabbitMqBundle #

## About ##

The RabbitMqBundle incorporates messaging in your application via [RabbitMq](http://www.rabbitmq.com/) using the [php-amqplib](http://github.com/videlalvaro/php-amqplib) library.

The bundle implements several messaging patterns as seen on the [Thumper](https://github.com/videlalvaro/Thumper) library. Therefore publishing messages to RabbitMQ from a Symfony2 controller is as easy as:

```php
$msg = array('user_id' => 1235, 'image_path' => '/path/to/new/pic.png');
$this->get('old_sound_rabbit_mq.upload_picture_producer')->publish(serialize($msg));
```

Later when you want to consume 50 messages out of the `upload_pictures` queue, you just run on the CLI:

```bash
$ ./app/console rabbitmq:consumer -m 50 upload_picture
```

All the examples expect a running RabbitMQ server.

This Bundle was presented at [Symfony Live Paris 2011](http://www.symfony-live.com/paris/schedule#session-av1) conference. See the slides [here](http://www.slideshare.net/old_sound/theres-a-rabbit-on-my-symfony):

[![Build Status](https://secure.travis-ci.org/videlalvaro/RabbitMqBundle.png?branch=master)](http://travis-ci.org/videlalvaro/RabbitMqBundle)

## Installation ##

The following instructions have been tested on a project created with the [Symfony2 Standard 2.0.6](http://symfony.com/download?v=Symfony_Standard_2.0.6.tgz)

Put the RabbitMqBundle and the [php-amqplib](http://github.com/videlalvaro/php-amqplib) library into the deps file:

```ini
...
[RabbitMqBundle]
git=http://github.com/videlalvaro/RabbitMqBundle.git
target=/bundles/OldSound/RabbitMqBundle

[php-amqplib]
git=http://github.com/videlalvaro/php-amqplib.git
target=videlalvaro/php-amqplib
...
```

Register the bundle and library namespaces in the `app/autoload.php` file:

```php
$loader->registerNamespaces(array(
    ...
    'OldSound'         => __DIR__.'/../vendor/bundles',
    'PhpAmqpLib'       => __DIR__.'/../vendor/videlalvaro/php-amqplib',
    ...
));
```

Add the RabbitMqBundle to your application's kernel:

```php
public function registerBundles()
{
    $bundles = array(
        ...
        new OldSound\RabbitMqBundle\OldSoundRabbitMqBundle(),
        ...
    );
    ...
}
```

### Warning - BC Breaking Changes ###

* Since 2012-06-04 Some default options for exchanges declared in the "producers" config section
  have changed to match the defaults of exchanges declared in the "consumers" section.
  The affected settings are:

  * `durable` was changed from `false` to `true`,
  * `auto_delete` was changed from `true` to `false`.

  Your configuration must be updated if you were relying on the previous default values.
* Since 2012-04-24 The ConsumerInterface::execute method signature has changed
* Since 2012-01-03 the consumers execute method gets the whole AMQP message object and not just the body. See the CHANGELOG file for more details.

## Usage ##

Configure the `rabbitmq` service in your config:

```yaml
old_sound_rabbit_mq:
    connections:
        default:
            host:      'localhost'
            port:      5672
            user:      'guest'
            password:  'guest'
            vhost:     '/'
    producers:
        upload_picture:
            connection: default
            exchange_options: {name: 'upload-picture', type: direct}
    consumers:
        upload_picture:
            connection: default
            exchange_options: {name: 'upload-picture', type: direct}
            queue_options:    {name: 'upload-picture'}
            callback:         upload_picture_service
    ...
```

Here we configure the connection service and the message endpoints that our application will have. In this example your service container will contain the service `old_sound_rabbit_mq.upload_picture_producer` and `old_sound_rabbit_mq.upload_picture_consumer`. The later expects that there's a service called `upload_picture_service`.

If you don't specify a connection for the client, the client will look for a connection with the same alias. So for our `upload_picture` the service container will look for an `upload_picture` connection.

If you need to use HA Queues then your queue options can be something like this:

```yaml
    queue_options:    {name: 'upload-picture', arguments: {'x-ha-policy': ['S', 'all']}}
```

Adapt the `arguments` according to your needs.

If you want to bind queue with specific routingKeys you can declare it in producer or consumer config:

    queue_options:
        name: "upload-picture"
        routing_keys:
          - 'android.#.upload'
          - 'iphone.upload'

## Producers, Consumers, What? ##

In a messaging application, the process sending messages to the broker is called __producer__ while the process receiving those messages is called __consumer__. In your application you will have several of them that you can list under their respective entries in the configuration.

### Producer ###

A producer will be used to send messages to the server. In the AMQP Model, messages are sent to an __exchange__, this means that in the configuration for a producer you will have to specify the connection options along with the exchange options, which usually will be the name of the exchange and the type of it.

Now let's say that you want to process picture uploads in the background. After you move the picture to its final location, you will publish a message to server with the following information:

```php
public function indexAction($name)
{
    ...
    $msg = array('user_id' => 1235, 'image_path' => '/path/to/new/pic.png');
    $this->get('old_sound_rabbit_mq.upload_picture_producer')->publish(serialize($msg));
    ...
}
```

As you can see, if in your configuration you have a producer called __upload\_picture__, then in the service container you will have a service called __old_sound_rabbit_mq.upload\_picture\_producer__.

You can use __setContentType__ and __setDeliveryMode__ methods in order to set the message content type and the message 
delivery mode respectively. Default values are __text/plain__ for content type and __2__ for delivery mode. 

```php
    $this->get('old_sound_rabbit_mq.upload_picture_producer')->setContentType('application/json');
```

The next piece of the puzzle is to have a consumer that will take the message out of the queue and process it accordingly.

### Consumers ###

A consumer will connect to the server and start a __loop__  waiting for incoming messages to process. Depending on the specified __callback__ for such consumer will be the behavior it will have. Let's review the consumer configuration from above:

```yaml
...
    consumers:
        upload_picture:
            connection: default
            exchange_options: {name: 'upload-picture', type: direct}
            queue_options:    {name: 'upload-picture'}
            callback:         upload_picture_service
    ...
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

    $ ./app/console rabbitmq:consumer -w upload_picture

Then consumer will finish executing instantly.

For using command with this flag you need to install PHP with [PCNTL extension](http://www.php.net/manual/en/book.pcntl.php).

If you want to establish a consumer memory limit, you can do it by using flag -l. In the following example, this flag adds 256 MB memory limit. Consumer will be stopped five MB before reaching 256MB in order to avoid a PHP Allowed memory size error.

$ ./app/console rabbitmq:consumer -l 256

### Callbacks ###

Here's an example callback:

```php
<?php

//src/Acme/DemoBundle/Consumer/UploadPictureConsumer.php

namespace Acme\DemoBundle\Consumer;

use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;

class UploadPictureConsumer implements ConsumerInterface
{
    public function execute(AMQPMessage $msg)
    {
        //Process picture upload.
        //$msg will be an instance of `PhpAmqpLib\Message\AMQPMessage` with the $msg->body being the data sent over RabbitMQ.

        $isUploadSuccess = someUploadPictureMethod();
        if (!$isUploadSuccess) {
            // If your image upload failed due to a temporary error you can return false
            // from your callback so the message will be rejected by the consumer and
            // requeued by RabbitMQ.
            // Any other value not equal to false will acknowledge the message and remove it
            // from the queue
            return false;
        }
    }
}
```

As you can see, this is as simple as implementing one method: __ConsumerInterface::execute__.

Keep in mind that your callbacks _need to be registered_ as normal `Symfony2` services. There you can inject the service container, the database service, the Symfony logger, and so on.

See [https://github.com/videlalvaro/php-amqplib/blob/master/doc/AMQPMessage.md](https://github.com/videlalvaro/php-amqplib/blob/master/doc/AMQPMessage.md) for more details of what's part of a message instance.

### Recap ###

This seems to be quite a lot of work for just sending messages, let's recap to have a better overview. This is what we need to produce/consume messages:

- Add an entry for the consumer/producer in the configuration.
- Implement your callback.
- Start the consumer from the CLI.
- Add the code to publish messages inside the controller.

And that's it!

### RPC or Reply/Response ###

So far we just have sent messages to consumers, but what if we want to get a reply from them? To achieve this we have to implement RPC calls into our application. This bundle makes is pretty easy to achieve such things with Symfony2.

Let's add a RPC client and server into the configuration:

```yaml
rpc_clients:
    integer_store:
        connection: default
rpc_servers:
    random_int:
        connection: default
        callback: random_int_server
```

Here we have a very useful server: it returns random integers to its clients. The callback used to process the request will be the __random\_int\_server__ service. Now let's see how to invoke it from our controllers.

First we have to start the server from the command line:

```bash
./app/console_dev rabbitmq:rpc-server random_int
```

And then add the following code to our controller:

```php
public function indexAction($name)
{
    ...
    $client = $this->get('old_sound_rabbit_mq.integer_store_rpc');
    $client->addRequest(serialize(array('min' => 0, 'max' => 10)), 'random_int', 'request_id');
    $replies = $client->getReplies();
    ...
}
```

As you can see there, if our client id is __integer\_store__, then the service name will be __old_sound_rabbit_mq.integer\_store_rpc__. Once we get that object we place a request on the server by calling `addRequest` that expects three parameters:

- The arguments to be sent to the remote procedure call.
- The name of the RPC server, in our case __random\_int__.
- A request identifier for our call, in this case __request\_id__.

The arguments we are sending are the __min__ and __max__ values for the `rand()` function. We send them by serializing an array. If our server expects JSON information, or XML, we will send such data here.

The final piece is to get the reply. Our PHP script will block till the server returns a value. The __$replies__ variable will be an associative array where each reply from the server will contained in the respective __request\_id__ key.

As you can guess, we can also make __parallel RPC calls__.

### Parallel RPC ###

Let's say that for rendering some webpage, you need to perform two database queries, one taking 5 seconds to complete and the other one taking 2 seconds –very expensive queries–. If you execute them sequentially, then your page will be ready to deliver in about 7 seconds. If you run them in parallel then you will have your page served in about 5 seconds. With RabbitMqBundle we can do such parallel calls with ease. Let's define a parallel client in the config and another RPC server:

```yaml
    ...
    rpc_clients:
        parallel:
            connection: default
    rpc_servers:
        char_count:
            connection: default
            callback: char_count_server
        random_int:
            connection: default
            callback: random_int_server
    ...
```

Then this code should go in our controller:

```php
public function indexAction($name)
{
    $client = $this->get('old_sound_rabbit_mq.parallel_rpc');
    $client->addRequest($name, 'char_count', 'char_count');
    $client->addRequest(serialize(array('min' => 0, 'max' => 10)), 'random_int', 'random_int');
    $replies = $client->getReplies();
}
```

Is very similar to the previous example, we just have an extra `addRequest` call. Also we provide meaningful request identifiers so later will be easier for us to find the reply we want in the __$replies__ array.

### Anonymous Consumers ###

Now, why will we ever need anonymous consumers? This sounds like some internet threat or something… Keep reading.

In AMQP there's a type of exchange called __topic__ where the messages are routed to queues based on –you guess– the topic of the message. We can send logs about our application to a RabbiMQ topic exchange using as topic the hostname where the log was created and the severity of such log. The message body will be the log content and our routing keys the will be like this:

- server1.error
- server2.info
- server1.warning
- ...

Since we don't want to be filling up queues with unlimited logs what we can do is that when we want to monitor the system, we can launch a consumer that creates a queue and attaches to the __logs__ exchange based on some topic, for example, we would like to see all the errors reported by our servers. The routing key will be something like: __\#.error__. In such case we have to come up with a queue name, bind it to the exchange, get the logs, unbind it and delete the queue. Happily AMPQ provides a way to do this automatically if you provide the right options when you declare and bind the queue. The problem is that you don't want to remember all those options. For such reason we implemented the __Anonymous Consumer__ pattern.

When we start an Anonymous Consumer, it will take care of such details and we just have to think about implementing the callback for when the messages arrive. Is it called Anonymous because it won't specify a queue name, but it will wait for RabbitMQ to assign a random one to it.

Now, how to configure and run such consumer?

```yaml
old_sound_rabbit_mq.config:
    ...
    anon_consumers:
        logs_watcher:
            connection: default
            exchange_options: {name: 'app-logs', type: topic}
            callback:         logs_watcher
    ...
```

There we specify the exchange name and it's type along with the callback that should be executed when a message arrives.

This Anonymous Consumer is now able to listen to Producers, which are linked to the same exchange and of type _topic_:

```yaml
old_sound_rabbit_mq.config:
    ...
    producers:
        app_logs:
            connection: default
            exchange_options: {name: 'app-logs', type: topic}
    ...
```

To start an _Anonymous Consumer_ we use the following command:

```bash
./app/console_dev rabbitmq:anon-consumer -m 5 -r '#.error' logs_watcher
```

The only new option compared to the commands that we have seen before is the one that specifies the __routing key__: `-r '#.error'`.

### STDIN Producer ###

There's a Command that reads data from STDIN and publishes it to a RabbitMQ queue. To use it first you have to configure a `producer` service in your configuration file like this:

```yaml
producers:
    words:
      connection: default
      exchange_options: {name: 'words', type: direct}
```

That producer will publish messages to the `words` direct exchange. Of course you can adapt the configuration to whatever you like.

Then let's say you want to publish the contents of some XML files so they are processed by a farm of consumers. You could publish them by just using a command like this:

```bash
$ find vendor/symfony/ -name "*.xml" -print0 | xargs -0 cat | ./app/console rabbitmq:stdin-producer words
```

This means you can compose producers with plain Unix commands.

Let's decompose that one liner:

```bash
find vendor/symfony/ -name "*.xml" -print0
```

That command will find all the `.xml` files inside the symfony folder and will print the file name. Each of those file names is then _piped_ to `cat` via `xargs`:

    xargs -0 cat

And finally the output of `cat` goes directly to our producer that is invoked like this:

```bash
./app/console rabbitmq:stdin-producer words
```

It takes only one argument which is the name of the producer as you configured it in your `config.yml` file.

## How To Contribute ##

To contribute just open a Pull Request with your new code taking into account that if you add new features or modify existing ones you have to document in this README what they do. If you break BC then you have to document it as well. Also you have to update the CHANGELOG. So:

- Document New Features.
- Update CHANGELOG.
- Document BC breaking changes.


## License ##

See: resources/meta/LICENSE.md

## Credits ##

The bundle structure and the documentation is partially based on the [RedisBundle](http://github.com/Seldaek/RedisBundle)
