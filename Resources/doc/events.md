#### Consumer Events ####

See `\OldSound\RabbitMqBundle\Event\...` events
# TODO

Event raised before processing a AMQPMessages.

If the process message will throw an Exception the event will not raise.

##### IDLE MESSAGE #####

OnIdleEvent

Event raised when `wait` method exit by timeout without receiving a message.
In order to make use of this event a consumer `idle_timeout` has to be [configured](#idle-timeout).
By default process exit on idle timeout, you can prevent it by setting `$event->setForceStop(false)` in a listener.


#TODO

Use services ressetter after processing a AMQPMessages event for prevent memory leak.

```yaml
services:
  app.subscriber.after_processing_services_resetter:
    parent: 'old_sound_rabbit_mq.subscriber.after_processing_services_resetter'
    tags:
      - { name: kernel.event_subscriber }
```