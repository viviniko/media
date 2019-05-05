<?php

namespace Viviniko\Media;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Viviniko\Media\Console\Commands\MediaTableCommand;

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

        $this->registerMediaService();

        $this->registerFileService();

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
    protected function registerMediaService()
    {
        $this->app->singleton(
            \Viviniko\Media\Services\ImageService::class,
            \Viviniko\Media\Services\Impl\ImageServiceImpl::class
        );
    }

    private function registerFileService()
    {
        $this->app->singleton(
            \Viviniko\Media\Services\FileService::class,
            \Viviniko\Media\Services\Impl\FileServiceImpl::class
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
            \Viviniko\Media\Services\FileService::class,
        ];
    }
}