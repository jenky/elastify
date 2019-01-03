<?php

namespace Jenky\LaravelElasticsearch\Storage;

use Closure;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use ONGR\ElasticsearchDSL\BuilderInterface;
use ONGR\ElasticsearchDSL\Highlight\Highlight;
use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery;
use ONGR\ElasticsearchDSL\Query\FullText\CommonTermsQuery;
use ONGR\ElasticsearchDSL\Query\FullText\MatchQuery;
use ONGR\ElasticsearchDSL\Query\FullText\MultiMatchQuery;
use ONGR\ElasticsearchDSL\Query\FullText\QueryStringQuery;
use ONGR\ElasticsearchDSL\Query\FullText\SimpleQueryStringQuery;
use ONGR\ElasticsearchDSL\Query\Geo\GeoBoundingBoxQuery;
use ONGR\ElasticsearchDSL\Query\Geo\GeoDistanceQuery;
use ONGR\ElasticsearchDSL\Query\Geo\GeoDistanceRangeQuery;
use ONGR\ElasticsearchDSL\Query\Geo\GeoPolygonQuery;
use ONGR\ElasticsearchDSL\Query\Geo\GeoShapeQuery;
use ONGR\ElasticsearchDSL\Query\MatchAllQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\ExistsQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\FuzzyQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\IdsQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\PrefixQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\TermsQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\WildcardQuery;
use ONGR\ElasticsearchDSL\Search;
use ONGR\ElasticsearchDSL\Sort\FieldSort;

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
     * Query bool state.
     *
     * @var string
     */
    protected $boolState = BoolQuery::MUST;

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
     * Set the "from" value of the query.
     *
     * @param  int  $value
     * @return $this
     */
    public function from(int $offset)
    {
        $this->query->setFrom($offset);

        return $this;
    }

    /**
     * Alias to set the "from" value of the query.
     *
     * @param  int  $value
     * @return $this
     */
    public function skip(int $offset)
    {
        return $this->from($offset);
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
     * Alias to set sort value of the query.
     *
     * @param  string|array $fields
     * @param  string|null $order
     * @param  array $parameters
     * @return $this
     */
    public function orderBy($fields, $order = null, array $parameters = [])
    {
        return $this->sortBy($fields, $order, $parameters);
    }

    /**
     * Set the query sort values values.
     *
     * @param  string|array $fields
     * @param  string|null $order
     * @param  array $parameters
     * @return $this
     */
    public function sortBy($fields, $order = null, array $parameters = [])
    {
        $fields = is_array($fields) ? $fields : [$fields];

        foreach ($fields as $field) {
            $this->query->addSort(new FieldSort($field, $order, $parameters));
        }

        return $this;
    }

    /**
     * Switch to a should statement.
     *
     * @return $this
     */
    public function should()
    {
        $this->boolState = BoolQuery::SHOULD;

        return $this;
    }

    /**
     * Switch to a must statement.
     *
     * @return $this
     */
    public function must()
    {
        $this->boolState = BoolQuery::MUST;

        return $this;
    }

    /**
     * Switch to a must not statement.
     *
     * @return $this
     */
    public function mustNot()
    {
        $this->boolState = BoolQuery::MUST_NOT;

        return $this;
    }

    /**
     * Switch to a filter query.
     */
    public function filter()
    {
        $this->boolState = BoolQuery::FILTER;

        return $this;
    }

    /**
     * Add an terms query.
     *
     * @param  string $field
     * @param  array $terms
     * @param  array $attributes
     * @return $this
     */
    public function terms($field, array $terms, array $attributes = [])
    {
        $this->append(new TermsQuery($field, $terms, $attributes));

        return $this;
    }

    /**
     * Add an exists query.
     *
     * @param  string|array $fields
     * @return $this
     */
    public function exists($fields)
    {
        $fields = is_array($fields) ? $fields : [$fields];

        foreach ($fields as $field) {
            $query = new ExistsQuery($field);

            $this->append($query);
        }

        return $this;
    }

    /**
     * Add a wildcard query.
     *
     * @param  string $field
     * @param  string $value
     * @param  float|null $boost
     * @return $this
     */
    public function wildcard($field, $value, $boost = 1.0)
    {
        $this->append(new WildcardQuery($field, $value, ['boost' => $boost]));

        return $this;
    }

    /**
     * Add a boost query.
     *
     * @param  float|null $boost
     * @return $this
     */
    public function matchAll($boost = 1.0)
    {
        $this->append(new MatchAllQuery(['boost' => $boost]));

        return $this;
    }

    /**
     * Add a match query.
     *
     * @param  string $field
     * @param  string $term
     * @param  array  $attributes
     * @return $this
     */
    public function match($field, $term, array $attributes = [])
    {
        $this->append(new MatchQuery($field, $term, $attributes));

        return $this;
    }

    /**
     * Add a multi match query.
     *
     * @param  array $fields
     * @param  string $term
     * @param  array $attributes
     * @return $this
     */
    public function multiMatch(array $fields, $term, array $attributes = [])
    {
        $this->append(new MultiMatchQuery($fields, $term, $attributes));

        return $this;
    }

    /**
     * Add a geo bounding box query.
     *
     * @param  string $field
     * @param  array $values
     * @param  array $parameters
     * @return $this
     */
    public function geoBoundingBox($field, $values, array $parameters = [])
    {
        $this->append(new GeoBoundingBoxQuery($field, $values, $parameters));

        return $this;
    }

    /**
     * Add a geo distance query.
     *
     * @param  string $field
     * @param  string $distance
     * @param  mixed $location
     * @param  array $attributes
     * @return $this
     */
    public function geoDistance($field, $distance, $location, array $attributes = [])
    {
        $this->append(new GeoDistanceQuery($field, $distance, $location, $attributes));

        return $this;
    }

    /**
     * Add a geo distance range query.
     *
     * @param  string $field
     * @param  mixed $from
     * @param  mixed $to
     * @param  mixed $location
     * @param  array $attributes
     * @return $this
     */
    public function geoDistanceRange($field, $from, $to, array $location, array $attributes = [])
    {
        $range = compact('from', 'to');

        $this->append(new GeoDistanceRangeQuery($field, $range, $location, $attributes));

        return $this;
    }

    /**
     * Add a geo polygon query.
     *
     * @param  string $field
     * @param  array $points
     * @param  array $attributes
     * @return $this
     */
    public function geoPolygon($field, array $points = [], array $attributes = [])
    {
        $query = new GeoPolygonQuery($field, $points, $attributes);

        $this->append($query);

        return $this;
    }

    /**
     * Add a geo shape query.
     *
     * @param  string $field
     * @param  string $type
     * @param  array $coordinates
     * @param  array $attributes
     * @return $this
     */
    public function geoShape($field, $type, array $coordinates = [], array $attributes = [])
    {
        $query = new GeoShapeQuery();

        $query->addShape($field, $type, $coordinates, $attributes);

        $this->append($query);

        return $this;
    }

    /**
     * Add a prefix query.
     *
     * @param  string $field
     * @param  string $term
     * @param  array $attributes
     * @return $this
     */
    public function prefix($field, $term, array $attributes = [])
    {
        $this->append(new PrefixQuery($field, $term, $attributes));

        return $this;
    }

    /**
     * Add a query string query.
     *
     * @param  string $query
     * @param  array $attributes
     * @return $this
     */
    public function queryString($query, array $attributes = [])
    {
        $this->append(new QueryStringQuery($query, $attributes));

        return $this;
    }

    /**
     * Add a simple query string query.
     *
     * @param string $query
     * @param array $attributes
     * @return $this
     */
    public function simpleQueryString($query, array $attributes = [])
    {
        $this->append(new SimpleQueryStringQuery($query, $attributes));

        return $this;
    }

    /**
     * Add a highlight to result.
     *
     * @param  array $fields
     * @param  array $parameters
     * @param  string $preTag
     * @param  string $postTag
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/search-request-highlighting.html
     * @return $this
     */
    public function highlight($fields = ['_all' => []], $parameters = [], $preTag = '<mark>', $postTag = '</mark>')
    {
        $highlight = new Highlight();
        $highlight->setTags([$preTag], [$postTag]);

        foreach ($fields as $field => $fieldParams) {
            $highlight->addField($field, $fieldParams);
        }

        if ($parameters) {
            $highlight->setParameters($parameters);
        }

        $this->query->addHighlight($highlight);

        return $this;
    }

    /**
     * Add a range query.
     *
     * @param  string $field
     * @param  array  $attributes
     * @return $this
     */
    public function range($field, array $attributes = [])
    {
        $this->append(new RangeQuery($field, $attributes));

        return $this;
    }

    /**
     * Add a regexp query.
     *
     * @param  string $field
     * @param  array  $attributes
     * @return $this
     */
    public function regexp($field, $regex, array $attributes = [])
    {
        $this->append(new RegexpQuery($field, $regex, $attributes));

        return $this;
    }

    /**
     * Add a common term query.
     *
     * @param  string $field
     * @param  string $term
     * @param  array $attributes
     * @return $this
     */
    public function commonTerm($field, $term, array $attributes = [])
    {
        $this->append(new CommonTermsQuery($field, $term, $attributes));

        return $this;
    }

    /**
     * Add a fuzzy query.
     *
     * @param  string $field
     * @param  string $term
     * @param  array $attributes
     * @return $this
     */
    public function fuzzy($field, $term, array $attributes = [])
    {
        $this->append(new FuzzyQuery($field, $term, $attributes));

        return $this;
    }

    /**
     * Add a nested query.
     *
     * @param  $field
     * @param  \Closure $closure
     * @param  string $scoreMode
     * @return $this
     */
    public function nested($field, Closure $closure, $scoreMode = 'avg')
    {
        // $builder = new self($this->connection, new $this->query());

        // $closure($builder);

        // $nestedQuery = $builder->query->getQueries();

        // $query = new NestedQuery($field, $nestedQuery, ['score_mode' => $score_mode]);

        // $this->append($query);

        return $this;
    }

    /**
     * Add aggregation.
     *
     * @param  \Closure $closure
     * @return $this
     */
    public function aggregate(Closure $closure)
    {
        // $builder = new AggregationBuilder($this->query);

        // $closure($builder);

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

    /**
     * Return the DSL query.
     *
     * @return array
     */
    public function toDSL()
    {
        return $this->query->toArray();
    }

    /**
     * Return the boolean query state.
     *
     * @return string
     */
    public function getBoolState()
    {
        return $this->boolState;
    }

    /**
     * Append a query.
     *
     * @param  \ONGR\ElasticsearchDSL\BuilderInterface $query
     * @return $this
     */
    public function append(BuilderInterface $query)
    {
        $this->query->addQuery($query, $this->getBoolState());

        return $this;
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
