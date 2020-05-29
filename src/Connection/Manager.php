<?php

namespace Jenky\Elastify\Connection;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use Jenky\Elastify\Contracts\ConnectionInterface;
use Jenky\Elastify\Contracts\ConnectionResolver;

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
     * @var \Jenky\Elastify\Connection\Factory
     */
    protected $factory;

    /**
     * The active connection instances.
     *
     * @var array
     */
    protected $connections = [];

    /**
     * The custom connection resolvers.
     *
     * @var array
     */
    protected $extensions = [];

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
     * @param  string|null  $name
     * @return \Jenky\Elastify\Contracts\ConnectionInterface
     */
    public function connection(?string $name = null): ConnectionInterface
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
     * @return \Jenky\Elastify\Contracts\ConnectionInterface
     */
    protected function makeConnection($name)
    {
        $config = $this->configuration($name);

        // First we will check by the connection name to see if an extension has been
        // registered specifically for that connection. If it has we will call the
        // Closure and pass it the config allowing it to resolve the connection.
        if (isset($this->extensions[$name])) {
            return call_user_func($this->extensions[$name], $config, $name);
        }

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
    public function setDefaultConnection($name)
    {
        $this->app['config']['elasticsearch.default'] = $name;
    }

    /**
     * Register an extension connection resolver.
     *
     * @param  string    $name
     * @param  callable  $resolver
     * @return void
     */
    public function extend($name, callable $resolver)
    {
        $this->extensions[$name] = $resolver;
    }

    /**
     * Return all of the created connections.
     *
     * @return array
     */
    public function getConnections()
    {
        return $this->connections;
    }

    /**
     * Dynamically pass methods to the default connection.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->connection()->{$method}(...$parameters);
    }
}
