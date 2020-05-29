<?php

namespace Jenky\Elastify\Contracts;

interface ConnectionResolver
{
    /**
     * Get a database connection instance.
     *
     * @param  string|null  $name
     * @return \Jenky\Elastify\Contracts\ConnectionInterface
     */
    public function connection(?string $name = null): ConnectionInterface;

    /**
     * Get the default connection name.
     *
     * @return string
     */
    public function getDefaultConnection(): string;

    /**
     * Set the default connection name.
     *
     * @param  string  $name
     * @return void
     */
    public function setDefaultConnection($name);
}
