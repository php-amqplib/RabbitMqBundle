## Other Commands ##

### Setting up the RabbitMQ fabric ###

The purpose of this bundle is to let your application produce messages and publish them to some exchanges you configured.

In some cases and even if your configuration is right, the messages you are producing will not be routed to any queue because none exist. The consumer responsible for the queue consumption has to be run for the queue to be created.

Launching a command for each consumer can be a nightmare when the number of consumers is high.

In order to create exchanges, queues and bindings at once and be sure you will not lose any message, you can run the following command:

```bash
$ ./app/console rabbitmq:declare
```

When desired, you can configure your consumers and producers to assume the RabbitMQ fabric is already defined. To do this, add the following to your configuration:

```yaml
producers:
    upload_picture:
      auto_declare: false
```

By default a consumer or producer will declare everything it needs with RabbitMQ when it starts.
Be careful using this, when exchanges or queues are not defined, there will be errors. When you've changed any configuration you need to run the above setup-fabric command to declare your configuration.



The rabbitmq:setup-fabric command will declare exchanges and queues as defined in your producer, consumer
and multi consumer configurations before it creates your arbitrary bindings. However, the rabbitmq:setup-fabric will
*NOT* declare addition queues and exchanges defined in the bindings. It's up to you to make sure exchanges/queues are declared.

If you want to remove all the messages awaiting in a queue, you can execute this command to purge this queue:

```bash
$ ./app/console rabbitmq:purge --no-confirmation upload_picture
```

For deleting the consumer's queue, use this command:

```bash
$ ./app/console rabbitmq:delete --no-confirmation upload_picture
```

### STDIN Producer ###

There's a Command that reads data from STDIN and publishes it to a RabbitMQ queue. To use it first you have to configure a `producer` service in your configuration file like this:

That producer will publish messages to the `words` direct exchange. Of course you can adapt the configuration to whatever you like.

Then let's say you want to publish the contents of some XML files so they are processed by a farm of consumers. You could publish them by just using a command like this:

```bash
$ find vendor/symfony/ -name "*.xml" -print0 | xargs -0 cat | ./app/console rabbitmq:stdin-producer words
```

This means you can compose producers with plain Unix commands.

Let's decompose that one liner:

```bash
$ find vendor/symfony/ -name "*.xml" -print0
```

That command will find all the `.xml` files inside the symfony folder and will print the file name. Each of those file names is then _piped_ to `cat` via `xargs`:

```bash
$ xargs -0 cat
```

And finally the output of `cat` goes directly to our producer that is invoked like this:

```bash
$ ./app/console rabbitmq:stdin-producer words
```

It takes only one argument which is the name of the producer as you configured it in your `config.yml` file.

