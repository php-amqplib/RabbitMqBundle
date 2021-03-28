### RPC or Reply/Response ###

So far we just have sent messages to consumers, but what if we want to get a reply from them? To achieve this we have to implement RPC calls into our application. This bundle makes it pretty easy to achieve such things with Symfony.

Let's add a RPC client and server into the configuration:

# TODO

*For a full configuration reference please use the `php app/console config:dump-reference old_sound_rabbit_mq` command.*

Here we have a very useful server: it returns random integers to its clients. The callback used to process the request will be the __random\_int\_server__ service. Now let's see how to invoke it from our controllers.

First we have to start the server from the command line:

```bash
$ ./app/console_dev rabbitmq:rpc-server random_int
```

And then add the following code to our controller:

#TODO

```php
public function indexAction($name)
{
    $client = $this->get('old_sound_rabbit_mq.integer_store_rpc');
    $client->addRequest(serialize(array('min' => 0, 'max' => 10)), 'random_int', 'request_id');
    $replies = $client->getReplies();
}
```

As you can see there, if our client id is __integer\_store__, then the service name will be __old_sound_rabbit_mq.integer\_store_rpc__. Once we get that object we place a request on the server by calling `addRequest` that expects three parameters:

- The arguments to be sent to the remote procedure call.
- The name of the RPC server, in our case __random\_int__.
- A request identifier for our call, in this case __request\_id__.

The arguments we are sending are the __min__ and __max__ values for the `rand()` function. We send them by serializing an array. If our server expects JSON information, or XML, we will send such data here.

The final piece is to get the reply. Our PHP script will block till the server returns a value. The __$replies__ variable will be an associative array where each reply from the server will contained in the respective __request\_id__ key.

By default the RPC Client expects the response to be serialized. If the server you are working with returns a non-serialized result then set the RPC client expect_serialized_response option to false. For example, if the integer_store server didn't serialize the result the client would be set as below:

```yaml
#TODO
rpc_clients:
    integer_store:
        connection: default
        expect_serialized_response: false
```

You can also set a expiration for request in seconds, after which message will no longer be handled by server and client request will simply time out. Setting expiration for messages works only for RabbitMQ 3.x and above. Visit http://www.rabbitmq.com/ttl.html#per-message-ttl for more information.

```php
public function indexAction($name)
{
    $expiration = 5; // seconds
    $client = $this->get('old_sound_rabbit_mq.integer_store_rpc');
    $client->addRequest($body, $server, $requestId, $routingKey, $expiration);
    try {
        $replies = $client->getReplies();
        // process $replies['request_id'];
    } catch (\PhpAmqpLib\Exception\AMQPTimeoutException $e) {
        // handle timeout
    }
}
```

As you can guess, we can also make __parallel RPC calls__.

### Parallel RPC ###

Let's say that for rendering some webpage, you need to perform two database queries, one taking 5 seconds to complete and the other one taking 2 seconds –very expensive queries–. If you execute them sequentially, then your page will be ready to deliver in about 7 seconds. If you run them in parallel then you will have your page served in about 5 seconds. With RabbitMqBundle we can do such parallel calls with ease. Let's define a parallel client in the config and another RPC server:

```yaml
#TODO
rpc_clients:
    parallel:
        connection: default
rpc_servers:
    char_count:
        connection: default
        callback:   char_count_server
    random_int:
        connection: default
        callback:   random_int_server
```

Then this code should go in our controller:

```php
@TODO
public function indexAction($name)
{
    $client = $this->get('old_sound_rabbit_mq.parallel_rpc');
    $client->addRequest($name, 'char_count', 'char_count');
    $client->addRequest(serialize(array('min' => 0, 'max' => 10)), 'random_int', 'random_int');
    $replies = $client->getReplies();
}
```

Is very similar to the previous example, we just have an extra `addRequest` call. Also we provide meaningful request identifiers so later will be easier for us to find the reply we want in the __$replies__ array.

### Direct Reply-To clients ###

To enable [direct reply-to clients](https://www.rabbitmq.com/direct-reply-to.html) you just have to enable option __direct_reply_to__ on the __rpc_clients__ configuration for the client.

This option will use pseudo-queue __amq.rabbitmq.reply-to__ when doing RPC calls. On the RPC server there is no modification needed.
