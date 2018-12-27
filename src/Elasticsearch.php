<?php

namespace Jenky\LaravelElasticsearch;

use Jenky\LaravelElasticsearch\Contracts\Elasticsearch as Contract;
use Jenky\LaravelElasticsearch\Elasticsearch\Response;
use ONGR\ElasticsearchDSL\Search;

class Elasticsearch implements Contract
{
    public function search(Search $search, $index) : Response
    {
        return Response::make(
            $this->elastic->search([
                'index' => $index,
                'body' => $search->toArray(),
            ])
        );
    }
}
