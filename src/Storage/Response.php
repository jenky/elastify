<?php

namespace Jenky\LaravelElasticsearch\Storage;

use Illuminate\Pagination\LengthAwarePaginator;
use Jenky\LaravelElasticsearch\Contracts\Paginator;

class Response extends LengthAwarePaginator implements Paginator
{
    /**
     * @var array
     */
    protected $raw = [];

    /**
     * Create elasticsearch response instance.
     *
     * @param  mixed $items
     * @param  int $perPage
     * @param  int $currentPage
     * @param  array $options
     * @return void
     */
    public function __construct($items, int $perPage = 10, $currentPage, array $options = [])
    {
        $this->raw = $items;

        return parent::__construct(
            $items['hits']['hits'] ?? [],
            $items['hits']['total'] ?? 0,
            $perPage,
            $currentPage,
            $options
        );
    }

    /**
     * Create a new results instance.
     *
     * @param  mixed  ...$arguments
     * @return static
     */
    public static function make(...$arguments)
    {
        return new static(...$arguments);
    }

    /**
     * Get took value.
     *
     * @return int
     */
    public function took()
    {
        return $this->raw['took'];
    }

    /**
     * Get timed_out value.
     *
     * @return bool
     */
    public function timedOut()
    {
        return $this->raw['timed_out'];
    }

    /**
     * Get _shards value.
     *
     * @return array
     */
    public function shards()
    {
        return $this->raw['_shards'];
    }

    /**
     * Get "hits" values.
     *
     * @return \Illuminate\Support\Collection
     */
    public function hits()
    {
        return $this->items;
    }

    /**
     * Get the raw response.
     *
     * @return array
     */
    public function raw()
    {
        return $this->raw;
    }
}
