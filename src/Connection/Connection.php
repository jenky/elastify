<?php

namespace Jenky\Elastify\Connection;

use Elasticsearch\Client;
use Illuminate\Support\Traits\ForwardsCalls;
use Jenky\Elastify\Builder\Query;
use Jenky\Elastify\Contracts\ConnectionInterface;
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
     * @param  string|null $type
     * @return \Jenky\Elastify\Builder\Query
     */
    public function index($index, $type = null)
    {
        return $this->query()->from($index, $type);
    }

    /**
     * Get a new query builder instance.
     *
     * @return \Jenky\Elastify\Builder\Query
     */
    public function query()
    {
        return new Query($this, $this->getQueryGrammar());
    }

    /**
     * Get the fluent query builder.
     *
     * @return \ONGR\ElasticsearchDSL\Search
     */
    public function getQueryGrammar()
    {
        return new Search;
    }

    /**
     * Get the elascticsearch client.
     *
     * @return \Elasticsearch\Client
     */
    public function getClient()
    {
        return $this->elastic;
    }

    /**
     * Handle dynamic method calls into the client.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->forwardCallTo(
            $this->getClient(), $method, $parameters
        );
    }
}
