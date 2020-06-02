<?php

namespace Jenky\Elastify\Tests;

use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\ElasticsearchException;
use Jenky\Elastify\Connection\Manager;
use Jenky\Elastify\Contracts\ConnectionInterface;

class ConnectionTest extends TestCase
{
    protected $elasticsearch;

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application   $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $config = $app->get('config');

        $config->set('elasticsearch.connections.testbench', [
            'hosts' => [
                [
                    'host' => 'localhost',
                    'port' => 9201,
                ],
            ]
        ]);
    }

    public function setUp(): void
    {
        parent::setUp();

        $this->elasticsearch = elasticsearch();
    }

    public function test_connection_factory()
    {
        $this->assertInstanceOf(Manager::class, $this->elasticsearch);
        $this->assertInstanceOf(ConnectionInterface::class, $this->app['elasticsearch.connection']);

        $this->assertTrue($this->elasticsearch->ping());
    }

    public function test_connection_client_instanceof_elasticsearch_client()
    {
        $this->assertInstanceOf(Client::class, $this->elasticsearch->getClient());
    }

    public function test_connection()
    {
        $info = $this->elasticsearch->info();

        $this->assertIsArray($info);
        $this->assertArrayHasKey('name', $info);
        $this->assertArrayHasKey('version', $info);
        $this->assertArrayHasKey('tagline', $info);
        $this->assertIsArray($info['version']);

        $this->expectException(ElasticsearchException::class);

        $this->assertFalse(elasticsearch('testbench')->ping());
    }

    public function test_invalid_connection()
    {
        $this->app['elasticsearch']->setDefaultConnection('foo');
        $this->assertEquals('foo', $this->app['elasticsearch']->getDefaultConnection());

        $this->expectException(\InvalidArgumentException::class);

        $this->app['elasticsearch.connection']->info();
    }
}
