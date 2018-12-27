<?php

namespace Jenky\LaravelElasticsearch;

use Elasticsearch\Connections\ConnectionFactory;
use Illuminate\Support\ServiceProvider;

class ElasticsearchServiceProvider extends ServiceProvider
{
    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerConnectionServices();
    }

    /**
     * Register the primary elasticsearch bindings.
     *
     * @return void
     */
    protected function registerConnectionServices()
    {
        // The connection factory is used to create the actual connection instances on
        // the elasticsearch. We will inject the factory into the manager so that it may
        // make the connections while they are actually needed and not of before.
        $this->app->singleton('elasticsearch.factory', function ($app) {
            return new ConnectionFactory($app);
        });

        // The database manager is used to resolve various connections, since multiple
        // connections might be managed. It also implements the connection resolver
        // interface which may be used by other components requiring connections.
        // $this->app->singleton('db', function ($app) {
        //     return new DatabaseManager($app, $app['db.factory']);
        // });

        // $this->app->bind('db.connection', function ($app) {
        //     return $app['db']->connection();
        // });
    }
}
