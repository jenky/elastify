<?php

namespace Jenky\Elastify\Builder;

use Closure;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Traits\ForwardsCalls;
use Illuminate\Support\Traits\Macroable;
use InvalidArgumentException;
use Jenky\Elastify\Concerns\BuildsQueries;
use Jenky\Elastify\Storage\Response;
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
use ONGR\ElasticsearchDSL\Sort\FieldSort;
use ReflectionClass;

class Query extends AbstractBuilder
{
    use BuildsQueries, ForwardsCalls, Macroable {
        __call as macroCall;
    }

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
     * @return array
     */
    public function create(array $params)
    {
        $data = [
            'index' => $this->from,
            'body' => $params,
        ];

        return $this->getConnection()->indices()->create($data);
    }

    /**
     * Create a new index if not exists.
     *
     * @param  array $params
     * @return void
     */
    public function createIfNotExists(array $params)
    {
        if (! $this->fromExists()) {
            $this->create($params);
        }
    }

    /**
     * Delete an index.
     *
     * @return array
     */
    public function drop()
    {
        return $this->getConnection()->indices()->delete([
            'index' => $this->from,
        ]);
    }

    /**
     * Delete an existing index.
     *
     * @return void
     */
    public function dropIfExists()
    {
        if ($this->fromExists()) {
            $this->drop();
        }
    }

    /**
     * Index a signle document.
     *
     * @param  array $params
     * @return array
     */
    public function insert(array $params)
    {
        return $this->getConnection()->index(array_filter([
            'index' => $this->from,
            'type' => $this->type,
            'body' => $params,
        ]));
    }

    /**
     * Flush the index.
     *
     * @return array
     */
    public function flush()
    {
        return $this->getConnection()
            ->indices()
            ->flush(array_filter([
                'index' => $this->from,
                'type' => $this->type,
            ]));
    }

    /**
     * Retrieve the "count" result of the query.
     *
     * @param  bool  $raw
     * @return int
     */
    public function count($raw = false)
    {
        $response = $this->getConnection()
            ->count(array_filter([
                'index' => $this->from,
                'type' => $this->type,
            ]));

        return $raw ? $response : $response['count'] ?? 0;
    }

    /**
     * Perform the search by using search API.
     *
     * @param  array $params
     * @return array
     */
    public function search(array $params = [])
    {
        return $this->getConnection()->search(array_filter([
            'index' => $this->from,
            'type' => $this->type,
            'body' => $params,
        ]));
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

        return $this->append(new IdsQuery(
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
        return $this->append(new TermQuery($field, $term, $parameters));
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
        return $this->append(new TermsQuery($field, $terms, $parameters));
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
            return $this->fromExists();
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
        return $this->append(new WildcardQuery($field, $value, $parameters));
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
        return $this->append(new MatchPhraseQuery($field, $value, $parameters));
    }

    /**
     * Add a boost query.
     *
     * @param  array $parameters
     * @return $this
     */
    public function matchAll(array $parameters = [])
    {
        return $this->append(new MatchAllQuery($parameters));
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
        return $this->append(new MatchQuery($field, $term, $parameters));
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
        return $this->append(new MultiMatchQuery($fields, $term, $parameters));
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
        return $this->append(new GeoBoundingBoxQuery($field, $values, $parameters));
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
        return $this->append(new GeoDistanceQuery($field, $distance, $location, $parameters));
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

        return $this->append(new GeoDistanceQuery($field, $range, $location, $parameters));
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

        return $this->append($query);
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

        return $this->append($query);
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
        return $this->append(new PrefixQuery($field, $term, $parameters));
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
        return $this->append(new QueryStringQuery($query, $parameters));
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
        return $this->append(new SimpleQueryStringQuery($query, $parameters));
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
        $highlight = (new Highlight)->setTags((array) $preTag, (array) $postTag);

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
        return $this->append(new RangeQuery($field, $parameters));
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
        return $this->append(new RegexpQuery($field, $regex, $parameters));
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
        return $this->append(new CommonTermsQuery($field, $term, $parameters));
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
        return $this->append(new FuzzyQuery($field, $term, $parameters));
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

        return $this->append($query);
    }

    /**
     * Add aggregation.
     *
     * @param \Closure|string $callback
     * @return $this
     */
    public function aggregate($callback)
    {
        return $this->callBuilder($callback, new Aggregation(
            $this->connection, $this->query
        ));
    }

    /**
     * Add suggesters.
     *
     * @param  \Closure|string $callback
     * @return $this
     */
    public function suggest($callback)
    {
        return $this->callBuilder($callback, new Suggestion(
            $this->connection, $this->query
        ));
    }

    /**
     * Call the builder to build sub query.
     *
     * @param  \Closure|string $callback
     * @param  \Jenky\Elastify\Builder\AbstractBuilder $builder
     * @return $this
     */
    protected function callBuilder($callback, AbstractBuilder $builder)
    {
        if ($callback instanceof Closure) {
            $callback($builder);
        } else {
            (new $callback)->__invoke($builder);
        }

        return $this;
    }

    /**
     * Generate aggregation name for field.
     *
     * @param  string $field
     * @return string
     */
    protected function generateAggregationName($field)
    {
        [$one, $caller] = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

        return sprintf('%s_%s', $caller['function'], $field);
    }

    /**
     * Retrieve the average of the values of a given field.
     *
     * @param  string  $field
     * @param  string|null $name
     * @return mixed
     */
    public function avg($field, $name = null)
    {
        $name = $name ?: $this->generateAggregationName($field);

        return $this->aggregate(function (Aggregation $builder) use ($field, $name) {
            $builder->average($name, $field);
        });
    }

    /**
     * Alias for the "avg" method.
     *
     * @param  string  $column
     * @return mixed
     */
    public function average($column)
    {
        return $this->avg($column);
    }

    /**
     * Retrieve the sum of the values of a given field.
     *
     * @param  string  $field
     * @param  string|null $name
     * @return mixed
     */
    public function sum($field, $name = null)
    {
        $name = $name ?: $this->generateAggregationName($field);

        return $this->aggregate(function (Aggregation $builder) use ($field, $name) {
            $builder->sum($name, $field);
        });
    }

    /**
     * Retrieve the minimum value of a given field.
     *
     * @param  string  $field
     * @param  string|null $name
     * @return mixed
     */
    public function min($field, $name = null)
    {
        $name = $name ?: $this->generateAggregationName($field);

        return $this->aggregate(function (Aggregation $builder) use ($field, $name) {
            $builder->min($name, $field);
        });
    }

    /**
     * Retrieve the maximum of the values of a given field.
     *
     * @param  string  $field
     * @param  string|null $name
     * @return mixed
     */
    public function max($field, $name = null)
    {
        $name = $name ?: $this->generateAggregationName($field);

        return $this->aggregate(function (Aggregation $builder) use ($field, $name) {
            $builder->max($name, $field);
        });
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
     * @return \Jenky\Elastify\Storage\Response
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
     * @return \Jenky\Elastify\Storage\Document|array
     */
    public function first()
    {
        return $this->limit(1)->get()->first();
    }

    /**
     * Execute the query and get all results.
     *
     * @return \Jenky\Elastify\Storage\Response
     */
    public function get($perPage = 10, $pageName = 'page', $page = null): Response
    {
        $page = $page ?: Paginator::resolveCurrentPage($pageName);

        $results = $this->search(
            $this->forPage($page, $perPage)->toDSL()
        );

        return $this->paginator(
            $results,
            $perPage,
            $page,
            [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => $pageName,
            ]
        );
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
