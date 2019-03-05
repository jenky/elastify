<?php

namespace Jenky\Elastify\Builder;

use Jenky\Elastify\Concerns\BuildsQueries;
use ONGR\ElasticsearchDSL\Aggregation\AbstractAggregation;
use ONGR\ElasticsearchDSL\Aggregation\Bucketing\DateRangeAggregation;
use ONGR\ElasticsearchDSL\Aggregation\Bucketing\GeoDistanceAggregation;
use ONGR\ElasticsearchDSL\Aggregation\Bucketing\GeoHashGridAggregation;
use ONGR\ElasticsearchDSL\Aggregation\Bucketing\HistogramAggregation;
use ONGR\ElasticsearchDSL\Aggregation\Bucketing\Ipv4RangeAggregation;
use ONGR\ElasticsearchDSL\Aggregation\Bucketing\MissingAggregation;
use ONGR\ElasticsearchDSL\Aggregation\Bucketing\RangeAggregation;
use ONGR\ElasticsearchDSL\Aggregation\Bucketing\TermsAggregation;
use ONGR\ElasticsearchDSL\Aggregation\Metric\AvgAggregation;
use ONGR\ElasticsearchDSL\Aggregation\Metric\CardinalityAggregation;
use ONGR\ElasticsearchDSL\Aggregation\Metric\GeoBoundsAggregation;
use ONGR\ElasticsearchDSL\Aggregation\Metric\MaxAggregation;
use ONGR\ElasticsearchDSL\Aggregation\Metric\MinAggregation;
use ONGR\ElasticsearchDSL\Aggregation\Metric\PercentileRanksAggregation;
use ONGR\ElasticsearchDSL\Aggregation\Metric\PercentilesAggregation;
use ONGR\ElasticsearchDSL\Aggregation\Metric\StatsAggregation;
use ONGR\ElasticsearchDSL\Aggregation\Metric\SumAggregation;
use ONGR\ElasticsearchDSL\Aggregation\Metric\ValueCountAggregation;
use ONGR\ElasticsearchDSL\Search;

class Aggregation
{
    use BuildsQueries;

    /**
     * An instance of DSL query.
     *
     * @var \ONGR\ElasticsearchDSL\Search
     */
    public $query;

    /**
     * Create new aggretion builder instance.
     *
     * @param   \ONGR\ElasticsearchDSL\Search $query
     * @return void
     */
    public function __construct(Search $query = null)
    {
        $this->query = $query;
    }

    /**
     * Add an average aggregate.
     *
     * @param   $name
     * @param   string|null $field
     * @param   string|null $script
     * @return $this
     */
    public function average($name, $field = null, $script = null)
    {
        $aggregation = new AvgAggregation($name, $field, $script);

        return $this->append($aggregation);
    }

    /**
     * Add an cardinality aggregate.
     *
     * @param  $name
     * @param  string|null $field
     * @param  string|null $script
     * @param  int         $precision
     * @param  bool        $rehash
     * @return $this
     */
    public function cardinality($name, $field = null, $script = null, $precision = null, $rehash = null)
    {
        $aggregation = new CardinalityAggregation($name);

        $aggregation->setField($field);

        $aggregation->setScript($script);

        $aggregation->setPrecisionThreshold($precision);

        $aggregation->setRehash($rehash);

        return $this->append($aggregation);
    }

    /**
     * Add a date range aggregate.
     *
     * @param  $name
     * @param  string|null $field
     * @param  string|null $format
     * @param  array $ranges
     * @return $this
     */
    public function dateRange($name, $field = null, $format = null, array $ranges = [])
    {
        $aggregation = new DateRangeAggregation($name, $field, $format, $ranges);

        return $this->append($aggregation);
    }

    /**
     * Add a geo bounds aggregate.
     *
     * @param  string      $name
     * @param  null|string $field
     * @param  bool        $wrapLongitude
     * @return $this
     */
    public function geoBounds($name, $field = null, $wrapLongitude = true)
    {
        $aggregation = new GeoBoundsAggregation($name, $field, $wrapLongitude);

        return $this->append($aggregation);
    }

    /**
     * Add a geo bounds aggregate.
     *
     * @param  string      $name
     * @param  null|string $field
     * @param  string|null $origin
     * @param  array $ranges
     * @return $this
     */
    public function geoDistance($name, $field = null, $origin = null, array $ranges = [])
    {
        $aggregation = new GeoDistanceAggregation($name, $field, $origin, $ranges);

        return $this->append($aggregation);
    }

    /**
     * Add a geo hash grid aggregate.
     *
     * @param  string      $name
     * @param  null|string $field
     * @param  float       $precision
     * @param  null        $size
     * @param  null        $shardSize
     */
    public function geoHashGrid($name, $field, $precision, $size = null, $shardSize = null)
    {
        $aggregation = new GeoHashGridAggregation($name, $field, $precision, $size, $shardSize);

        return $this->append($aggregation);
    }

    /**
     * Add a histogram aggregate.
     *
     * @param  string $name
     * @param  string|null $field
     * @param  int|null    $interval
     * @param  int    $minDocCount
     * @param  string $orderMode
     * @param  string $orderDirection
     * @param  int    $extendedBoundsMin
     * @param  int    $extendedBoundsMax
     * @param  bool   $keyed
     * @return $this
     */
    public function histogram(
        $name,
        $field = null,
        $interval = null,
        $minDocCount = null,
        $orderMode = null,
        $orderDirection = 'asc',
        $extendedBoundsMin = null,
        $extendedBoundsMax = null,
        $keyed = null
    ) {
        $aggregation = new HistogramAggregation(
            $name,
            $field,
            $interval,
            $minDocCount,
            $orderMode,
            $orderDirection,
            $extendedBoundsMin,
            $extendedBoundsMax,
            $keyed
        );

        return $this->append($aggregation);
    }

    /**
     * Add an ipv4 range aggregate.
     *
     * @param  string $name
     * @param  string|null  $field
     * @param  array $ranges
     * @return $this
     */
    public function ipv4Range($name, $field = null, array $ranges)
    {
        $aggregation = new Ipv4RangeAggregation($name, $field, $ranges);

        return $this->append($aggregation);
    }

    /**
     * Add an max aggregate.
     *
     * @param  string $name
     * @param  string|null $field
     * @param  string|null $script
     * @return $this
     */
    public function max($name, $field = null, $script = null)
    {
        $aggregation = new MaxAggregation($name, $field, $script);

        return $this->append($aggregation);
    }

    /**
     * Add an min aggregate.
     *
     * @param  string $name
     * @param  string|null $field
     * @param  string|null $script
     * @return $this
     */
    public function min($name, $field = null, $script = null)
    {
        $aggregation = new MinAggregation($name, $field, $script);

        return $this->append($aggregation);
    }

    /**
     * Add an missing aggregate.
     *
     * @param  string $name
     * @param  string|null $field
     * @return $this
     */
    public function missing($name, $field = null)
    {
        $aggregation = new MissingAggregation($name, $field);

        return $this->append($aggregation);
    }

    /**
     * Add an percentile aggregate.
     *
     * @param  string $name
     * @param  string|null $field
     * @param  array|null $percents
     * @param  null $script
     * @param  null $compression
     * @return $this
     */
    public function percentile($name, $field = null, $percents = null, $script = null, $compression = null)
    {
        $aggregation = new PercentilesAggregation($name, $field, $percents, $script, $compression);

        return $this->append($aggregation);
    }

    /**
     * Add an percentileRanks aggregate.
     *
     * @param  string $name
     * @param  string $field
     * @param  array  $values
     * @param  null   $script
     * @param  null   $compression
     * @return $this
     */
    public function percentileRanks($name, $field, array $values, $script = null, $compression = null)
    {
        $aggregation = new PercentileRanksAggregation($name, $field, $values, $script, $compression);

        return $this->append($aggregation);
    }

    /**
     * Add an stats aggregate.
     *
     * @param  $name
     * @param  string|null $field
     * @param  string|null $script
     * @return $this
     */
    public function stats($name, $field = null, $script = null)
    {
        $aggregation = new StatsAggregation($name, $field, $script);

        return $this->append($aggregation);
    }

    /**
     * Add an sum aggregate.
     *
     * @param  string $name
     * @param  string|null $field
     * @param  string|null $script
     * @return $this
     */
    public function sum($name, $field = null, $script = null)
    {
        $aggregation = new SumAggregation($name, $field, $script);

        return $this->append($aggregation);
    }

    /**
     * Add a value count aggregate.
     *
     * @param  $name
     * @param  string      $field
     * @param  string|null $script
     * @return $this
     */
    public function valueCount($name, $field = null, $script = null)
    {
        $aggregation = new ValueCountAggregation($name, $field, $script);

        return $this->append($aggregation);
    }

    /**
     * Add a range aggregate.
     *
     * @param  string $name
     * @param  string $field
     * @param  array  $ranges
     * @param  bool   $keyed
     * @return $this
     */
    public function range($name, $field, array $ranges, $keyed = false)
    {
        $aggregation = new RangeAggregation($name, $field, $ranges, $keyed);

        return $this->append($aggregation);
    }

    /**
     * Add a terms aggregate.
     *
     * @param  string      $name
     * @param  string|null $field
     * @param  string|null $script
     * @return $this
     */
    public function terms($name, $field = null, $script = null)
    {
        $aggregation = new TermsAggregation($name, $field, $script);

        return $this->append($aggregation);
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
     * Append an aggregation to the aggregation query builder.
     *
     * @param \ONGR\ElasticsearchDSL\Aggregation\AbstractAggregation $aggregation
     * @return $this
     */
    public function append(AbstractAggregation $aggregation)
    {
        $this->query->addAggregation($aggregation);

        return $this;
    }
}
