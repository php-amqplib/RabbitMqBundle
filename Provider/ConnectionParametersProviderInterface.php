<?php

namespace OldSound\RabbitMqBundle\Provider;

/**
 * Interface to provide and/or override connection parameters.
 *
 * @author David Cochrum <davidcochrum@gmail.com>
 */
interface ConnectionParametersProviderInterface
{
    /**
     * Return connection parameters.
     *
     * Example:
     * array(
     *   'host' => 'localhost',
     *   'port' => 5672,
     *   'user' => 'guest',
     *   'password' => 'guest',
     *   'vhost' => '/',
     *   'lazy' => false,
     *   'connection_timeout' => 3,
     *   'read_write_timeout' => 3,
     *   'keepalive' => false,
     *   'heartbeat' => 0,
     *   'use_socket' => true,
     * )
     *
     * @return array
     */
    public function getConnectionParameters();
}
