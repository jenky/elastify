<?php

namespace Jenky\LaravelElasticsearch\Indices;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Jenky\LaravelElasticsearch\Connection\HasConnection;
use Jenky\LaravelElasticsearch\Response;
use ONGR\ElasticsearchDSL\Search;

abstract class Index
{
    use HasConnection;

    /**
     * The connection name for the index.
     *
     * @var string
     */
    protected $connection;

    /**
     * The index name.
     *
     * @var string
     */
    protected $name;

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
     * Get the index name.
     *
     * @return string
     */
    private function name(): string
    {
        if (! isset($this->name)) {
            $className = str_replace('Index', '', class_basename($this));

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
    public function getName(): string
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
        return $this->name().'*';
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

        if ($this->multipleIndices) {
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
                ->exists(['index' => $this->getName()]);
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
                'index' => $this->getName(),
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
            ->delete(['index' => $this->getName()]);
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
                'index' => $this->getName(),
                'body' => [
                    'settings' => $data['settings'],
                ],
            ]);
        }

        if (! empty($data['mappings'])) {
            $this->getConnection()->indices()->putMapping([
                'index' => $this->getName(),
                'type' => $this->getType(),
                'body' => $data['mappings'],
            ]);
        }
    }

    public function search(Search $search): Response
    {
        return $this->searchRaw($search->toArray());
    }

    public function searchRaw(array $body): Response
    {
        return Response::make(
            $this->getConnection()->search([
                'index' => $this->searchableAs(),
                'body' => $body,
            ])
        );
    }
}
