<?php

namespace Jenky\LaravelElasticsearch\Builder;

use Closure;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\ForwardsCalls;
use Illuminate\Support\Traits\Macroable;
use InvalidArgumentException;
use Jenky\LaravelElasticsearch\Contracts\ConnectionInterface;
use Jenky\LaravelElasticsearch\Storage\Response;
use ONGR\ElasticsearchDSL\BuilderInterface;
use ONGR\ElasticsearchDSL\Highlight\Highlight;
use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery;
use ONGR\ElasticsearchDSL\Query\FullText\CommonTermsQuery;
use ONGR\ElasticsearchDSL\Query\FullText\MatchPhraseQuery;
use ONGR\ElasticsearchDSL\Query\FullText\MatchQuery;
use ONGR\ElasticsearchDSL\Query\FullText\MultiMatchQuery;
use ONGR\ElasticsearchDSL\Query\FullText\QueryStringQuery;
use ONGR\ElasticsearchDSL\Query\FullText\SimpleQueryStringQuery;
use ONGR\ElasticsearchDSL\Query\Geo\GeoBoundingBoxQuery;
use ONGR\ElasticsearchDSL\Query\Geo\GeoDistanceQuery;
use ONGR\ElasticsearchDSL\Query\Geo\GeoPolygonQuery;
use ONGR\ElasticsearchDSL\Query\Geo\GeoShapeQuery;
use ONGR\ElasticsearchDSL\Query\Joining\NestedQuery;
use ONGR\ElasticsearchDSL\Query\MatchAllQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\ExistsQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\FuzzyQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\IdsQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\PrefixQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\RangeQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\RegexpQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\TermQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\TermsQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\WildcardQuery;
use ONGR\ElasticsearchDSL\Search;
use ONGR\ElasticsearchDSL\Sort\FieldSort;
use ReflectionClass;

class Query
{
    use ForwardsCalls, Macroable {
        __call as macroCall;
    }

    /**
     * @var \Jenky\LaravelElasticsearch\Contracts\ConnectionInterface
     */
    protected $connection;

    /**
     * The indices/aliases which the query is targeting.
     *
     * @var string
     */
    protected $from;

    /**
     * The index default type.
     *
     * @var string
     */
    protected $type;

    /**
     * The DSL query builder.
     *
     * @var \ONGR\ElasticsearchDSL\Search
     */
    protected $query;

    /**
     * Query bool state.
     *
     * @var string
     */
    protected $boolQuery = BoolQuery::MUST;

    /**
     * All of the available clause operators.
     *
     * @var array
     */
    public $operators = [
        '=', '<', '>', '<=', '>=', '<>', '!=', '<=>',
        'lt', 'gt', 'lte', 'gte',
        // 'like', 'like binary', 'not like', 'ilike',
        // '&', '|', '^', '<<', '>>',
        // 'rlike', 'regexp', 'not regexp',
        // '~', '~*', '!~', '!~*', 'similar to',
        // 'not similar to', 'not ilike', '~~*', '!~~*',
    ];

    /**
     * Create a new query builder instance.
     *
     * @param  \Jenky\LaravelElasticsearch\Contracts\ConnectionInterface $connection
     * @param  \ONGR\ElasticsearchDSL\Search $query
     * @return void
     */
    public function __construct(ConnectionInterface $connection, Search $query)
    {
        $this->connection = $connection;
        $this->query = $query;
    }

    /**
     * Get the ealsticsearch connection instance.
     *
     * @return \Jenky\LaravelElasticsearch\Contracts\ConnectionInterface
     */
    public function getConnection()
    {
        return $this->connection;
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
    public function setQuery(Search $query)
    {
        $this->query = $query;

        return $this;
    }

    /**
     * Set the indices/aliases which the query is targeting.
     *
     * @param  string $from
     * @param  string|null $type
     * @return $this
     */
    public function from($from, $type = null)
    {
        $this->from = $from;

        if ($type) {
            $this->type($type);
        }

        return $this;
    }

    /**
     * Set the index type.
     *
     * @param  string $type
     * @return $this
     */
    public function type($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Check if whether indices/aliases with/without type are exists.
     *
     * @return bool
     */
    public function indexExists()
    {
        return $this->getConnection()->indices()->exists(array_filter([
            'index' => $this->from,
            'type' => $this->type,
        ]));
    }

    /**
     * Create a new index.
     *
     * @param  array $params
     * @return void
     */
    public function create(array $params)
    {
        $data = [
            'index' => $this->index,
            'body' => $params,
        ];

        $this->getConnection()->indices()->create($data);
    }

    /**
     * Create a new index if not exists.
     *
     * @param  array $params
     * @return void
     */
    public function createIfNotExists(array $params)
    {
        if (! $this->indexExists()) {
            $this->create($params);
        }
    }

    /**
     * Delete an index.
     *
     * @return void
     */
    public function drop()
    {
        $this->getConnection()->indices()->delete([
            'index' => $this->index,
        ]);
    }

    /**
     * Delete an existing index.
     *
     * @return void
     */
    public function dropIfExists()
    {
        if ($this->indexExists()) {
            $this->drop();
        }
    }

    /**
     * Perform the search by using search API.
     *
     * @param  array $params
     * @return array
     */
    public function search(array $params = [])
    {
        return $this->getConnection()->search([
            'index' => $this->from,
            'body' => $params,
        ]);
    }

    /**
     * Add a basic where clause to the query.
     *
     * @param  string|array|\Closure $field
     * @param  mixed $operator
     * @param  mixed $value
     * @param  string $boolQuery
     * @return $this
     */
    public function where($field, $operator = null, $value = null, $boolQuery = BoolQuery::MUST)
    {
        // Here we will make some assumptions about the operator. If only 2 values are
        // passed to the method, we will assume that the operator is an equals sign
        // and keep going. Otherwise, we'll require the operator to be passed in.
        [$value, $operator] = $this->prepareValueAndOperator(
            $value,
            $operator,
            func_num_args() === 2
        );

        // If the field is actually a Closure instance, we will assume the developer
        // wants to begin a nested where statement which is wrapped in parenthesis.
        // We'll add that Closure to the query then return back out immediately.
        if ($operator instanceof Closure) {
            return $this->nested($field, $operator);
        }

        // If the given operator is not found in the list of valid operators we will
        // assume that the developer is just short-cutting the '=' operators and
        // we will set the operators to '=' and set the values appropriately.
        if ($this->invalidOperator($operator)) {
            [$value, $operator] = [$operator, '='];
        }

        // if ($this->isRangeOperator($operator)) {
        //     return $this->range($field, array_merge());
        // }

        // Todo: implement

        $this->bool($boolQuery);

        return $this;
    }

    /**
     * Prepare the value and operator for a where clause.
     *
     * @param  string  $value
     * @param  string  $operator
     * @param  bool  $useDefault
     * @throws \InvalidArgumentException
     * @return array
     */
    public function prepareValueAndOperator($value, $operator, $useDefault = false)
    {
        if ($useDefault) {
            return [$operator, '='];
        } elseif ($this->invalidOperatorAndValue($operator, $value)) {
            throw new InvalidArgumentException('Illegal operator and value combination.');
        }

        return [$value, $this->transformOperator($operator)];
    }

    /**
     * Transform operator to supported format.
     *
     * @param  string $operator
     * @return string
     */
    protected function transformOperator($operator)
    {
        $maps = [
            'lt' => '>',
            'gt' => '<',
            'lte' => '<=',
            'gte' => '>=',
        ];

        foreach ($maps as $value => $alias) {
            if ($alias === $operator) {
                return $value;
            }
        }

        return $operator;
    }

    /**
     * Determine if the given operator and value combination is legal.
     *
     * Prevents using Null values with invalid operators.
     *
     * @param  string  $operator
     * @param  mixed  $value
     * @return bool
     */
    protected function invalidOperatorAndValue($operator, $value)
    {
        return is_null($value) && in_array($operator, $this->operators) &&
             ! in_array($operator, ['=', '<>', '!=']);
    }

    /**
     * Determine if the given operator is supported.
     *
     * @param  string  $operator
     * @return bool
     */
    protected function invalidOperator($operator)
    {
        return ! in_array(strtolower($operator), $this->operators, true);
    }

    /**
     * Determine if the given operator is range.
     *
     * @param  string  $operator
     * @return bool
     */
    protected function isRangeOperator($operator)
    {
        return in_array(strtolower($operator), [
            'lt', 'gt', 'lte', 'gte',
        ], true);
    }

    /**
     * Add an "or where" clause to the query.
     *
     * @param  string|array|\Closure $field
     * @param  mixed $operator
     * @param  mixed $value
     * @return $this
     */
    public function orWhere($field, $operator = null, $value = null)
    {
        [$value, $operator] = $this->prepareValueAndOperator(
            $value,
            $operator,
            func_num_args() === 2
        );

        return $this->where($field, $operator, $value, BoolQuery::SHOULD);
    }

    /**
     * Add a "where in" clause to the query.
     *
     * @param  string $column
     * @param  mixed $values
     * @param  array $parameters
     * @return $this
     */
    public function whereIn($field, $values, array $parameters = [], $not = false)
    {
        // Next, if the value is Arrayable we need to cast it to its raw array form so we
        // have the underlying array value instead of an Arrayable object which is not
        // able to be added as a binding, etc. We will then add to the wheres array.
        if ($values instanceof Arrayable) {
            $values = $values->toArray();
        }

        $boolQuery = $not ? BoolQuery::MUST : BoolQuery::MUST_NOT;

        return $this->bool($boolQuery)->terms($field, $values, $parameters);
    }

    /**
     * Add a "where not in" clause to the query.
     *
     * @param  string $field
     * @param  mixed $values
     * @param  array $parameters
     * @return $this
     */
    public function whereNotIn($field, $values, array $parameters = [])
    {
        return $this->whereIn($field, $values, $parameters, true);
    }

    /**
     * Add a where clause on the primary key to the query.
     *
     * @param  mixed $id
     * @param  string|null $type
     * @return $this
     */
    public function whereKey($id, $type = null)
    {
        if ($id instanceof Arrayable) {
            $id = $id->toArray();
        }

        $this->append(new IdsQuery(
            (array) $id,
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
    public function offset(int $offset)
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
        return $this->offset($offset);
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
     * Set bool query operation.
     *
     * @param  string $bool
     * @return $this
     */
    public function bool($bool)
    {
        $this->boolQuery = $this->prepareBoolQuery($bool);

        return $this;
    }

    /**
     * Switch to a should statement.
     *
     * @return $this
     */
    public function should()
    {
        $this->boolQuery = BoolQuery::SHOULD;

        return $this;
    }

    /**
     * Switch to a must statement.
     *
     * @return $this
     */
    public function must()
    {
        $this->boolQuery = BoolQuery::MUST;

        return $this;
    }

    /**
     * Switch to a must not statement.
     *
     * @return $this
     */
    public function mustNot()
    {
        $this->boolQuery = BoolQuery::MUST_NOT;

        return $this;
    }

    /**
     * Switch to a filter query.
     */
    public function filter()
    {
        $this->boolQuery = BoolQuery::FILTER;

        return $this;
    }

    /**
     * Add an term query.
     *
     * @param  string $field
     * @param  string $terms
     * @param  array $parameters
     * @return $this
     */
    public function term($field, $term, array $parameters = [])
    {
        $this->append(new TermQuery($field, $term, $parameters));

        return $this;
    }

    /**
     * Add an terms query.
     *
     * @param  string $field
     * @param  array $terms
     * @param  array $parameters
     * @return $this
     */
    public function terms($field, array $terms, array $parameters = [])
    {
        $this->append(new TermsQuery($field, $terms, $parameters));

        return $this;
    }

    /**
     * Add an exists query.
     *
     * @param  null|string|array $fields
     * @return $this
     */
    public function exists($fields = null)
    {
        if (is_null($fields)) {
            return $this->indexExists();
        }

        $fields = is_array($fields) ? $fields : func_get_args();

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
     * @param  array $parameters
     * @return $this
     */
    public function wildcard($field, $value, array $parameters = [])
    {
        $this->append(new WildcardQuery($field, $value, $parameters));

        return $this;
    }

    /**
     * Add a match phrase query.
     *
     * @param  string $field
     * @param  string $value
     * @param  array $parameters
     * @return $this
     */
    public function matchPhrase($field, $value, array $parameters = [])
    {
        $this->append(new MatchPhraseQuery($field, $value, $parameters));

        return $this;
    }

    /**
     * Add a boost query.
     *
     * @param  array $parameters
     * @return $this
     */
    public function matchAll(array $parameters = [])
    {
        $this->append(new MatchAllQuery($parameters));

        return $this;
    }

    /**
     * Add a match query.
     *
     * @param  string $field
     * @param  string $term
     * @param  array  $parameters
     * @return $this
     */
    public function match($field, $term, array $parameters = [])
    {
        $this->append(new MatchQuery($field, $term, $parameters));

        return $this;
    }

    /**
     * Add a multi match query.
     *
     * @param  array $fields
     * @param  string $term
     * @param  array $parameters
     * @return $this
     */
    public function multiMatch(array $fields, $term, array $parameters = [])
    {
        $this->append(new MultiMatchQuery($fields, $term, $parameters));

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
     * @param  array $parameters
     * @return $this
     */
    public function geoDistance($field, $distance, $location, array $parameters = [])
    {
        $this->append(new GeoDistanceQuery($field, $distance, $location, $parameters));

        return $this;
    }

    /**
     * Add a geo distance range query.
     *
     * @param  string $field
     * @param  mixed $from
     * @param  mixed $to
     * @param  mixed $location
     * @param  array $parameters
     * @return $this
     */
    public function geoDistanceRange($field, $from, $to, $location, array $parameters = [])
    {
        $range = compact('from', 'to');

        $this->append(new GeoDistanceQuery($field, $range, $location, $parameters));

        return $this;
    }

    /**
     * Add a geo polygon query.
     *
     * @param  string $field
     * @param  array $points
     * @param  array $parameters
     * @return $this
     */
    public function geoPolygon($field, array $points = [], array $parameters = [])
    {
        $query = new GeoPolygonQuery($field, $points, $parameters);

        $this->append($query);

        return $this;
    }

    /**
     * Add a geo shape query.
     *
     * @param  string $field
     * @param  string $type
     * @param  array $coordinates
     * @param  array $parameters
     * @return $this
     */
    public function geoShape($field, $type, array $coordinates = [], array $parameters = [])
    {
        $query = new GeoShapeQuery;

        $query->addShape($field, $type, $coordinates, $parameters);

        $this->append($query);

        return $this;
    }

    /**
     * Add a prefix query.
     *
     * @param  string $field
     * @param  string $term
     * @param  array $parameters
     * @return $this
     */
    public function prefix($field, $term, array $parameters = [])
    {
        $this->append(new PrefixQuery($field, $term, $parameters));

        return $this;
    }

    /**
     * Add a query string query.
     *
     * @param  string $query
     * @param  array $parameters
     * @return $this
     */
    public function queryString($query, array $parameters = [])
    {
        $this->append(new QueryStringQuery($query, $parameters));

        return $this;
    }

    /**
     * Add a simple query string query.
     *
     * @param string $query
     * @param array $parameters
     * @return $this
     */
    public function simpleQueryString($query, array $parameters = [])
    {
        $this->append(new SimpleQueryStringQuery($query, $parameters));

        return $this;
    }

    /**
     * Add a highlight to result.
     *
     * @param  array $fields
     * @param  array $parameters
     * @param  string|array $preTag
     * @param  string|array $postTag
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/search-request-highlighting.html
     * @return $this
     */
    public function highlight($fields = ['_all' => []], $parameters = [], $preTag = '<mark>', $postTag = '</mark>')
    {
        $highlight = new Highlight;
        $highlight->setTags((array) $preTag, (array) $postTag);

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
     * @param  array  $parameters
     * @return $this
     */
    public function range($field, array $parameters = [])
    {
        $this->append(new RangeQuery($field, $parameters));

        return $this;
    }

    /**
     * Add a regexp query.
     *
     * @param  string $field
     * @param  array  $parameters
     * @return $this
     */
    public function regexp($field, $regex, array $parameters = [])
    {
        $this->append(new RegexpQuery($field, $regex, $parameters));

        return $this;
    }

    /**
     * Add a common term query.
     *
     * @param  string $field
     * @param  string $term
     * @param  array $parameters
     * @return $this
     */
    public function commonTerm($field, $term, array $parameters = [])
    {
        $this->append(new CommonTermsQuery($field, $term, $parameters));

        return $this;
    }

    /**
     * Add a fuzzy query.
     *
     * @param  string $field
     * @param  string $term
     * @param  array $parameters
     * @return $this
     */
    public function fuzzy($field, $term, array $parameters = [])
    {
        $this->append(new FuzzyQuery($field, $term, $parameters));

        return $this;
    }

    /**
     * Add a nested query.
     *
     * @param  srting $field
     * @param  \Closure $closure
     * @param  string $scoreMode
     * @return $this
     */
    public function nested($field, Closure $closure, $scoreMode = 'avg')
    {
        $builder = $this->getIndex()->newQuery();

        $closure($builder);

        $nestedQuery = $builder->getQuery()->getQueries();

        $query = new NestedQuery($field, $nestedQuery, ['score_mode' => $scoreMode]);

        $this->append($query);

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
    public function getBoolQuery()
    {
        return $this->boolQuery;
    }

    /**
     * Determine if the given bool query operation is legal.
     *
     * @param  string $bool
     * @throws \InvalidArgumentException
     * @return string
     */
    protected function prepareBoolQuery($bool)
    {
        $reflect = new ReflectionClass(BoolQuery::class);

        if (! in_array($bool, $reflect->getConstants())) {
            throw new InvalidArgumentException('Illegal bool query operation.');
        }

        return $bool;
    }

    /**
     * Append a query.
     *
     * @param  \ONGR\ElasticsearchDSL\BuilderInterface $query
     * @return $this
     */
    public function append(BuilderInterface $query)
    {
        $this->query->addQuery($query, $this->getBoolQuery());

        return $this;
    }

    /**
     * Apply the callback's query changes if the given "value" is true.
     *
     * @param  mixed  $value
     * @param  callable  $callback
     * @param  callable  $default
     * @return mixed|$this
     */
    public function when($value, $callback, $default = null)
    {
        if ($value) {
            return $callback($this, $value) ?: $this;
        } elseif ($default) {
            return $default($this, $value) ?: $this;
        }

        return $this;
    }

    /**
     * Pass the query to a given callback.
     *
     * @param  \Closure  $callback
     * @return $this
     */
    public function tap($callback)
    {
        return $this->when(true, $callback);
    }

    /**
     * Apply the callback's query changes if the given "value" is false.
     *
     * @param  mixed  $value
     * @param  callable  $callback
     * @param  callable  $default
     * @return mixed|$this
     */
    public function unless($value, $callback, $default = null)
    {
        if (! $value) {
            return $callback($this, $value) ?: $this;
        } elseif ($default) {
            return $default($this, $value) ?: $this;
        }

        return $this;
    }

    /**
     * Find a document by its primary key.
     *
     * @param  mixed $id
     * @param  string|null $type
     * @return mixed
     */
    public function find($id, $type = null)
    {
        if (is_array($id) || $id instanceof Arrayable) {
            return $this->findMany($id, $type);
        }

        return $this->whereKey($id, $type)->first();
    }

    /**
     * Find multiple documents by their primary key.
     *
     * @param  \Illuminate\Contracts\Support\Arrayable|array $ids
     * @param  string|null $type
     * @return \Jenky\LaravelElasticsearch\Storage\Response
     */
    public function findMany($ids, $type = null)
    {
        if (! empty($ids)) {
            $this->whereKey($ids, $type);
        }

        return $this->get();
    }

    /**
     * Execute the query and get the first result.
     *
     * @return \Jenky\LaravelElasticsearch\Storage\Document|array
     */
    public function first()
    {
        return $this->limit(1)->get()->first();
    }

    /**
     * Execute the query and get all results.
     *
     * @return \Jenky\LaravelElasticsearch\Storage\Response
     */
    public function get($perPage = 10, $pageName = 'page', $page = null): Response
    {
        $page = $page ?: Paginator::resolveCurrentPage($pageName);

        $results = $this->search(
            $this->forPage($page, $perPage)->toDSL()
        );

        return Response::make($results, $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => $pageName,
        ]);
    }

    /**
     * Handle dynamic method calls into the method.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @throws \BadMethodCallException
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $parameters);
        }

        // if (Str::startsWith($method, 'where')) {
        //     return $this->dynamicWhere($method, $parameters);
        // }

        static::throwBadMethodCallException($method);
    }
}
