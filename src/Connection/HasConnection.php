<?php

namespace Jenky\Elastify\Connection;

use Jenky\Elastify\Contracts\ConnectionResolver;

trait HasConnection
{
    /**
     * The connection name for the index.
     *
     * @var string
     */
    protected $connection;

    /**
     * The connection resolver instance.
     *
     * @var \Jenky\Elastify\Contracts\ConnectionResolver
     */
    protected static $resolver;

    /**
     * Get the elasticsearch connection for the index.
     *
     * @return \Jenky\Elastify\Contracts\ConnectionInterface
     */
    public function getConnection()
    {
        return static::resolveConnection($this->getConnectionName());
    }

    /**
     * Get the current connection name for the index.
     *
     * @return string
     */
    public function getConnectionName()
    {
        return $this->connection;
    }

    /**
     * Set the connection associated with the index.
     *
     * @param  string  $name
     * @return $this
     */
    public function setConnection($name)
    {
        $this->connection = $name;

        return $this;
    }

    /**
     * Resolve a connection instance.
     *
     * @param  string|null  $connection
     * @return \Jenky\Elastify\Contracts\ConnectionInterface
     */
    public static function resolveConnection($connection = null)
    {
        return static::$resolver->connection($connection);
    }

    /**
     * Get the connection resolver instance.
     *
     * @return \Jenky\Elastify\Contracts\ConnectionResolver
     */
    public static function getConnectionResolver(): ConnectionResolver
    {
        return static::$resolver;
    }

    /**
     * Set the connection resolver instance.
     *
     * @param  \Jenky\Elastify\Contracts\ConnectionResolver  $resolver
     * @return void
     */
    public static function setConnectionResolver(ConnectionResolver $resolver)
    {
        static::$resolver = $resolver;
    }

    /**
     * Unset the connection resolver for models.
     *
     * @return void
     */
    public static function unsetConnectionResolver()
    {
        static::$resolver = null;
    }
}
