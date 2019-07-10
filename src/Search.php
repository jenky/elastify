<?php

namespace Jenky\Elastify;

use Jenky\Elastify\Facades\ES;

class Search
{
    /**
     * Search between multiple indices.
     *
     * @param  string|array $indices
     * @return \Jenky\Elastify\Builder\Query
     */
    public static function with($indices)
    {
        $indices = is_array($indices) ? $indices : func_get_args();
        $from = [];

        foreach ($indices as $index) {
            if ($index instanceof Index) {
                $from[] = $index->searchableAs();
            } else {
                $from[] = $index;
            }
        }

        return ES::index(implode(',', $from));
    }
}
