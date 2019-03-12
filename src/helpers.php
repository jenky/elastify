<?php

if (! function_exists('elasticsearch')) {
    /**
     * Get a elasticsearch client instance.
     *
     * @param  string  $connection
     * @return \Jenky\Elastify\Contracts\ConnectionInterface
     */
    function elasticsearch($connection = null)
    {
        return $connection ? app('elasticsearch') : app('elasticsearch')->connection($connection);
    }
}
