<?php

namespace Jenky\Elastify\Contracts;

use Elasticsearch\Client;

interface ClientFactory
{
    /**
     * Make the Elasticsearch client for the given configuration.
     *
     * @param  array $config
     * @return \Jenky\Elastify\Contracts\ConnectionInterface
     */
    public function make(array $config);
}
