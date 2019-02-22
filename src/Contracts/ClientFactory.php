<?php

namespace Jenky\LaravelElasticsearch\Contracts;

use Elasticsearch\Client;

interface ClientFactory
{
    /**
     * Make the Elasticsearch client for the given configuration.
     *
     * @param  array $config
     * @return \Jenky\LaravelElasticsearch\Contracts\ConnectionInterface
     */
    public function make(array $config);
}
