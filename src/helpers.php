<?php

if (! function_exists('elasticsearch')) {
    /**
     * Get a elasticsearch client instance.
     *
     * @param  string  $connection
     * @return \Elasticsearch\Client
     */
    function elasticsearch($connection = null)
    {
        return app('elasticsearch')->connection($connection);
    }
}
