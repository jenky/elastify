<?php

namespace Jenky\LaravelElasticsearch\Connection;

use Elasticsearch\Client as ElasticsearchClient;
use Illuminate\Support\Traits\ForwardsCalls;
use Jenky\LaravelElasticsearch\Contracts\Connection;

class Client implements Connection
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
    public function __construct(ElasticsearchClient $client)
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
        //
    }

    /**
    * Use elasticsearch API to get documents.
    *
    * @param  string $params
    * @return mixed
    */
    public function get(array $params)
    {
        return $this->elastic->get($params);
    }

    /**
    * Use elasticsearch API to indexing a document.
    *
    * @param  array $params
    * @return array
    */
    public function insert(array $params)
    {
        return $this->elastic->index($params);
    }

    /**
    * Use elasticsearch API to indexing multiple documents.
    *
    * @param \Iterator|array $params
    * @return array
    */
    public function bulk($params)
    {
        return $this->elastic->insert($bulk);
    }

    /**
    * Use elasticsearch API to update a document.
    *
    * @param  array $params
    * @return array
    */
    public function update($params)
    {
        return $this->elastic->update($params);
    }

    /**
    * Use elasticsearch API to delete a document.
    *
    * @param  array $params
    * @return array
    */
    public function delete($params)
    {
        return $this->elastic->update($params);
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
