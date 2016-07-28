<?php

namespace Klsandbox\SiteModel;

use Illuminate\Support\ServiceProvider;

class SiteModelServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('command.klsandbox.siteappend', function ($app) {
            return new SiteAppend();
        });

        $this->commands('command.klsandbox.siteappend');

        $models = \Config::get('site.models');

        if (!$models) {
            \Log::warning('No models configured');

            return;
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'command.klsandbox.siteappend',
        ];
    }

    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../../../database/migrations/' => database_path('/migrations'),
                ], 'migrations');

        $this->publishes([
            __DIR__ . '/../../../config/' => config_path(),
                ], 'config');

        foreach (config('site.models') as $siteClass) {
            if (!class_exists($siteClass)) {
                \App::abort(500, 'Class not found');
            }
        }
    }
}
