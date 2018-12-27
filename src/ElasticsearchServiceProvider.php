<?php

namespace Jenky\LaravelElasticsearch;

use Cviebrock\LaravelElasticsearch\ServiceProvider as ElasticsearchProvider;

class ElasticsearchServiceProvider extends ElasticsearchProvider
{
    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        if (! $this->app->bound(ElasticsearchProvider::class)) {
            $this->app->register(ElasticsearchProvider::class);
        }
    }
}
