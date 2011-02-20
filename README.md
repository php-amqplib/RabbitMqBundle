# RabbitMqBundle #

## About ##

The RabbitMqBundle incorporates messaging in your application via [RabbitMq](http://www.rabbitmq.com/) using the [php-amqplib](http://github.com/tnc/php-amqplib) library.

The bundle implements several messaging patterns as seen on the [Thumper](https://github.com/videlalvaro/Thumper) library.

All the examples expects a running RabbitMQ.

## Installation ##

The following instructions expect a project created with the [Symfony2 sandbox PR6](http://symfony-reloaded.org/downloads/sandbox_2_0_PR6.zip)

Put the RabbitMqBundle into the src/ dir:

    $ mkdir -p src/OldSound
    $ git clone git://github.com/videlalvaro/RabbitMqBundle.git src/OldSound/RabbitMqBundle

Put the [php-amqplib](http://github.com/tnc/php-amqplib) library into the vendor dir:

    $ git clone git://github.com/tnc/php-amqplib.git vendor/php-amqplib

Add the [php-amqplib](http://github.com/tnc/php-amqplib) autoloading to your project's bootstrap script (app/autoload.php):

    spl_autoload_register(function($class) use ($vendorDir)
    {
        if (strpos($class, 'AMQPConnection') === 0) {
            require_once $vendorDir.'/php-amqplib/amqp.inc';
            return true;
        }
    });

Add the RabbitMqBundle to your application's kernel:

    public function registerBundles()
    {
        $bundles = array(
            ...
            new OldSound\RabbitMqBundle\RabbitMqBundle(),
            ...
        );
        ...
    }


## Usage ##

Configure the `rabbitmq` service in your config:

    rabbitmq.config:
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
                callback:         @upload_picture_service
        ...

You have to configure at least one connection and one client. In the above
example your service container will contain the service `rabbitmq.upload_picture_producer` and `rabbitmq.upload_picture_consumer`. The later expects that there's a service called `upload_picture_service`.

If you don't specify a connection for the client, the client will look for a connection with the same alias. So for our `upload_picture` the service container will look for an `upload_picture` connection.

## Producers, Consumers, What? ##

In a messaging application, the process sending messages to the broker is called __producer__ while the process receiving those messages is called __consumer__. In your application you will have several of them that you can list under the their respective entries in the configuration.

### Producer ###

A producer will be used to send messages to the server. In the AMQP Model, messages are sent to an __exchange__, this means that in the configuration for a producer you will have to specify the connection options along with the exchange options, which usually will be the name of the exchange and the type of it.

Now let's say that you want to process picture uploads in the background. After you move the picture to its final location, you will publish a message to server with the following information: 

    public function indexAction($name)
    {
        ...
        $msg = array('user_id' => 1235, 'image_path' => '/path/to/new/pic.png');
        $this->get('rabbitmq.upload_picture_producer')->publish(serialize($msg));
        ...
    }
    
The next piece of the puzzle is to have a consumer that will take the message out of the queue and process it accordingly.

### Consumers ###

A consumer will connect to the server and start a __loop__  waiting for incoming messages to process. Depending on the specified __callback__ for such consumer will be the behavior it will have. Let's review the consumer configuration from above:

    ...
    consumers:
        upload_picture:
            connection: default
            exchange_options: {name: 'upload-picture', type: direct}
            queue_options:    {name: 'upload-picture'}
            callback:         @upload_picture_service
    ...
    
As we see there, the __callback__ option has a reference to an __upload\_picture\_service__. When the consumer gets a message from the server it will execute such service. If for testing or debugging purposes you need to specify a different callback, then you can change it there. 

Apart from the callback we also specify the connection to use, the same way as we do with a __producer__. The remaining options are the __exchange\_options___ and the __queue\_options__. The __exchange\_options__ should be the same ones as those used for the __producer__. In the __queue\_options__ we will provide a __queue name___. Why?

As we said, messages in AMQP are published to an __exchange__. This doesn't mean the message has reached a __queue__. For this to happen, first we need to create such __queue__ and then bind it to the __exchange__. The cool thing about this is that you can bind several __queues__ to one __exchange__, in that way one message can arrive to several destinations. The advantage of this approach is the __decoupling__ from the producer and the consumer. The producer does not care about how many consumers will process his messages. All it needs is that his message arrives to the server. In this way we can expand the actions we perform every time a picture is uploaded without the need to change code in our controller.

Now, how to run a consumer? There's a command for it that can be executed like this:

    $ ./app/console_dev rabbitmq:consumer -m 50 upload_picture
    
What does this mean? We are executing the __upload\_picture__ consumer telling it to consume only 50 messages. Every time the consumer receives a message from the server, it will execute the configured callback. 

### Callbacks ###

Here's an example callback:

    <?php
    
    //src/Sensio/HelloBundle/Consumer/UploadPictureConsumer.php

    namespace Sensio\HelloBundle\Consumer;

    use Symfony\Component\DependencyInjection\ContainerAware;
    use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;

    class UploadPictureConsumer extends ContainerAware implements ConsumerInterface
    {
        public function execute($msg)
        {
            //Process picture upload. 
            //$msg will be what was published from the Controller.
        }
    }
    
As you can see, this is as simple as implementing one method: __ConsumerInterface::execute__.

Keep in mind that because the callback class extends ContainerAware, it means it has access the __Symfonny2__ service container that is specific to the current running application.

### Recap ###

This seems quite a lot, let's recap to have a better overview. This is what we need to produce/consume messages.

- Add an entry for the consumer/producer in the configuration.
- Implement your callback.
- Start the consumer form the CLI.
- Add the code to publish messages inside the controller.

And that's it!

### RPC or Reply/Response ###

Currently supported, not yet documented.

### Parallel RPC ###

Currently supported, not yet documented.

### Anonymous Consumers ###

Currently supported, not yet documented.

## License ##

See: resources/meta/LICENSE.md

## Credits ##

The bundle structure and the documentation is partially based on the [RedisBundle](http://github.com/Seldaek/RedisBundle)