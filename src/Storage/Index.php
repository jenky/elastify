<?php

namespace Jenky\LaravelElasticsearch\Storage;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\ForwardsCalls;
use Jenky\LaravelElasticsearch\Connection\HasConnection;
use ONGR\ElasticsearchDSL\Search;

abstract class Index
{
    use ForwardsCalls, HasConnection;

    /**
     * The index name.
     *
     * @var string
     */
    protected $index;

    /**
     * The index type.
     *
     * @var string
     */
    protected $type = '_doc';

    /**
     * The number of documents to return.
     *
     * @var int
     */
    protected $perPage = 10;

    /**
     * Indicates if uses multiple indices.
     *
     * @var bool
     */
    public $multipleIndices = true;

    /**
     * Indicates if the index exists.
     *
     * @var bool
     */
    protected static $exists;

    /**
     * The document class.
     *
     * @var \Jenky\Elastichsearch\Storage\Document
     */
    protected $document = Document::class;

    // protected $document;

    /**
     * Get the document class.
     *
     * @return string
     */
    public function getDocument()
    {
        return $this->document;
    }

    /**
     * Set a index instance for the index being queried.
     *
     * @param  string $document
     * @return $this
     */
    public function setDocument(string $document)
    {
        $this->document = $document;

        return $this;
    }

    /**
     * Get the index name.
     *
     * @return string
     */
    protected function name(): string
    {
        if (! isset($this->index)) {
            $className = str_replace('Index', '', class_basename($this));

            return str_replace(
                '\\',
                '',
                Str::snake(Str::plural($className))
            );
        }

        $return = $this->index;
    }

    /**
     * Get the index name.
     *
     * @return string
     */
    public function getIndex(): string
    {
        $name = $this->name();

        return $this->multipleIndices ? $name.$this->suffix() : $name;
    }

    /**
     * Get the index type.
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type ?: '_doc';
    }

    /**
     * Get the index name suffix if multiple indices is true.
     *
     * @return string
     */
    protected function suffix(): string
    {
        return '-'.date('Y.m.d');
    }

    /**
     * Get the index name for search. It could be index alias or wildcard match.
     *
     * @return string
     */
    public function searchableAs(): string
    {
        return $this->multipleIndices ? $this->name().'-*' : $this->name();
    }

    /**
     * Get the index settings.
     *
     * @return array
     */
    public function settings(): array
    {
        return [];
    }

    /**
     * Get the index mapping.
     *
     * @return array
     */
    public function mapping(): array
    {
        return [
            $this->getType() => [
                '_source' => [
                    'enabled' => true,
                ],
                'properties' => $this->properties(),
            ],
        ];
    }

    /**
     * Get all mapping properties.
     *
     * @return array
     */
    public function properties(): array
    {
        return [];
    }

    /**
     * Get index aliases.
     *
     * @return array
     */
    public function aliases(): array
    {
        return [
            '.'.$this->name() => new \stdClass,
        ];
    }

    /**
     * Get the index configuration.
     *
     * @return array
     */
    public function configuaration(): array
    {
        return [
            'settings' => $this->settings(),
            'mappings' => $this->mapping(),
            'aliases' => $this->aliases(),
        ];
    }

    /**
     * Check if whether index is exists.
     *
     * @return bool
     */
    public static function exists()
    {
        $instance = new static(...func_get_args());

        if (is_null(static::$exists)) {
            static::$exists = $instance->getConnection()
                ->indices()
                ->exists(['index' => $instance->getIndex()]);
        }

        return static::$exists;
    }

    /**
     * Create the index.
     *
     * @return void
     */
    public static function create()
    {
        $instance = new static(...func_get_args());

        $instance->getConnection()
            ->indices()
            ->create([
                'index' => $instance->getIndex(),
                'body' => array_filter($instance->configuaration()),
            ]);
    }

    /**
     * Delete the index.
     *
     * @return void
     */
    public static function delete()
    {
        $instance = new static(...func_get_args());

        $instance->getConnection()
            ->indices()
            ->delete(['index' => $instance->getIndex()]);
    }

    /**
     * Update index configuration.
     *
     * @param  array $config
     * @return void
     */
    public static function update(array $config)
    {
        $instance = new static(...func_get_args());
        $data = Arr::only($config, ['settings', 'mappings']);

        if (! empty($data['settings'])) {
            $instance->getConnection()->indices()->putSettings([
                'index' => $instance->getIndex(),
                'body' => [
                    'settings' => $data['settings'],
                ],
            ]);
        }

        if (! empty($data['mappings'])) {
            $instance->getConnection()->indices()->putMapping([
                'index' => $instance->getIndex(),
                'type' => $instance->getType(),
                'body' => $data['mappings'],
            ]);
        }
    }

    /**
     * Flush the index.
     *
     * @return void
     */
    public static function flush()
    {
        $instance = new static(...func_get_args());

        $instance->getConnection()
            ->indices()
            ->flush(['index' => $instance->getIndex()]);
    }

    /**
     * Begin querying the index.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function query()
    {
        return (new static(...func_get_args()))->newQuery();
    }

    /**
     * Get the number of models to return per page.
     *
     * @return int
     */
    public function getPerPage()
    {
        return $this->perPage;
    }

    /**
     * Set the number of models to return per page.
     *
     * @param  int  $perPage
     * @return $this
     */
    public function setPerPage($perPage)
    {
        $this->perPage = $perPage;

        return $this;
    }

    /**
     * Get a new query builder for the index.
     *
     * @return \Jenky\LaravelElasticsearch\Storage\Builder
     */
    public function newQuery(): Builder
    {
        return (new Builder($this->newBaseQuery()))
            ->setIndex($this);
    }

    /**
     * Create new base query.
     *
     * @return \ONGR\ElasticsearchDSL\Search
     */
    public function newBaseQuery(): Search
    {
        return new Search;
    }

    /**
     * Handle dynamic method calls into the model.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->forwardCallTo($this->newQuery(), $method, $parameters);
    }

    /**
     * Handle dynamic static method calls into the method.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public static function __callStatic($method, $parameters)
    {
        return (new static)->{$method}(...$parameters);
    }
}
