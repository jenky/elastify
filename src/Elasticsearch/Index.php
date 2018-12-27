<?php

namespace Jenky\LaravelElasticsearch\Elasticsearch;

use Illuminate\Support\Str;

abstract class Index
{
    /**
     * The index name.
     *
     * @var string
     */
    protected $name;

    /**
     * The index type.
     *
     * @var string
     */
    protected $type = '_doc';

    /**
     * Indicates if uses multiple indices.
     *
     * @var bool
     */
    public $multipleIndices = true;

    /**
     * Get the index name.
     *
     * @return string
     */
    private function name() : string
    {
        if (! isset($this->name)) {
            $className = str_replace('Index', class_basename($this));

            return str_replace(
                '\\',
                '',
                Str::snake(Str::plural($className))
            );
        }

        $return = $this->name;
    }

    /**
     * Get the index name.
     *
     * @return string
     */
    public function getName() : string
    {
        $name = $this->name();

        return $this->multipleIndices ? $name.$this->getSuffix() : $name;
    }

    /**
     * Get the index name suffix if multiple indices is true.
     *
     * @return string
     */
    protected function getSuffix() : string
    {
        return '-'.date('Y.m.d');
    }

    /**
     * Get the index type.
     *
     * @return string
     */
    public function getType() : string
    {
        return $this->type ?: '_doc';
    }

    /**
     * Get the index settings.
     *
     * @return array
     */
    public function settings() : array
    {
        return [];
    }

    /**
     * Get the index mapping.
     *
     * @return array
     */
    public function mapping() : array
    {
        return [];
    }

    /**
     * Get index aliases.
     *
     * @return array
     */
    public function aliases() : array
    {
        $aliases = [];

        if ($this->multipleIndices) {
            $aliases[] = $this->name();
        }

        return $aliases;
    }
}
