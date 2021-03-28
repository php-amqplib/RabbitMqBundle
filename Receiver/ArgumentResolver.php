<?php


namespace OldSound\RabbitMqBundle\Receiver;

use OldSound\RabbitMqBundle\Declarations\ConsumeOptions;
use OldSound\RabbitMqBundle\Receiver\ArgumentResolver\ConsumeOptionsResolver;
use OldSound\RabbitMqBundle\Receiver\ArgumentResolver\MessagesValueResolver;

final class ArgumentResolver implements ArgumentResolverInterface
{
    private $argumentMetadataFactory;

    /**
     * @var iterable|ArgumentValueResolverInterface[]
     */
    private $argumentValueResolvers;

    public function __construct(ArgumentMetadataFactoryInterface $argumentMetadataFactory = null, iterable $argumentValueResolvers = [])
    {
        $this->argumentMetadataFactory = $argumentMetadataFactory ?: new ArgumentMetadataFactory();
        $this->argumentValueResolvers = $argumentValueResolvers ?: self::getDefaultArgumentValueResolvers();
    }

    /**
     * {@inheritdoc}
     */
    public function getArguments(array $messages, ConsumeOptions $options): array
    {
        $arguments = [];

        foreach ($this->argumentMetadataFactory->createArgumentMetadata($options->receiver) as $metadata) {
            foreach ($this->argumentValueResolvers as $resolver) {
                if (!$resolver->supports($messages, $options, $metadata)) {
                    continue;
                }

                $resolved = $resolver->resolve($messages, $options, $metadata);

                $atLeastOne = false;
                foreach ($resolved as $append) {
                    $atLeastOne = true;
                    $arguments[] = $append;
                }

                if (!$atLeastOne) {
                    throw new \InvalidArgumentException(sprintf('"%s::resolve()" must yield at least one value.', get_debug_type($resolver)));
                }

                // continue to the next controller argument
                continue 2;
            }

            $representative = $receiver;

            if (\is_array($representative)) {
                $representative = sprintf('%s::%s()', \get_class($representative[0]), $representative[1]);
            } elseif (\is_object($representative)) {
                $representative = \get_class($representative);
            }

            throw new \RuntimeException(sprintf('Controller "%s" requires that you provide a value for the "$%s" argument. Either the argument is nullable and no null value has been provided, no default value has been provided or because there is a non optional argument after this one.', $representative, $metadata->getName()));
        }

        return $arguments;
    }

    public static function getDefaultArgumentValueResolvers(): iterable
    {
        return [
            new MessagesValueResolver(),
            new ConsumeOptionsResolver()
        ];
    }
}