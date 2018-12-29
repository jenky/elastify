<?php

namespace Jenky\LaravelElasticsearch\Storage;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use ONGR\ElasticsearchDSL\Query\TermLevel\IdsQuery;
use ONGR\ElasticsearchDSL\Search;

class Builder
{
    /**
     * The base query builder instance.
     *
     * @var \ONGR\ElasticsearchDSL\Search
     */
    protected $query;

    /**
     * The model being queried.
     *
     * @var \Jenky\LaravelElasticsearch\Storage\Index
     */
    protected $index;

    /**
     * Create a new Eloquent query builder instance.
     *
     * @param  \ONGR\ElasticsearchDSL\Search  $query
     * @return void
     */
    public function __construct(Search $query)
    {
        $this->query = $query;
    }

    /**
     * Get the underlying query builder instance.
     *
     * @return \ONGR\ElasticsearchDSL\Search  $query
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Set the underlying query builder instance.
     *
     * @param  \ONGR\ElasticsearchDSL\Search  $query
     * @return $this
     */
    public function setQuery($query)
    {
        $this->query = $query;

        return $this;
    }

    /**
     * Get the index instance being queried.
     *
     * @return \Jenky\LaravelElasticsearch\Storage\Index|static
     */
    public function getIndex()
    {
        return $this->index;
    }

    /**
     * Set a index instance for the index being queried.
     *
     * @param  \Jenky\LaravelElasticsearch\Storage\Index  $index
     * @return $this
     */
    public function setIndex(Index $index)
    {
        $this->index = $index;

        return $this;
    }

    public function find($id, $type = null)
    {
        if (is_array($id) || $id instanceof Arrayable) {
            return $this->findMany($id, $type);
        }

        return $this->whereKey($id, $type)->first();
    }

    public function findMany($ids, $type = null)
    {
        if (! empty($ids)) {
            $this->whereKey($ids, $type);
        }

        return $this->get();
    }

    public function whereKey($id, $type = null)
    {
        if ($id instanceof Arrayable) {
            $id = $id->toArray();
        }

        $ids = (array) $id;

        $this->query->addQuery(new IdsQuery(
            $ids,
            array_filter(compact('type'))
        ));

        return $this;
    }

    /**
     * Alias to set the "from" value of the query.
     *
     * @param  int  $value
     * @return $this
     */
    public function from(int $offset)
    {
        return $this->skip($offset);
    }

    /**
     * Set the "from" value of the query.
     *
     * @param  int  $value
     * @return $this
     */
    public function skip(int $offset)
    {
        $this->query->setFrom($offset);

        return $this;
    }

    /**
     * Alias to set the "limit" value of the query.
     *
     * @param  int  $value
     * @return $this
     */
    public function take(int $limit)
    {
        return $this->limit($limit);
    }

    /**
     * Set the "size" value of the query.
     *
     * @param  int  $value
     * @return $this
     */
    public function limit(int $limit)
    {
        $this->query->setSize($limit);

        return $this;
    }

    /**
     * Set the limit and offset for a given page.
     *
     * @param  int  $page
     * @param  int  $perPage
     * @return $this|static
     */
    public function forPage($page, $perPage = 10)
    {
        return $this->skip(($page - 1) * $perPage)->take($perPage);
    }

    public function first()
    {
        return $this->get()->first();
    }

    public function get($perPage = null, $pageName = 'page', $page = null): Response
    {
        $page = $page ?: Paginator::resolveCurrentPage($pageName);

        $perPage = $perPage ?: $this->index->getPerPage();

        $results = $this->search(
            $this->forPage($page, $perPage)->getQuery()
                ->toArray()
        );

        if ($documentClass = $this->getIndex()->getDocument()) {
            $results['hits']['hits'] = Collection::make($results['hits']['hits'] ?? [])
                ->mapInto($documentClass);
        }

        return Response::make($results, $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => $pageName,
        ]);
    }

    public function search(array $params = [])
    {
        return $this->getIndex()->getConnection()->search([
            'index' => $this->getIndex()->searchableAs(),
            'body' => $params,
        ]);
    }
}
