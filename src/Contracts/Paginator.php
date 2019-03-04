<?php

namespace Jenky\LaravelElasticsearch\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface Paginator extends LengthAwarePaginator
{
    /**
     * Get took value.
     *
     * @return int
     */
    public function took();

    /**
     * Get timed_out value.
     *
     * @return bool
     */
    public function timedOut();

    /**
     * Get _shards value.
     *
     * @return array
     */
    public function shards();

    /**
     * Get "hits" values.
     *
     * @return \Illuminate\Support\Collection
     */
    public function hits();
}
