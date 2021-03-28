# Complex configuration example #
```yaml
old_sound_rabbit_mq:
  connections: # multiple connections
    infrastructure:
      host: 'rabbitmq'
      port: 5672
      user: 'user'
      password: 'user'
      vhost: '/'
      lazy: false
      connection_timeout: 3
      read_write_timeout: 3
      keepalive: false
      heartbeat: 0
    crm:
      # A different (unused) connection defined by an URL. One can omit all parts,
      # except the scheme (amqp:). If both segment in the URL and a key value (see above)
      # are given the value from the URL takes precedence.
      # See https://www.rabbitmq.com/uri-spec.html on how to encode values.
      url: 'amqp://user:user@rabbitmq:5672?lazy=1&connection_timeout=10'

  declarations:
    exchanges:
      # infrastructure
      - name: logs
        type: topic
        bindings:
          - { destination: info_logs, routing_keys: ['debug', 'info'] }
          - { destination: warning_logs, routing_keys: ['notice', 'warning'] }
          - { destination: critical_logs, routing_keys: ['error', 'critical'] }
          - { destination: emergency_logs, routing_keys: ['alert', 'emergency'] }
      # crm
      - { name: orders, type: topic }

    queues:
      # infrastructure
      info_logs: ~
        # default values
        # name: info_logs by default key
        # durable: true
        # auto_delete: false
      warning_logs:
        name: warning_logs
        arguments: {'x-message-ttl': ['I', 20000]}
      critical_logs: ~
        arguments: {'x-ha-policy': ['S', 'all']} }
      emergency_logs: ~
      # crm
      high_priority_orders: ~

    # alternative way to declare bindings
    bindings:
      # payment
      - { exchange: bills, destination: bills, routing_key: 'baz.*' }
      # market
      - { exchange: orders, destination: high_priority_orders, routing_key: 'high' }


  producers:
    # infrastructure
    logs:
      connection: infrastructure
      exchange: logs

    # crm
    orders:
      connection: crm
      exchange: orders

  consumers:
    # infrastructure
    logs:
      connection: infrastructure # without specify would be equals 'default'
      # idle_timeout: 60
      # idle_timeout_exit_code: 0
      # timeout_wait: 10
      # graceful_max_execution:
      #   timeout: 1800 # 30 minutes 
      #   exit_code: 10 # default is 0 
      consumeQueues: # Here is how you can set a consumer with multiple queues:
        - { queue: info_logs, callback: App\Consumer\BatchLogsConsumer, batch_count: 500, qos_prefetch_count: 500 }
        - { queue: warning_logs, callback: App\Consumer\BatchLogsConsumer, batch_count: 100, qos_prefetch_count: 100 }
        - { queue: critical_logs, callback: App\Consumer\BatchLogsConsumer, batch_count: 3, qos_prefetch_count: 3 }
        - { queue: emergency_logs, callback: App\Consumer\BatchLogsConsumer, batch_count: 1, qos_prefetch_count: 10 } 
      
    # crm
    high_priority_orders:
      connection: crm
      consumeQueues:
        - { queue: high_priority_orders, callback: App\Consumer\OrdersConsumer }
      timeout_wait: 4
    anon_orders: # anon
      connection: crm
      consumeQueues:
        - { queue: anon_orders, callback: App\Service\Handler, exclusive: true, auto_delete: true }
    rpc_sum:
      connection: crm
      consumeQueues:
        - queue: rpc_sum # rpc request queue
          callback: App\Consumer\RpcCalculatorConsumer
          serializer: json_encode # rpc
          # batch_count: ~
          # qos_prefetch_size: 0
          # qos_prefetch_count: 10
```


### Multiple Consumers ###

It's a good practice to have a lot of queues for logic separation. With a simple consumer you will have to create one worker (consumer) per queue and it can be hard to manage when dealing
with many evolutions (forget to add a line in your supervisord configuration?). This is also useful for small queues as you may not want to have as many workers as queues, and want to regroup
some tasks together without losing flexibility and separation principle.

Multiple consumers allow you to handle this use case by listening to multiple queues on the same consumer.

Specify multiple queues for `consumeQueues` direcitve.


With RabbitMqBundle, you can configure that qos_options per consumer like that:
```yaml
qos_options: {prefetch_size: 0, prefetch_count: 1, global: false}
```

Example to using
```shell
  bin/console rabbitmq:consumer logs -vvv
  
  echo "Internal project is not available" | bin/console rabbitmq:stdin-producer logs -f raw -r alert
  
  echo "Some error happened" | bin/console rabbitmq:stdin-producer logs -f raw -r error
  echo "Some error happened" | bin/console rabbitmq:stdin-producer logs -f raw -r error
  echo "Some error happened" | bin/console rabbitmq:stdin-producer logs -f raw -r error
  
  bin/console rabbitmq:consumer high_priority_orders -vvv
  
  echo "Order #4" | bin/console rabbitmq:stdin-producer orders -f raw -r high
```

### Import notice - Heartbeats ###

It's a good idea to set the ```read_write_timeout``` to 2x the heartbeat so your socket will be open. If you don't do this, or use a different multiplier, there's a risk the __consumer__ socket will timeout.

### Arbitrary Bindings ###

You may find that your application has a complex workflow and you you need to have arbitrary binding. Arbitrary
binding scenarios might include exchange to exchange bindings via `destination_is_exchange` property.

```yaml
bindings:
    - {exchange: foo, destination: bar, routing_key: 'baz.*' }
    - {exchange: foo1, destination: foo, routing_key: 'baz.*', destination_is_exchange: true}
```

# Queue arguments #
If you need to add optional queue arguments, then your queue options can be something like this:
another example with message TTL of 20 seconds:

```yaml
queues:
  warning_logs:
    arguments: {'x-message-ttl': ['I', 20000]}
```

The argument value must be a list of datatype and value. Valid datatypes are:

* `S` - String
* `I` - Integer
* `D` - Decimal
* `T` - Timestamps
* `F` - Table
* `A` - Array

Adapt the `arguments` according to your needs.



# Dequeuer TODO
Be aware that queues providers are responsible for the proper calls to `setDequeuer` and that callbacks are callables
(not `ConsumerInterface`). In case service providing queues implements `DequeuerAwareInterface`, a call to
`setDequeuer` is added to the definition of the service with a `DequeuerInterface` currently being a `MultipleConsumer`.



### Anonymous Consumers ###

Now, why will we ever need anonymous consumers? This sounds like some internet threat or something… Keep reading.

In AMQP there's a type of exchange called __topic__ where the messages are routed to queues based on –you guess– the topic of the message. We can send logs about our application to a RabbiMQ topic exchange using as topic the hostname where the log was created and the severity of such log. The message body will be the log content and our routing keys the will be like this:

- server1.error
- server2.info
- server1.warning
- ...

Since we don't want to be filling up queues with unlimited logs what we can do is that when we want to monitor the system, we can launch a consumer that creates a queue and attaches to the __logs__ exchange based on some topic, for example, we would like to see all the errors reported by our servers. The routing key will be something like: __\#.error__. In such case we have to come up with a queue name, bind it to the exchange, get the logs, unbind it and delete the queue. Happily AMPQ provides a way to do this automatically if you provide the right options when you declare and bind the queue. The problem is that you don't want to remember all those options. For such reason we implemented the __Anonymous Consumer__ pattern.

When we start an Anonymous Consumer, it will take care of such details and we just have to think about implementing the callback for when the messages arrive. Is it called Anonymous because it won't specify a queue name, but it will wait for RabbitMQ to assign a random one to it.

Specify `anon` as true for consumer

```yaml
consumers:
    logs_watcher:
        connection: default
        anon: true
        consumeQueues:
          ...
```

#### Idle timeout ####

If you need to set a timeout when there are no messages from your queue during a period of time, you can set the `idle_timeout` in seconds.
The `idle_timeout_exit_code` specifies what exit code should be returned by the consumer when the idle timeout occurs. Without specifying it, the consumer will throw an **PhpAmqpLib\Exception\AMQPTimeoutException** exception.

#### Timeout wait ####

Set the `timeout_wait` in seconds.
The `timeout_wait` specifies how long the consumer will wait without receiving a new message before ensuring the current connection is still valid.

#### Graceful max execution timeout ####

If you'd like your consumer to be running up to certain time and then gracefully exit, then set the `graceful_max_execution.timeout` in seconds.
"Gracefully exit" means, that the consumer will exit either after the currently running task or immediatelly, when waiting for new tasks.
The `graceful_max_execution.exit_code` specifies what exit code should be returned by the consumer when the graceful max execution timeout occurs. Without specifying it, the consumer will exit with status `0`.

This feature is great in conjuction with supervisord, which together can allow for periodical memory leaks cleanup, connection with database/rabbitmq renewal and more.
