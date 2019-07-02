<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Elasticsearch Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the elasticsearch connections below
    | you wish to use as your default connection for all elasticsearch instance.
    | Of course you may use many connections at once using
    | the Elasticsearch library.
    |
    */

    'default' => env('ELASTICSEARCH_CONNECTION', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Elasticsearch Connections
    |--------------------------------------------------------------------------
    |
    | Here are each of the elasticsearch connections setup for your application.
    |
    */

    'connections' => [

        'default' => [

            /**
             * Hosts
             *
             * This is an array of hosts that the client will connect to. It can be a
             * single host, or an array if you are running a cluster of Elasticsearch
             * instances.
             *
             * This is the only configuration value that is mandatory.
             *
             * Presently using "extended" host configuration method
             *
             * @see https://www.elastic.co/guide/en/elasticsearch/client/php-api/2.0/_configuration.html#_extended_host_configuration
             *
             * There is also the shorter "inline" configuration method available
             *
             * @see https://www.elastic.co/guide/en/elasticsearch/client/php-api/2.0/_configuration.html#_inline_host_configuration
             */
            'hosts' => [
                [
                    'host' => env('ELASTICSEARCH_HOST', 'localhost'),
                    'port' => env('ELASTICSEARCH_PORT', 9200),
                    'scheme' => env('ELASTICSEARCH_SCHEME', null),
                    'user' => env('ELASTICSEARCH_USER', null),
                    'pass' => env('ELASTICSEARCH_PASS', null),

                    // If you are connecting to an Elasticsearch instance on AWS, you will need these values as well
                    'aws' => env('AWS_ELASTICSEARCH_ENABLED', false),
                    'aws_region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
                    'aws_key' => env('AWS_ACCESS_KEY_ID'),
                    'aws_secret' => env('AWS_SECRET_ACCESS_KEY'),
                ],
            ],

            /**
             * Logging
             *
             * Logging is handled by passing in an instance of Monolog\Logger (which
             * coincidentally is what Laravel's default logger is).
             *
             * @see https://www.elastic.co/guide/en/elasticsearch/client/php-api/2.0/_configuration.html#enabling_logger
             */

            'logging' => [
                'driver' => env('ELASTICSEARCH_LOG_DRIVER', null),

                'drivers' => [
                    'default' => [
                        'path' => storage_path('logs/elasticsearch.log'),
                        'level' => Monolog\Logger::INFO,
                    ],

                    'logger' => [
                        'channel' => env('ELASTICSEARCH_LOG_CHANNEL', 'daily'),
                    ],
                ],
            ],

            /**
             * SSL
             *
             * If your Elasticsearch instance uses an out-dated or self-signed SSL
             * certificate, you will need to pass in the certificate bundle.  This can
             * either be the path to the certificate file (for self-signed certs), or a
             * package like https://github.com/Kdyby/CurlCaBundle.  See the documentation
             * below for all the details.
             *
             * If you are using SSL instances, and the certificates are up-to-date and
             * signed by a public certificate authority, then you can leave this null and
             * just use "https" in the host path(s) above and you should be fine.
             *
             * @see https://www.elastic.co/guide/en/elasticsearch/client/php-api/2.0/_security.html#_ssl_encryption_2
             */

            'ssl_verification' => null,

            /**
             * Retries
             *
             * By default, the client will retry n times, where n = number of nodes in
             * your cluster. If you would like to disable retries, or change the number,
             * you can do so here.
             *
             * @see https://www.elastic.co/guide/en/elasticsearch/client/php-api/2.0/_configuration.html#_set_retries
             */

            'retries' => null,

             /**
             * Sniff On Start
             *
             * @see https://www.elastic.co/guide/en/elasticsearch/client/php-api/2.0/_configuration.html
             */

            'sniff_on_start' => false,

            /**
             * HTTP Handler
             *
             * @see https://www.elastic.co/guide/en/elasticsearch/client/php-api/2.0/_configuration.html#_configure_the_http_handler
             * @see http://ringphp.readthedocs.org/en/latest/client_handlers.html
             */

            'http_handler' => null,

            /**
             * Connection Pool
             *
             * @see https://www.elastic.co/guide/en/elasticsearch/client/php-api/2.0/_configuration.html#_setting_the_connection_pool
             * @see https://www.elastic.co/guide/en/elasticsearch/client/php-api/2.0/_connection_pool.html
             */

            'connection_pool' => null,

            /**
             * Connection Selector
             *
             * @see https://www.elastic.co/guide/en/elasticsearch/client/php-api/2.0/_configuration.html#_setting_the_connection_selector
             * @see https://www.elastic.co/guide/en/elasticsearch/client/php-api/2.0/_selectors.html
             */

            'connection_selector' => null,

            /**
             * Serializer
             *
             * @see https://www.elastic.co/guide/en/elasticsearch/client/php-api/2.0/_configuration.html#_setting_the_serializer
             * @see https://www.elastic.co/guide/en/elasticsearch/client/php-api/2.0/_serializers.html
             */

            'serializer' => null,

            /**
             * Connection Factory
             *
             * @see https://www.elastic.co/guide/en/elasticsearch/client/php-api/2.0/_configuration.html#_setting_a_custom_connectionfactory
             */

            'connection_factory' => null,

            /**
             * Endpoint
             *
             * @see https://www.elastic.co/guide/en/elasticsearch/client/php-api/6.0/_configuration.html#_set_the_endpoint_closure
             */

            'endpoint' => null,

            /**
             * Register additional namespaces
             *
             * An array of additional namespaces to register.
             *
             * @example 'namespaces' => [XPack::Security(), XPack::Watcher()]
             * @see https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/ElasticsearchPHP_Endpoints.html#Elasticsearch_ClientBuilderregisterNamespace_registerNamespace
             */

            'namespaces' => [],

            /**
             * Tracer
             *
             * Tracer is handled by passing in a name of the class implements Psr\Log\LoggerInterface.
             *
             * @see https://www.elastic.co/guide/en/elasticsearch/client/php-api/2.0/_configuration.html#_setting_a_custom_connectionfactory
             */

            'tracer' => null,
        ],

    ],
];
