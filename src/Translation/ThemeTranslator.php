<?php

namespace Slashforward\LaravelTheme\Translation;

use Illuminate\Translation\Translator;
use Slashforward\LaravelTheme\Theme;

class ThemeTranslator extends Translator
{
    /**
     * The array of loaded translation groups.
     *
     * @var array
     */
    protected $themeLoaded = [];

     /**
     * Load the specified language group.
     *
     * @param  string  $namespace
     * @param  string  $group
     * @param  string  $locale
     * @return void
     */
    public function loadTheme(Theme $theme, $key)
    {
        $themeLoaded = [];

        $locale = $this->locale();
        [$namespace, $group, $item] = $this->parseKey($key);

        // We already loaded this theme, load it into loaded
        if ($this->isThemeLoaded($theme, $namespace, $group, $locale))
        {
            $themeLoaded = $this->themeLoaded[$theme->getSlug()][$namespace][$group][$locale];
        }

        if(empty($themeLoaded))
        {
            \Arr::forget($this->loaded, "$namespace.$group.$locale");
        }
        else {
            \Arr::set($this->loaded, "$namespace.$group.$locale", $themeLoaded); 
        }

        // Path veranderen zodat we in de juiste directory kijken
        $this->getLoader()->setPath(
            $theme->getLangPath(app()->basePath('lang'))
        );

        // The loader is responsible for returning the array of language lines for the
        // given namespace, group, and locale. We'll set the lines in this array of
        // lines that have already been loaded so that we can easily access them.
        $lines = $this->loader->load($locale, $group, $namespace);

        \Arr::set($this->themeLoaded, "{$theme->getSlug()}.$namespace.$group.$locale", $lines);
    }

    public function getRawLine($key) {
        $locale = $this->locale();
        [$namespace, $group, $item] = $this->parseKey($key);
        
        return \Arr::get($this->loaded, "$namespace.$group.$locale.$item");
    }

    /**
     * Determine if the given group has been loaded.
     *
     * @param  string  $namespace
     * @param  string  $group
     * @param  string  $locale
     * @return bool
     */
    protected function isThemeLoaded(Theme $theme, $namespace, $group, $locale)
    {
        return isset($this->themeLoaded[$theme->getSlug()][$namespace][$group][$locale]);
    }
}
