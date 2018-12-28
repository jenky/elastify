<?php

namespace Jenky\LaravelElasticsearch\Connection;

use Elasticsearch\Client;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use Jenky\LaravelElasticsearch\Contracts\ConnectionResolver;

class Manager implements ConnectionResolver
{
    /**
     * The application instance.
     *
     * @var \Illuminate\Foundation\Application
     */
    protected $app;

    /**
     * The database connection factory instance.
     *
     * @var \Jenky\LaravelElasticsearch\Connection\Factory
     */
    protected $factory;

    /**
     * The active connection instances.
     *
     * @var array
     */
    protected $connections = [];

    /**
     * Create a new database manager instance.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @param  \Illuminate\Database\Connectors\ConnectionFactory  $factory
     * @return void
     */
    public function __construct(Application $app, Factory $factory)
    {
        $this->app = $app;
        $this->factory = $factory;
    }

    /**
     * Get a database connection instance.
     *
     * @param  string  $name
     * @return \Elasticsearch\Client
     */
    public function connection($name = null): Client
    {
        $name = $name ?: $this->getDefaultConnection();

        if (! isset($this->connections[$name])) {
            $client = $this->makeConnection($name);

            $this->connections[$name] = $client;
        }

        return $this->connections[$name];
    }

    /**
     * Make the database connection instance.
     *
     * @param  string  $name
     * @return \Elasticsearch\Client
     */
    protected function makeConnection($name): Client
    {
        $config = $this->configuration($name);

        return $this->factory->make($config);
    }

    /**
     * Get the configuration for a connection.
     *
     * @param  string  $name
     * @throws \InvalidArgumentException
     * @return array
     */
    protected function configuration($name)
    {
        $name = $name ?: $this->getDefaultConnection();

        // To get the elasticsearch connection configuration, we will just pull each of the
        // connection configurations and get the configurations for the given name.
        // If the configuration doesn't exist, we'll throw an exception and bail.
        $connections = $this->app['config']['elasticsearch.connections'];

        if (is_null($config = Arr::get($connections, $name))) {
            throw new InvalidArgumentException("Elasticsearch connection [{$name}] not configured.");
        }

        return $config;
    }

    /**
     * Get the default connection name.
     *
     * @return string
     */
    public function getDefaultConnection(): string
    {
        return $this->app['config']['elasticsearch.default'];
    }

    /**
     * Set the default connection name.
     *
     * @param  string  $name
     * @return void
     */
    public function setDefaultConnection($name): string
    {
        $this->app['config']['elasticsearch.default'] = $name;
    }
}
