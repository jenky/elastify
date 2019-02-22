<?php

namespace Jenky\LaravelElasticsearch\Contracts;

interface ConnectionResolver
{
    /**
     * Get a database connection instance.
     *
     * @param  string  $name
     * @return \Jenky\LaravelElasticsearch\Contracts\ConnectionInterface
     */
    public function connection($name = null): ConnectionInterface;

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
    public function setDefaultConnection($name): string;
}
