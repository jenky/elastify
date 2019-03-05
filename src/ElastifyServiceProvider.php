<?php

namespace Jenky\Elastify;

use Illuminate\Support\ServiceProvider;
use Jenky\Elastify\Connection\Factory;
use Jenky\Elastify\Connection\Manager;
use Jenky\Elastify\Contracts\ClientFactory;
use Jenky\Elastify\Storage\Index;

class ElastifyServiceProvider extends ServiceProvider
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

        $this->app->alias('elasticsearch.factory', ClientFactory::class);

        // The elasticsearch manager is used to resolve various connections, since multiple
        // connections might be managed. It also implements the connection resolver
        // interface which may be used by other components requiring connections.
        $this->app->singleton('elasticsearch', function ($app) {
            return new Manager($app, $app['elasticsearch.factory']);
        });

        $this->app->bind('elasticsearch.connection', function ($app) {
            return $app['elasticsearch']->connection();
        });
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
