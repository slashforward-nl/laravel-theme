<?php namespace Slashforward\LaravelTheme;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Env;

class Themes
{
    protected $themesPath;
    protected $activeTheme = null;
    protected $themes = [];
    protected $defaultViewPaths;
    protected $defaultLangPath;
    protected $cachePath;

    public function __construct()
    {
        $this->defaultViewPaths = config('view.paths');
        $this->defaultLangPath = app()->langPath();
        $this->themesPath = config('themes.themes_path', null) ?: config('view.paths')[0];
        $this->cachePath = base_path('bootstrap/cache/themes.php');
    }

    /**
     * Return $filename path located in themes folder
     *
     * @param  string $filename
     * @return string
     */
    public function themes_path($filename = null)
    {
        return $filename ? $this->themesPath . '/' . $filename : $this->themesPath;
    }

    /**
     * Return list of registered themes
     *
     * @return array
     */
    public function all()
    {
        return $this->themes;
    }

    /**
     * Check if @themeName is registered
     *
     * @return bool
     */
    public function exists($themeName)
    {
        // dd($this->themes);
        // dump()

        return array_key_exists($themeName, $this->themes);
    }

    /**
     * Enable $themeName & set view paths
     *
     * @return Theme
     */
    public function set($themeName)
    {
        // Find theme or throw exception
        $theme = $this->find($themeName);

        // If active theme is already set theme no need to redo.
        if(!empty($activeTheme) && $theme->name == $this->current()->name) {
            return $theme;
        }

        // Set the active theme
        $this->activeTheme = $theme;

        // Get theme view paths
        $viewPaths = $theme->getViewPaths($this->defaultViewPaths);

        // Change the paths in config for further usage
        config(['view.paths' => $viewPaths]);

        // Change the paths in the view finder so we use the right files
        $themeViewFinder = app('view.finder');
        $themeViewFinder->setPaths($viewPaths)->flush();

        // Change the language path if it is set in the config.
        $langPath = $theme->getLangPath($this->defaultLangPath);
        app()->useLangPath($langPath);

        // Dispatch event so user can hook in and trigger actions
        Event::dispatch('Slashforward.laravel-theme.change', $theme);

        return $theme;
    }

    /**
     * Get current theme
     *
     * @return Theme
     */
    public function current() : Theme|null
    {
        return $this->activeTheme ? $this->activeTheme : null;
    }

    /**
     * Get current theme's name
     *
     * @return string
     */
    public function get()
    {
        return $this->current() ? $this->current()->name : '';
    }

    /**
     * Find a theme by it's name
     *
     * @return Theme
     */
    public function find($themeName)
    {
        // Search for registered themes
        if (array_key_exists($themeName, $this->themes)) {
            return $this->themes[$themeName];
        }

        throw new Exceptions\ThemeNotFound($themeName);
    }

    /**
     * Register a new theme
     *
     * @return Theme
     */
    public function add(Theme $theme)
    {
        if ($this->exists($theme->name)) {
            throw new Exceptions\ThemeAlreadyExists($theme);
        }

        $this->themes[$theme->name] = $theme;

        return $theme;
    }

    // Original view paths defined in config.view.php
    public function getDefaultViewPaths()
    {
        return $this->defaultViewsPath;
    }

    public function getDefaultLangPath()
    {
        return $this->defaultLangPath;
    }

    /**
     * Scan all folders inside the themes path & config/themes.php
     */
    public function scan()
    {
        $themesConfig = config('themes.themes', []);

        // Add themes from config/themes.php
        foreach ($themesConfig as $themeName => $themeConfig) {

            // Is it an element with no values?
            if (is_string($themeConfig)) {
                $themeName = $themeConfig;
                $themeConfig = [];
            }

            // Create new or Update existing?
            $theme = (!$this->exists($themeName)) ?
                new Theme($themeName) : 
                $this->find($themeName);
            
            if(isset($themeConfig['slug'])) 
                $theme->slug = $themeConfig['slug'];

            if(isset($themeConfig['public-path']))
                $theme->publicPath = $themeConfig['public-path'];

            if(isset($themeConfig['views-path']))
                $theme->viewsPath = $themeConfig['views-path'];

            if(isset($themeConfig['lang-path']))
                $theme->langPath = $themeConfig['lang-path'];
            
            if(isset($themeConfig['extends']))
                $theme->extends = $themeConfig['extends'];

            // $parentThemes[$themeName] = $themeConfig['extends'] ?: null;

            $theme->loadSettings(array_merge($theme->settings, $themeConfig));

            $this->add($theme);
            // dd($theme);
        }

        // All themes are loaded. Now we can assign the parents to the child-themes
        foreach ($this->themes as $themeName => $theme) {
            // $child = $this->find($childName);
            if($theme->extends) {
                // Find parent theme, not found will throw error
                $parentTheme = $this->find($theme->extends);

                $theme->setParent($parentTheme);
            }
        }
    }

    /*--------------------------------------------------------------------------
    | Proxy to current theme
    |--------------------------------------------------------------------------*/

    // Return url of current theme
    public function url($filename)
    {
        // If no Theme set, return /$filename
        if (!$this->current()) {
            return "/" . ltrim($filename, '/');
        }

        return $this->current()->url($filename);
    }

    /**
     * Act as a proxy to the current theme. Map theme's functions to the Themes class. (Decorator Pattern)
     */
    public function __call($method, $args)
    {
        // dd($method);
        if (($theme = $this->current())) {
            return call_user_func_array([$theme, $method], $args);
        }
        // } else {
            // throw new \Exception("No theme is set. Can not execute method [$method] in [" . self::class . "]", 1);
        // }
    }

    /*--------------------------------------------------------------------------
    | Blade Helper Functions
    |--------------------------------------------------------------------------*/

    /**
     * Return css link for $href
     *
     * @param  string $href
     * @return string
     */
    public function css($href)
    {
        return sprintf('<link media="all" type="text/css" rel="stylesheet" href="%s">', $this->url($href));
    }

    /**
     * Return script link for $href
     *
     * @param  string $href
     * @return string
     */
    public function js($href)
    {
        return sprintf('<script src="%s"></script>', $this->url($href));
    }

    /**
     * Return img tag
     *
     * @param  string $src
     * @param  string $alt
     * @param  string $Class
     * @param  array $attributes
     * @return string
     */
    public function img($src, $alt = '', $class = '', $attributes = [])
    {
        return sprintf('<img src="%s" alt="%s" class="%s" %s>',
            $this->url($src),
            $alt,
            $class,
            $this->HtmlAttributes($attributes)
        );
    }

    /**
     * Return attributes in html format
     *
     * @param  array $attributes
     * @return string
     */
    private function HtmlAttributes($attributes)
    {
        $formatted = join(' ', array_map(function ($key) use ($attributes) {
            if (is_bool($attributes[$key])) {
                return $attributes[$key] ? $key : '';
            }
            return $key . '="' . $attributes[$key] . '"';
        }, array_keys($attributes)));
        return $formatted;
    }

}
