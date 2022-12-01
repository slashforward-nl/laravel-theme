<?php namespace Igaster\LaravelTheme;

use Igaster\LaravelTheme\Commands\CreateJsonTranslations;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

class ThemeServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('slashforward.themes', function ($app) {
            return new Themes();
        });

        $this->app->singleton('view.finder', function ($app) {
            return new \Igaster\LaravelTheme\ThemeViewFinder(
                $app['files'],
                $app['config']['view.paths'],
                null
            );
        });

        $this->app->singleton('translation.loader', function ($app) {
            return new \Igaster\LaravelTheme\Translation\ThemeFileLoader(
                $app['files'], $app['path.lang']
            );
        });

        $this->app->singleton('translator', function ($app) {
            $loader = $app['translation.loader'];

            // When registering the translator component, we'll need to set the default
            // locale as well as the fallback locale. So, we'll grab the application
            // configuration so we can easily get both of these values from there.
            $locale = $app['config']['app.locale'];

            $trans = new \Igaster\LaravelTheme\Translation\ThemeTranslator($loader, $locale);

            $trans->setFallback($app['config']['app.fallback_locale']);

            return $trans;
        });

        require_once 'helpers.php';
    }

    public function boot()
    {
        /*--------------------------------------------------------------------------
        | Initialize Themes
        |--------------------------------------------------------------------------*/
        $themes = app()->make('slashforward.themes');
        $themes->scan();

        /*--------------------------------------------------------------------------
        | Activate default theme
        |--------------------------------------------------------------------------*/
        if (!$themes->current() && \Config::get('themes.default')) {
            $themes->set(\Config::get('themes.default'));
        }

        /*--------------------------------------------------------------------------
        | Pulish configuration file
        |--------------------------------------------------------------------------*/
        $this->publishes([
            __DIR__ . '/Config/themes.php' => config_path('themes.php'),
        ], 'laravel-theme');

        /*--------------------------------------------------------------------------
        | Register Console Commands
        |--------------------------------------------------------------------------*/
        if ($this->app->runningInConsole()) {
            $this->commands([
                CreateJsonTranslations::class,
        //         createTheme::class,
        //         removeTheme::class,
        //         createPackage::class,
        //         installPackage::class,
        //         refreshCache::class,
            ]);
        }

    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['slashforward.themes'];
    }
}
