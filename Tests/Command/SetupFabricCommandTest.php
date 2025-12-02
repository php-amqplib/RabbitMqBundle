<?php

declare(strict_types=1);

namespace OldSound\RabbitMqBundle\Tests\Command;

use OldSound\RabbitMqBundle\Command\SetupFabricCommand;
use OldSound\RabbitMqBundle\RabbitMq\AmqpPartsHolder;
use OldSound\RabbitMqBundle\RabbitMq\AnonConsumer;
use OldSound\RabbitMqBundle\RabbitMq\BatchConsumer;
use OldSound\RabbitMqBundle\RabbitMq\Consumer;
use OldSound\RabbitMqBundle\RabbitMq\DynamicConsumer;
use OldSound\RabbitMqBundle\RabbitMq\MultipleConsumer;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \OldSound\RabbitMqBundle\Command\SetupFabricCommand
 */
class SetupFabricCommandTest extends KernelTestCase
{
    public function testExecute(): void
    {
        $application = $this->createApplication();

        $command = new SetupFabricCommand();
        $container = $application->getKernel()->getContainer();

        $partsHolder = new AmqpPartsHolder();
        $consumer = $this->createMock(Consumer::class);
        $consumer->expects($this->once())->method('setupFabric');
        $partsHolder->addPart('old_sound_rabbit_mq.base_amqp', $consumer);

        $multipleConsumer = $this->createMock(MultipleConsumer::class);
        $multipleConsumer->expects($this->once())->method('setupFabric');
        $partsHolder->addPart('old_sound_rabbit_mq.base_amqp', $multipleConsumer);

        $batchConsumer = $this->createMock(BatchConsumer::class);
        $batchConsumer->expects($this->once())->method('setupFabric');
        $partsHolder->addPart('old_sound_rabbit_mq.base_amqp', $batchConsumer);

        $anonConsumer = $this->createMock(AnonConsumer::class);
        $anonConsumer->expects($this->once())->method('setupFabric');
        $partsHolder->addPart('old_sound_rabbit_mq.base_amqp', $anonConsumer);

        $dynamicConsumer = $this->createMock(DynamicConsumer::class);
        $dynamicConsumer->expects($this->never())->method('setupFabric');
        $partsHolder->addPart('old_sound_rabbit_mq.base_amqp', $dynamicConsumer);

        $container->set('old_sound_rabbit_mq.parts_holder', $partsHolder);

        $command->setContainer($container);

        // TODO: Use addCommand() once Symfony Support for < 7.4 is dropped
        $application->add($command);

        $registeredCommand = $application->find('rabbitmq:setup-fabric');

        $commandTester = new CommandTester($registeredCommand);

        $commandTester->execute([
            'command'  => $registeredCommand->getName(),
        ], [
            'verbosity' => OutputInterface::VERBOSITY_VERBOSE,
        ]);

        self::assertSame(0, $commandTester->getStatusCode());
    }

    public function testExecute_withSkipAnonConsumers(): void
    {
        $application = $this->createApplication();

        $command = new SetupFabricCommand();
        $container = $application->getKernel()->getContainer();

        $partsHolder = new AmqpPartsHolder();
        $consumer = $this->createMock(Consumer::class);
        $consumer->expects($this->once())->method('setupFabric');
        $partsHolder->addPart('old_sound_rabbit_mq.base_amqp', $consumer);

        $multipleConsumer = $this->createMock(MultipleConsumer::class);
        $multipleConsumer->expects($this->once())->method('setupFabric');
        $partsHolder->addPart('old_sound_rabbit_mq.base_amqp', $multipleConsumer);

        $batchConsumer = $this->createMock(BatchConsumer::class);
        $batchConsumer->expects($this->once())->method('setupFabric');
        $partsHolder->addPart('old_sound_rabbit_mq.base_amqp', $batchConsumer);

        $anonConsumer = $this->createMock(AnonConsumer::class);
        $anonConsumer->expects($this->never())->method('setupFabric');
        $partsHolder->addPart('old_sound_rabbit_mq.base_amqp', $anonConsumer);

        $dynamicConsumer = $this->createMock(DynamicConsumer::class);
        $dynamicConsumer->expects($this->never())->method('setupFabric');
        $partsHolder->addPart('old_sound_rabbit_mq.base_amqp', $dynamicConsumer);

        $container->set('old_sound_rabbit_mq.parts_holder', $partsHolder);

        $command->setContainer($container);

        // TODO: Use addCommand() once Symfony Support for < 7.4 is dropped
        $application->add($command);

        $registeredCommand = $application->find('rabbitmq:setup-fabric');

        $commandTester = new CommandTester($registeredCommand);

        $commandTester->execute([
            'command'  => $registeredCommand->getName(),
            '--skip-anon-consumers' => true,
        ], [
            'verbosity' => OutputInterface::VERBOSITY_VERBOSE,
        ]);

        self::assertSame(0, $commandTester->getStatusCode());
    }

    private function createApplication(): Application
    {
        $kernel = self::createKernel();
        $kernel->boot();

        return new Application($kernel);
    }
}
