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

        return $this->index;
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
     * Create new model instance.
     *
     * @return void
     */
    public static function make()
    {
        return new static(...func_get_args());
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
                'body' => array_filter($this->configuaration()),
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
        if (in_array($method, ['create', 'update', 'delete', 'flush', 'exists'])) {
            return $this->{$method}(...$parameters);
        }

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
        return (new static(...func_get_args()))->{$method}(...$parameters);
    }
}
