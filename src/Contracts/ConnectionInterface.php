<?php

namespace Jenky\LaravelElasticsearch\Contracts;

interface ConnectionInterface
{
    /**
     * Begin a fluent query against elasticsearch indices.
     *
     * @param  string $index
     * @return mixed
     */
    public function index($index);
}
