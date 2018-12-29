<?php

namespace Jenky\LaravelElasticsearch\Storage;

use Illuminate\Pagination\LengthAwarePaginator;

class Response extends LengthAwarePaginator
{
    /**
     * The index instance.
     *
     * @var \Jenky\LaravelElasticsearch\Storage\Index
     */
    protected $index;

    /**
     * @var array
     */
    protected $raw = [];

    /**
     * @var \Illuminate\Support\Collection
     */
    protected $hits;

    /**
     * Create elasticsearch response instance.
     *
     * @param  array $raw
     * @param  int $perPage
     * @param  int $currentPage
     * @param  array $options
     * @return void
     */
    public function __construct(array $raw = [], int $perPage = 10, $page, array $options = [])
    {
        $this->raw = $raw;

        return parent::__construct(
            $raw['hits']['hits'] ?? [],
            $raw['hits']['total'] ?? 0,
            $perPage,
            $page,
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
     * Get the raw response.
     *
     * @return array
     */
    public function raw()
    {
        return $this->raw;
    }
}
