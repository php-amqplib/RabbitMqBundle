# Optimize consuming a lot of queues by combine to one command

Consume multiple queues by one worker command for reduce complexity and simplify debugging.
A lot of running php commands can consume significant memory size and would be not convinient in development and testing environment which no need parallel executation.

#TODO
```bash
    $ ./bin/console rabbitmq:group:consumer all
```
#TODO