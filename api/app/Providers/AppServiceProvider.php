<?php

namespace App\Providers;

use App\Services\NoteRepository;
use Aws\DynamoDb\DynamoDbClient;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DynamoDbClient::class, function () {
            $config = [
                'version' => 'latest',
                'region'  => env('AWS_REGION', 'ap-northeast-1'),
            ];

            if ($endpoint = env('DYNAMODB_ENDPOINT')) {
                $config['endpoint'] = $endpoint;
                $config['credentials'] = [
                    'key'    => env('AWS_ACCESS_KEY_ID', 'local'),
                    'secret' => env('AWS_SECRET_ACCESS_KEY', 'local'),
                ];
            }

            return new DynamoDbClient($config);
        });

        $this->app->singleton(NoteRepository::class, function ($app) {
            return new NoteRepository(
                $app->make(DynamoDbClient::class),
                env('DYNAMODB_TABLE', 'burnnote'),
            );
        });
    }

    public function boot(): void
    {
        //
    }
}
