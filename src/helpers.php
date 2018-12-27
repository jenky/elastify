<?php

use Cviebrock\LaravelElasticsearch\Manager;

if (! function_exists('elasticsearch')) {
    /**
     * Get a elasticsearch client instance.
     *
     * @param  string  $connection
     * @return \Elasticsearch\Client
     */
    function elasticsearch($connection = null)
    {
        return app(Manager::class)->connection($connection);
    }
}
