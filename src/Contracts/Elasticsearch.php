<?php

namespace Jenky\LaravelElasticsearch\Contracts;

use Jenky\LaravelElasticsearch\Elasticsearch\Response;
use ONGR\ElasticsearchDSL\Search;

interface Elasticsearch
{
    /**
     * Use Elasticsearch API to perform search.
     *
     * @param  \ONGR\ElasticsearchDSL\Search $search
     * @param  string $index
     * @return Jenky\LaravelElasticsearch\Elasticsearch\Response
     */
    public function search(Search $search, $index) : Response;
}
