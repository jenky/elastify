<?php

namespace Jenky\Elastify\Storage;

interface Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  \Jenky\Elastify\Storage\Builder $builder
     * @return void
     */
    public function apply(Builder $builder);
}
