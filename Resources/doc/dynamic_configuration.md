### Dynamic Configuration ###

### Dynamic Connection ###

Sometimes your connection information may need to be dynamic. Dynamic connection parameters allow you to supply or
override parameters programmatically through a service.

e.g. In a scenario when the `vhost` parameter of the connection depends on the current tenant of your white-labeled
application and you do not want (or can't) change it's configuration every time.

Example Implementation:
```yaml
services:
  app.my_rabbit_connection_factory:
    class: App\MyRabbitmqConnectionFactory
  app.my_rabbit_connection:
    class: PhpAmqpLib\Connection\AMQPAbstractConnection
    factory: ['app.my_rabbit_connection_factory', 'create']
    arguments: ['special_server_label']
  old_sound_rabbit_mq.connection.my_dynamic: # my_dynamic would be support as value in old_sound_rabbit_mq for connection parameter
    alias: '@app.my_rabbit_connection' 
```

```php
<?php
class MyRabbitmqConnectionFactory
{
    public function create($serverLabel): AMQPAbstractConnection { /* create dynamically */ }
}
```

In this case, the AMQPAbstractConnection will be craeted from MyRabbitmqConnectionFactory service.


#### Dynimic consumer ####

Example dynimic consumer with multiple `consumeQueues` combined from all consumers with `crm` connection for reduce complexity and simplify debugging.
A lot of running php commands can consume significant memory size and would be not convinient in development and testing environment which no need parallel executation.

You can define consumer by `services.yml`.

```shell
services:
  old_sound_rabbit_mq.consumer.crm_all: # would be available from rabbitmq:consumer crm_all
    public: true
    class: OldSound\RabbitMqBundle\RabbitMq\Consumer
    arguments: ["@old_sound_rabbit_mq.channel.crm"] # inject crm connection channel
    tags:
      - { name: 'old_sound_rabbit_mq.consumer', consumer: 'crm_all' }
    calls:
      - { method: 'consumeQueues', arguments: [!tagged_iterator 'old_sound_rabbit_mq.crm.queue_consuming'] } # inject all crm consumeQueues items
```

Execute all `crm` connection `consumerQueues` together. Inludes `rpc_sum` and `hight_priority_orders`
```bash
$ ./app/console rabbitmq:consumer crm_all -vvv
```

```bash
echo "{\"a\": 39, \"b\": 5}" | bin/console rabbitmq:stdin-producer direct -f raw -r rpc_sum
echo "Order #4" | bin/console rabbitmq:stdin-producer orders -f raw -r high
```



### Dynamic Consumers ###

Sometimes you have to change the consumer's configuration on the fly.
Dynamic consumers allow you to define the consumers queue options programmatically, based on the context.

e.g. In a scenario when the defined consumer must be responsible for a dynamic number of topics and you do not want (or can't) change it's configuration every time.

```yaml
service:
    app.my_consumer_factory:
      class: App\ConsumerFactory
    app.my_dynamic_consumer:
        class: OldSound\RabbitMqBundle\RabbitMq\Consumer
        factory: "@app.my_consumer_factory"
        tags:
          - { old_sound_rabbit_mq.consumer: { name: 'my_dynamic_consumer' } } # allow use for "bin/console rabbitmq:consumer" command
```