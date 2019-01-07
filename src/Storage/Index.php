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
     * The index aliases.
     *
     * @var string|array
     */
    protected $aliases;

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
        $aliases = (array) $this->aliases;

        if ($this->multipleIndices
        && ! in_array($this->name(), $aliases)) {
            $aliases[] = $this->name();
        }

        return $aliases;
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
    public function exists()
    {
        if (is_null(static::$exists)) {
            static::$exists = $this->getConnection()
                ->indices()
                ->exists(['index' => $this->getIndex()]);
        }

        return static::$exists;
    }

    /**
     * Create the index.
     *
     * @return void
     */
    public function create()
    {
        $this->getConnection()
            ->indices()
            ->create([
                'index' => $this->getIndex(),
                'body' => array_filter($this->getConfiguration()),
            ]);
    }

    /**
     * Delete the index.
     *
     * @return void
     */
    public function delete()
    {
        $this->getConnection()
            ->indices()
            ->delete(['index' => $this->getIndex()]);
    }

    /**
     * Update index configuration.
     *
     * @param  array $config
     * @return void
     */
    public function update(array $config)
    {
        $data = Arr::only($config, ['settings', 'mappings']);

        if (! empty($data['settings'])) {
            $this->getConnection()->indices()->putSettings([
                'index' => $this->getIndex(),
                'body' => [
                    'settings' => $data['settings'],
                ],
            ]);
        }

        if (! empty($data['mappings'])) {
            $this->getConnection()->indices()->putMapping([
                'index' => $this->getIndex(),
                'type' => $this->getType(),
                'body' => $data['mappings'],
            ]);
        }
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
