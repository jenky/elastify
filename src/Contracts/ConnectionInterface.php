<?php

namespace Jenky\Elastify\Contracts;

interface ConnectionInterface
{
    /**
     * Begin a fluent query against elasticsearch indices.
     *
     * @param  string $index
     * @param  string|null $type
     * @return mixed
     */
    public function index($index, $type = null);
}
