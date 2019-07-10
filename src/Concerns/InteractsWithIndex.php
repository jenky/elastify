<?php

namespace Jenky\Elastify\Concerns;

use Illuminate\Support\Str;
use Illuminate\Support\Traits\ForwardsCalls;

trait InteractsWithIndex
{
    use ForwardsCalls;

    /**
     * Indicates if the index exists.
     *
     * @var bool
     */
    protected static $exists;

    protected function forwardsCallToIndices($method, $parameters)
    {
        return $this->forwardCallTo(
            $this->getConnection()->indices(),
            $method,
            $parameters
        );
    }

    /**
     * Check if whether index is exists.
     *
     * @param  string|null $index
     * @return bool
     */
    protected function exists($index = null)
    {
        if (is_null(static::$exists)) {
            static::$exists = $this->getConnection()
                ->indices()
                ->exists(['index' => $index ?: $this->getIndex()]);
        }

        return static::$exists;
    }

    /**
     * Create the index.
     *
     * @param  string|null $index
     * @return void
     */
    protected function create($index = null)
    {
        $this->getConnection()
            ->indices()
            ->create([
                'index' => $index ?: $this->getIndex(),
                'body' => array_filter($this->configuration()),
            ]);
    }

    /**
     * Delete the index.
     *
     * @param  string|null $index
     * @return void
     */
    protected function delete($index = null)
    {
        $this->getConnection()
            ->indices()
            ->delete(['index' => $index ?: $this->getIndex()]);
    }

    /**
     * Update index configuration.
     *
     * @param  array $config
     * @param  string|null $index
     * @return void
     */
    protected function update(array $config, $index = null)
    {
        $data = Arr::only($config, ['settings', 'mappings']);
        $index = $index ?: $this->getIndex();

        if (! empty($data['settings'])) {
            $this->getConnection()->indices()->putSettings([
                'index' => $index,
                'body' => [
                    'settings' => $data['settings'],
                ],
            ]);
        }

        if (! empty($data['mappings'])) {
            $this->getConnection()->indices()->putMapping([
                'index' => $index,
                'type' => $this->getType(),
                'body' => $data['mappings'],
            ]);
        }
    }

    /**
     * Flush the index.
     *
     * @param  string|null $index
     * @return void
     */
    protected function flush($index = null)
    {
        $this->getConnection()
            ->indices()
            ->flush(['index' => $index ?: $this->getIndex()]);
    }
}
