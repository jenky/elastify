<?php

namespace Jenky\LaravelElasticsearch\Connection;

use Elasticsearch\Client;
use Illuminate\Support\Traits\ForwardsCalls;
use Jenky\LaravelElasticsearch\Builder\Query;
use Jenky\LaravelElasticsearch\Contracts\ConnectionInterface;
use ONGR\ElasticsearchDSL\Search;

class Connection implements ConnectionInterface
{
    use ForwardsCalls;

    /**
     * The elasticsearch client.
     *
     * @var \Elasticsearch\Client
     */
    protected $elastic;

    /**
     * Create new client instance.
     *
     * @param  \Elasticsearch\Client $client
     * @return void
     */
    public function __construct(Client $client)
    {
        $this->elastic = $client;
    }

    /**
     * Begin a fluent query against elasticsearch indices.
     *
     * @param  string $index
     * @return mixed
     */
    public function index($index)
    {
        return new Query($this, $this->getQueryBuilder());
    }

    /**
     * Get the fluent query builder.
     *
     * @return \ONGR\ElasticsearchDSL\Search
     */
    public function getQueryBuilder()
    {
        return new Search;
    }

    /**
     * Handle dynamic method calls into the model.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->forwardCallTo($this->elastic, $method, $parameters);
    }
}
