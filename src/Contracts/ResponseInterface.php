<?php

namespace Jenky\Elastify\Contracts;

interface ResponseInterface
{
    /**
     * Get took value.
     *
     * @return int
     */
    public function took(): int;

    /**
     * Get timed_out value.
     *
     * @return bool
     */
    public function timedOut(): bool;

    /**
     * Get _shards value.
     *
     * @return array
     */
    public function shards(): array;

    /**
     * Get "hits" values.
     *
     * @return \Illuminate\Support\Collection
     */
    public function hits();

    /**
     * Get the "total" documents value.
     *
     * @return int
     */
    public function total(): int;

    /**
     * Get the "aggregations" values.
     *
     * @param  string|null $key
     * @param  mixed $default
     * @return mixed
     */
    public function aggregations($key = null, $default = null);

    /**
     * Get the "suggest" values.
     *
     * @param  string|null $key
     * @param  mixed $default
     * @return mixed
     */
    public function suggest($key = null, $default = null);
}
