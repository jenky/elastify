<?php

namespace Jenky\Elastify;

interface Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  \Jenky\Elastify\Builder $builder
     * @return void
     */
    public function apply(Builder $builder);
}
