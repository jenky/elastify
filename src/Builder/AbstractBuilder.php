<?php

namespace Jenky\Elastify\Builder;

use Jenky\Elastify\Contracts\ConnectionInterface;
use ONGR\ElasticsearchDSL\Search;

abstract class AbstractBuilder
{
    /**
     * @var \Jenky\Elastify\Contracts\ConnectionInterface
     */
    protected $connection;

    /**
     * The DSL query builder.
     *
     * @var \ONGR\ElasticsearchDSL\Search
     */
    protected $query;

    /**
     * Create a new query builder instance.
     *
     * @param  \Jenky\Elastify\Contracts\ConnectionInterface $connection
     * @param  \ONGR\ElasticsearchDSL\Search $query
     * @return void
     */
    public function __construct(ConnectionInterface $connection, Search $query = null)
    {
        $this->connection = $connection;
        $this->query = $query ?: $connection->getQueryGrammar();
    }

    /**
     * Get the ealsticsearch connection instance.
     *
     * @return \Jenky\Elastify\Contracts\ConnectionInterface
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Get the underlying query builder instance.
     *
     * @return \ONGR\ElasticsearchDSL\Search  $query
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Set the underlying query builder instance.
     *
     * @param  \ONGR\ElasticsearchDSL\Search  $query
     * @return $this
     */
    public function setQuery(Search $query)
    {
        $this->query = $query;

        return $this;
    }

    /**
     * Return the DSL query.
     *
     * @return array
     */
    public function toDSL()
    {
        return $this->query->toArray();
    }
}
