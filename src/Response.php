<?php

namespace Jenky\LaravelElasticsearch;

use ArrayAccess;
use Countable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Traits\ForwardsCalls;
use IteratorAggregate;
use JsonSerializable;

// TODO: use Paginator?
class Response implements Arrayable, ArrayAccess, Countable, IteratorAggregate, JsonSerializable, Jsonable
{
    use ForwardsCalls;

    /**
     * @var array
     */
    protected $raw = [];

    /**
     * @var \Illuminate\Support\Collection
     */
    protected $hits;

    /**
     * Create elasticsearch result instance.
     *
     * @param  array $raw
     * @return void
     */
    public function __construct(array $raw = [])
    {
        $this->raw = $raw;

        $this->hits = collect($raw['hits']['hits'] ?? []);
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
     * Get hits.
     *
     * @return \Illuminate\Support\Collection
     */
    public function hits()
    {
        return $this->hits;
    }

    /**
     * Get total hits.
     *
     * @return int
     */
    public function total()
    {
        return $this->raw['hits']['total'];
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            // 'current_page' => $this->currentPage(),
            'data' => $this->hits->toArray(),
            // 'first_page_url' => $this->url(1),
            // 'from' => $this->firstItem(),
            // 'last_page' => $this->lastPage(),
            // 'last_page_url' => $this->url($this->lastPage()),
            // 'next_page_url' => $this->nextPageUrl(),
            // 'path' => $this->path,
            // 'per_page' => $this->perPage(),
            // 'prev_page_url' => $this->previousPageUrl(),
            // 'to' => $this->lastItem(),
            'total' => $this->total(),
        ];
    }

    /**
     * Convert the object into something JSON serializable.
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * Convert the object to its JSON representation.
     *
     * @param  int  $options
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * Determine if the given item exists.
     *
     * @param  mixed  $key
     * @return bool
     */
    public function offsetExists($key)
    {
        return $this->hits->has($key);
    }

    /**
     * Get the item at the given offset.
     *
     * @param  mixed  $key
     * @return mixed
     */
    public function offsetGet($key)
    {
        return $this->hits->get($key);
    }

    /**
     * Set the item at the given offset.
     *
     * @param  mixed  $key
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet($key, $value)
    {
        $this->hits->put($key, $value);
    }

    /**
     * Unset the item at the given key.
     *
     * @param  mixed  $key
     * @return void
     */
    public function offsetUnset($key)
    {
        $this->hits->forget($key);
    }

    /**
     * Get an iterator for the items.
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return $this->hits->getIterator();
    }

    /**
     * Get the number of items for the current page.
     *
     * @return int
     */
    public function count()
    {
        return $this->hits->count();
    }

    /**
     * Make dynamic calls into the collection.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->forwardCallTo($this->hits(), $method, $parameters);
    }
}
