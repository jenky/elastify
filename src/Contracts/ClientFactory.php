<?php

namespace Jenky\LaravelElasticsearch\Contracts;

use Elasticsearch\Client;

interface ClientFactory
{
    /**
     * Make the Elasticsearch client for the given configuration.
     *
     * @param  array $config
     * @return \Elasticsearch\Client
     */
    public function make(array $config): Client;
}
