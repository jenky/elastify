<?php

namespace Jenky\LaravelElasticsearch\Storage;

interface Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  \Jenky\LaravelElasticsearch\Storage\Builder $builder
     * @return void
     */
    public function apply(Builder $builder);
}
