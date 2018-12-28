<?php

namespace Jenky\LaravelElasticsearch\Contracts;

use Elasticsearch\Client;

interface ConnectionResolver
{
    /**
     * Get a database connection instance.
     *
     * @param  string  $name
     * @return \Elasticsearch\Client
     */
    public function connection($name = null): Client;

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
