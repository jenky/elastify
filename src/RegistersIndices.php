<?php

namespace Jenky\Elastify;

trait RegistersIndices
{
    /**
     * Register the application's Elasticsearch indices.
     *
     * @return void
     */
    public function registerIndices()
    {
        foreach ($this->indices() as $key => $value) {
            // ES::policy($key, $value);
        }
    }

    /**
     * Get the indices defined on the provider.
     *
     * @return array
     */
    public function indices(): array
    {
        return property_exists($this, 'indices') ? (array) $this->indices : [];
    }
}
