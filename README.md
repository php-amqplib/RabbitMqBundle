# RabbitMqBundle #

[![Latest Version](http://img.shields.io/packagist/v/emag-tech-labs/rabbitmq-bundle.svg?style=flat-square)](https://github.com/eMAGTechLabs/RabbitMqBundle/releases)
[![Test](https://github.com/eMAGTechLabs/RabbitMqBundle/actions/workflows/test.yaml/badge.svg)](https://github.com/eMAGTechLabs/RabbitMqBundle/actions/workflows/test.yaml)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/eMAGTechLabs/RabbitMqBundle/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/eMAGTechLabs/RabbitMqBundle/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/eMAGTechLabs/RabbitMqBundle/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/eMAGTechLabs/RabbitMqBundle/?branch=master)
[![PHPStan](https://img.shields.io/badge/PHPStan-enabled-brightgreen.svg?style=flat-square)](https://github.com/phpstan/phpstan)

The RabbitMqBundle incorporates messaging in your application via [RabbitMQ](http://www.rabbitmq.com/) using the [php-amqplib](http://github.com/php-amqplib/php-amqplib) library.

All the examples expect a running RabbitMQ server.

## Installation ##
Require the bundle and its dependencies with composer:
```bash
$ composer require emag-tech-labs/rabbitmq-bundle
```
Ensure symfony flex auto added `OldSoundRabbitMqBundle` line to `config/bundles.php`:

### Recap ###

This seems to be quite a lot of work for just sending messages, let's recap to have a better overview. This is what we need to produce/consume messages:

- Add declarations and an entry for the consumer/producer in the configuration.
- Implement your callback.
- Start the consumer from the CLI.
- Add the code to publish messages inside the controller.

And that's it!

## Documentation ##
The source of the documentation is stored in the `Resources/doc/` folder in this bundle

[Base using](Resources/doc/using.md)
[Configuration](Resources/doc/configuration.md)
[Commands](Resources/doc/commands.md)
[Batch consumers](Resources/doc/batch_consumers.md)
[Logging](Resources/doc/logging.md)
[RPC](Resources/doc/rpc.md)
[Events](Resources/doc/events.md)
[Dynamic configuration](Resources/doc/dynamic_configuration.md)

### Receipts ###

[Kubernetes receipt](Resources/doc/kubernetes_receipt.md)
[Optimize consuming a lot of queues by combine to one command](Resources/doc/optimize_receipt.md)

## How To Contribute ##
To contribute just open a Pull Request with your new code taking into account that if you add new features or modify existing ones you have to document in this README what they do. If you break BC then you have to document it as well. Also you have to update the CHANGELOG. So:

- Document New Features.
- Update CHANGELOG.
- Document BC breaking changes.

## License ##
See: resources/meta/LICENSE.md

## Credits ##
The bundle structure and the documentation is partially based on the [RedisBundle](http://github.com/Seldaek/RedisBundle)
