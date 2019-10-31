<?php

namespace Viviniko\Media;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use League\Flysystem\Filesystem;
use OSS\OssClient;
use Viviniko\Media\Console\Commands\MediaTableCommand;
use Viviniko\Media\Models\File;
use Viviniko\Media\Observers\FileObserver;
use Viviniko\Media\Storages\Oss\AliOssAdapter;
use Viviniko\Media\Storages\Oss\PutFile;
use Viviniko\Media\Storages\Oss\PutRemoteFile;

class MediaServiceProvider extends BaseServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        // Publish config files
        $this->publishes([
            __DIR__.'/../config/media.php' => config_path('media.php'),
        ]);

        // Register commands
        $this->commands('command.media.table');

        Storage::extend('oss', function($app, $config)
        {
            $accessId  = $config['access_id'];
            $accessKey = $config['access_key'];

            $cdnDomain = empty($config['cdnDomain']) ? '' : $config['cdnDomain'];
            $bucket    = $config['bucket'];
            $ssl       = empty($config['ssl']) ? false : $config['ssl'];
            $debug     = empty($config['debug']) ? false : $config['debug'];
            $endPoint  = $config['endpoint'];

            $adapter = new AliOssAdapter(
                (empty($accessId) || empty($accessKey) || empty($endPoint)) ?
                    null :
                    new OssClient($accessId, $accessKey, $endPoint, !empty($cdnDomain) && $cdnDomain == $endPoint),
                $bucket, $endPoint, $ssl, $debug, $cdnDomain
            );
            $filesystem =  new Filesystem($adapter);
            $filesystem->addPlugin(new PutFile());
            $filesystem->addPlugin(new PutRemoteFile());
            //$filesystem->addPlugin(new CallBack());
            return $filesystem;
        });

        File::observe(FileObserver::class);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/media.php', 'media');

        $this->registerRepositories();

        $this->registerServices();

        $this->registerCommands();
    }

    /**
     * Register the artisan commands.
     *
     * @return void
     */
    private function registerCommands()
    {
        $this->app->singleton('command.media.table', function ($app) {
            return new MediaTableCommand($app['files'], $app['composer']);
        });
    }

    protected function registerRepositories()
    {
        $this->app->singleton(
            \Viviniko\Media\Repositories\FileRepository::class,
            \Viviniko\Media\Repositories\EloquentFile::class
        );
    }

    /**
     * Register the media service provider.
     *
     * @return void
     */
    protected function registerServices()
    {
        $this->app->singleton(
            \Viviniko\Media\Services\ImageService::class,
            \Viviniko\Media\Services\Impl\ImageServiceImpl::class
        );
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            \Viviniko\Media\Services\ImageService::class,
        ];
    }
}