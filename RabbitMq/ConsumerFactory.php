<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

use OldSound\RabbitMqBundle\Declarations\ConsumerDef;
use OldSound\RabbitMqBundle\Receiver\ArgumentResolver;
use OldSound\RabbitMqBundle\Receiver\ArgumentValueResolverInterface;

class ConsumerFactory implements ConsumerFactoryInterface
{
    /** @var ArgumentValueResolverInterface[] */
    private $resolvers;

    public function __consturct()
    {
        $this->resolvers = [];
    }

    public function addAgumentResolver(ArgumentValueResolverInterface $argumentValueResolver)
    {
        $this->resolvers[] = $argumentValueResolver;
    }

    public function create(ConsumerDef $consumerDef): Consumer
    {
        $argumentResolver = new ArgumentResolver(null,
            [...$this->resolvers, ...ArgumentResolver::getDefaultArgumentValueResolvers()]
        );
        return new Consumer($consumerDef, $argumentResolver);
    }
}