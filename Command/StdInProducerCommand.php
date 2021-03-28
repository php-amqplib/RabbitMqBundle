<?php

namespace OldSound\RabbitMqBundle\Command;

use OldSound\RabbitMqBundle\RabbitMq\Producer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class StdInProducerCommand extends Command
{
    use ContainerAwareTrait;

    const FORMAT_PHP = 'php';
    const FORMAT_RAW = 'raw';

    protected function configure()
    {
        parent::configure();

        $this
            ->setName('rabbitmq:stdin-producer')
            ->addArgument('name', InputArgument::REQUIRED, 'Producer Name')
            ->setDescription('Executes a producer that reads data from STDIN')
            ->addOption('route', 'r', InputOption::VALUE_OPTIONAL, 'Routing Key', '')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Payload Format', self::FORMAT_PHP)
            ->addOption('debug', 'd', InputOption::VALUE_OPTIONAL, 'Enable Debugging', false)
        ;
    }

    /**
     * Executes the current command.
     *
     * @param InputInterface  $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     *
     * @return integer 0 if everything went fine, or an error code
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        define('AMQP_DEBUG', (bool) $input->getOption('debug'));

        $producerName = $input->getArgument('name');
        $alias = sprintf('old_sound_rabbit_mq.producer.%s', $producerName);
        if (!$this->container->has($alias)) {
            $producerNames = []; // TODO $this->container->getParameter('old_sound_rabbit_mq.allowed_producer_names');
            throw new InvalidArgumentException(sprintf('Producer %s is undefined. Allowed ones: %s', $producerName, join(', ', $producerNames)));
        }

        /** @var Producer $producer */
        $producer = $this->container->get($alias);

        $data = '';
        while (!feof(STDIN)) {
            $data .= fread(STDIN, 8192);
        }

        $route = $input->getOption('route');
        $format = $input->getOption('format');

        switch ($format) {
            case self::FORMAT_RAW:
                break; // data as is
            case self::FORMAT_PHP:
                $data = serialize($data);
                break;
            default:
                throw new \InvalidArgumentException(sprintf('Invalid payload format "%s", expecting one of: %s.',
                    $format, implode(', ', array(self::FORMAT_PHP, self::FORMAT_RAW))));
        }

        $producer->publish($data, $route);

        return 0;
    }
}
