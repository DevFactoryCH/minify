<?php namespace Devfactory\Minify;

use Illuminate\Support\ServiceProvider;

class MinifyServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application configuration.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/config/minify.php' => config_path('minify.php'),
        ], 'config');
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        // Register container binding
        $this->app->singleton('minify', function ($app) {
            return new Minify(
                [
                    'css_build_path' => config('minify.css_build_path'),
                    'css_url_path' => config('minify.css_url_path'),
                    'js_build_path' => config('minify.js_build_path'),
                    'js_url_path' => config('minify.js_url_path'),
                    'ignore_environments' => config('minify.ignore_environments'),
                    'base_url' => config('minify.base_url'),
                    'reverse_sort' => config('minify.reverse_sort'),
                    'disable_mtime' => config('minify.disable_mtime'),
                    'hash_salt' => config('minify.hash_salt'),
                ],
                $app->environment()
            );
        });

        // Merge config with config from application
        $this->mergeConfigFrom(
            __DIR__ . '/config/minify.php', 'minify'
        );
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return ['minify'];
    }
}
