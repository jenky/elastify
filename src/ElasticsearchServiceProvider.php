<?php

namespace Jenky\LaravelElasticsearch;

use Illuminate\Support\ServiceProvider;
use Jenky\LaravelElasticsearch\Connection\Factory;
use Jenky\LaravelElasticsearch\Connection\Manager;
use Jenky\LaravelElasticsearch\Contracts\ConnectionResolver;
use Jenky\LaravelElasticsearch\Indices\Index;

class ElasticsearchServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any package services.
     *
     * @return void
     */
    public function boot()
    {
        Index::setConnectionResolver($this->app['elasticsearch']);

        $this->registerPublishing();
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/elasticsearch.php',
            'elasticsearch'
        );

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
            return new Factory($app);
        });

        // The elasticsearch manager is used to resolve various connections, since multiple
        // connections might be managed. It also implements the connection resolver
        // interface which may be used by other components requiring connections.
        $this->app->singleton('elasticsearch', function ($app) {
            return new Manager($app, $app['elasticsearch.factory']);
        });

        $this->app->bind('elasticsearch.connection', function ($app) {
            return $app['elasticsearch']->connection();
        });

        $this->app->alias('elasticsearch', ConnectionResolver::class);
    }

    /**
     * Register the package's publishable resources.
     *
     * @return void
     */
    protected function registerPublishing()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/elasticsearch.php' => config_path('elasticsearch.php'),
            ], 'elasticsearch-config');
        }
    }
}
