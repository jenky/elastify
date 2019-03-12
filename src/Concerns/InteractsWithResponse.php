<?php

namespace Jenky\Elastify\Concerns;

use Illuminate\Support\Arr;

trait InteractsWithResponse
{
    /**
     * @var array
     */
    protected $response = [];

    /**
     * Get took value.
     *
     * @return int
     */
    public function took()
    {
        return $this->response['took'];
    }

    /**
     * Get timed_out value.
     *
     * @return bool
     */
    public function timedOut()
    {
        return $this->response['timed_out'];
    }

    /**
     * Get _shards value.
     *
     * @return array
     */
    public function shards()
    {
        return $this->response['_shards'];
    }

    /**
     * Get "hits" values.
     *
     * @return \Illuminate\Support\Collection
     */
    public function hits()
    {
        return $this->response['hits']['hits'] ?? [];
    }

    /**
     * Get the "total" documents value.
     *
     * @return int
     */
    public function total()
    {
        return $this->response['hits']['total'] ?? 0;
    }

    /**
     * Get the response response.
     *
     * @return array
     */
    public function raw()
    {
        return $this->response;
    }

    /**
     * Get the "aggregations" values.
     *
     * @param  string|null $key
     * @param  mixed $default
     * @return mixed
     */
    public function aggregations($key = null, $default = null)
    {
        $aggregations = $this->response['aggregations'] ?? [];

        return $key ? Arr::get($aggregations, $key, $default) : $aggregations;
    }

    /**
     * Get the "suggest" values.
     *
     * @param  string|null $key
     * @param  mixed $default
     * @return mixed
     */
    public function suggest($key = null, $default = null)
    {
        $suggest = $this->response['suggest'] ?? [];

        return $key ? Arr::get($suggest, $key, $default) : $suggest;
    }
}
