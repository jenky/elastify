<?php

namespace Jenky\LaravelElasticsearch\Contracts;

interface Connection
{
    /**
     * Begin a fluent query against elasticsearch indices.
     *
     * @param  string $index
     * @return mixed
     */
    public function index($index);

    /**
     * Use elasticsearch API to get documents.
     *
     * @param  string $params
     * @return mixed
     */
    public function get(array $params);

    /**
     * Use elasticsearch API to indexing a document.
     *
     * @param  array $params
     * @return array
     */
    public function insert(array $params);

    /**
     * Use elasticsearch API to indexing multiple documents.
     *
     * @param \Iterator|array $params
     * @return array
     */
    public function bulk($params);

    /**
     * Use elasticsearch API to update a document.
     *
     * @param  array $params
     * @return array
     */
    public function update($params);

    /**
     * Use elasticsearch API to delete a document.
     *
     * @param  array $params
     * @return array
     */
    public function delete($params);
}
