<?php namespace Devfactory\Minify;

use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;

class MinifyServiceProvider extends ServiceProvider {

  /**
   * Indicates if loading of the provider is deferred.
   *
   * @var bool
   */
  protected $defer = false;

  /**
   * Bootstrap the application events.
   *
   * @return void
   */
  public function boot()
  {
    $this->publishConfig();
  }

  /**
   * Register the service provider.
   *
   * @return void
   */
  public function register()
  {

    $this->registerServices();
    $this->mergeConfig();

  }

  /**
   * Register the package services.
   *
   * @return void
   */
  protected function registerServices() {
    $this->app->singleton('minify', function ($app) {
      return new Minify(
        array(
          'css_build_path' => config('minify.css_build_path'),
          'css_url_path' => config('minify.css_url_path'),
          'js_build_path' => config('minify.js_build_path'),
          'js_url_path' => config('minify.js_url_path'),
          'ignore_environments' => config('minify.ignore_environments'),
          'base_url' => config('minify.base_url'),
          'reverse_sort' => config('minify.reverse_sort'),
          'disable_mtime' => config('minify.disable_mtime'),
          'hash_salt' => config('minify.hash_salt'),
        ),
        $app->environment()
      );
    });
  }

  /**
   * Publish the package configuration
   */
  protected function publishConfig() {
    $this->publishes([
       __DIR__ . '/config/config.php' => config_path('minify.php'),
    ]);
  }

  /**
   * Merge media config with users.
   */
  private function mergeConfig() {
    $this->mergeConfigFrom(
      __DIR__ . '/config/config.php', 'minify'
    );
  }

  /**
   * Get the services provided by the provider.
   *
   * @return array
   */
  public function provides()
  {
    return array('minify');
  }
}
