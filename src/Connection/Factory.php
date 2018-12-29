<?php

namespace Jenky\LaravelElasticsearch\Connection;

use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Ring\Future\CompletedFutureArray;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Arr;
use Jenky\LaravelElasticsearch\Contracts\ClientFactory;
use Psr\Http\Message\ResponseInterface;

class Factory implements ClientFactory
{
    /**
     * Map configuration array keys with ES ClientBuilder setters.
     *
     * @var array
     */
    protected $configMappings = [
        'ssl_verification' => 'setSSLVerification',
        'sniff_on_start' => 'setSniffOnStart',
        'retries' => 'setRetries',
        'http_handler' => 'setHandler',
        'connection_pool' => 'setConnectionPool',
        'connection_selector' => 'setSelector',
        'serializer' => 'setSerializer',
        'connection_factory' => 'setConnectionFactory',
        'endpoint' => 'setEndpoint',
        'namespaces' => 'registerNamespace',
    ];

    /**
     * The IoC container instance.
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $container;

    /**
     * Create a new connection factory instance.
     *
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @return void
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Make the Elasticsearch client for the given named configuration, or
     * the default client.
     *
     * @param  array $config
     * @return \Elasticsearch\Client
     */
    public function make(array $config): Client
    {
        return $this->createClient($config);
    }

    /**
     * Build and configure an Elasticsearch client.
     *
     * @param  array $config
     * @return \Elasticsearch\Client
     */
    protected function createClient(array $config): Client
    {
        $clientBuilder = ClientBuilder::create();

        // Configure hosts
        $clientBuilder->setHosts($config['hosts'] ?? []);

        foreach ($this->configMappings as $key => $method) {
            $value = Arr::get($config, $key);

            if (is_array($value)) {
                foreach ($value as $val) {
                    $clientBuilder->{$method}($val);
                }
            } elseif ($value !== null) {
                $clientBuilder->{$method}($value);
            }
        }

        $this->configureLogging($clientBuilder, $config);
        $this->configureAwsHandlers($clientBuilder, $config);

        return $clientBuilder->build();
    }

    /**
     * Configure Elasticsearch client logging.
     *
     * @param  \Elasticsearch\ClientBuilder $client
     * @param  array $config
     * @return void
     */
    protected function configureLogging(ClientBuilder $client, array $config)
    {
        $driver = Arr::get($config, 'logging.driver');
        $config = Arr::get($config, 'logging.drivers.'.$driver);

        if (! $driver || empty($config)) {
            return;
        }

        switch ($driver) {
            case 'default':
                $logObject = ClientBuilder::defaultLogger(Arr::get($config, 'path'), Arr::get($config, 'level'));
                $clientBuilder->setLogger($logObject);
                break;

            case 'logger':
                $clientBuilder->setLogger($this->container['log']->channel($Arr::get($config, 'channel')));
                break;

            default:
                break;
        }
    }

    /**
     * Configure handlers for any AWS hosts.
     *
     * @param  \Elasticsearch\ClientBuilder $client
     * @param  array $config
     * @return void
     */
    protected function configureAwsHandlers(ClientBuilder $client, array $config)
    {
        foreach ($config['hosts'] ?? [] as $host) {
            if (isset($host['aws']) && $host['aws']) {
                $clientBuilder->setHandler(function (array $request) use ($host) {
                    $psr7Handler = \Aws\default_http_handler();
                    $signer = new \Aws\Signature\SignatureV4('es', $host['aws_region']);
                    $request['headers']['Host'][0] = parse_url($request['headers']['Host'][0])['host'];

                    // Create a PSR-7 request from the array passed to the handler
                    $psr7Request = new Request(
                        $request['http_method'],
                        (new Uri($request['uri']))
                            ->withScheme($request['scheme'])
                            ->withHost($request['headers']['Host'][0]),
                        $request['headers'],
                        $request['body']
                    );

                    // Sign the PSR-7 request with credentials from the environment
                    $signedRequest = $signer->signRequest(
                        $psr7Request,
                        new \Aws\Credentials\Credentials($host['aws_key'], $host['aws_secret'])
                    );

                    // Send the signed request to Amazon ES
                    $response = $psr7Handler($signedRequest)
                        ->then(function (ResponseInterface $response) {
                            return $response;
                        }, function ($error) {
                            return $error['response'];
                        })
                        ->wait();

                    // Convert the PSR-7 response to a RingPHP response
                    return new CompletedFutureArray([
                        'status' => $response->getStatusCode(),
                        'headers' => $response->getHeaders(),
                        'body' => $response->getBody()->detach(),
                        'transfer_stats' => ['total_time' => 0],
                        'effective_url' => (string) $psr7Request->getUri(),
                    ]);
                });
            }
        }
    }
}
