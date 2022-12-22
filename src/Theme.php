<?php namespace Slashforward\LaravelTheme;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Arr;

class Theme
{
    public $name;
    public $slug;
    public $viewsPath;
    public $publicPath;
    public $langPath;
    public $extends;
    public $settings = [];

    /** @var Theme  */
    public $parent;

    public function __construct($name) {
        $this->name = $name; 
    }

    public function getName() {
        return $this->name;
    }

    public function getSlug()
    {
        if($this->parent && is_null($this->slug)) {
            return $this->parent->getSlug();
        }

        return ($this->slug ?? $this->name);
    }

    public function getPublicPath() {
        return $this->publicPath ?: $this->getSlug();
    }

    public function getLangPath($defaultPath) {
        $langPath = $this->langPath ?: $defaultPath . DIRECTORY_SEPARATOR . $this->getSlug();

        // Check in parent theme is folder does not exist
        if (!\Storage::exists($langPath) && !empty($parent = $this->getParent()) ) {
            $langPath = $parent->getLangPath($defaultPath);
        }

        return $langPath;
    }

    public function getViewPath() {
        return $this->viewPath ?? ($this->slug ?? $this->name);
    }

    public function getViewPaths($defaultPaths = [])
    {
        // Build Paths array.
        // All paths are relative to Config::get('theme.theme_path')
        $paths = [];
        $theme = $this;

        do {
            if (substr($theme->getViewPath(), 0, 1) === DIRECTORY_SEPARATOR) {
                $path = base_path(substr($theme->getViewPath(), 1));
            } else {
                $path = themes_path($theme->getViewPath());
            }

            if (!in_array($path, $paths)) {
                $paths[] = $path;
            }

        } while ($theme = $theme->parent);

        // fall-back to default paths (set in views.php config file)
        foreach ($defaultPaths as $path) {
            if (!in_array($path, $paths)) {
                $paths[] = $path;
            }
        }

        // dd($paths);
        
        return $paths;
    }

    public function url($url)
    {
        // return external URLs unmodified
        if (preg_match('/^((http(s?):)?\/\/)/i', $url)) {
            return $url;
        }

        $url = ltrim($url, '/');

        // Is theme folder located on the web (ie AWS)? Dont lookup parent themes...
        if (preg_match('/^((http(s?):)?\/\/)/i', $this->getPublicPath())) {
            return $this->getPublicPath() . '/' . $url;
        }

        // Check for valid {xxx} keys and replace them with the Theme's configuration value (in themes.php)
        preg_match_all('/\{(.*?)\}/', $url, $matches);
        foreach ($matches[1] as $param) {
            if (($value = $this->getSetting($param)) !== null) {
                $url = str_replace('{' . $param . '}', $value, $url);
            }
        }

        // Seperate url from url queries
        if (($position = strpos($url, '?')) !== false) {
            $baseUrl = substr($url, 0, $position);
            $params = substr($url, $position);
        } else {
            $baseUrl = $url;
            $params = '';
        }

        
        // Lookup asset in current's theme public path
        $fullUrl = (empty($this->getPublicPath()) ? '' : '/') . $this->getPublicPath() . '/' . $baseUrl;

        if (file_exists($fullPath = public_path($fullUrl))) {
            return $fullUrl . $params;
        }

        // If not found then lookup in parent's theme public path
        if ($parentTheme = $this->getParent()) {
            return $parentTheme->url($url);
        }
        // No parent theme? Lookup in the public folder.
        else {
            if (file_exists(public_path($baseUrl))) {
                return "/" . $baseUrl . $params;
            }
        }

        // Asset not found at all. Error handling
        $action = Config::get('themes.asset_not_found', 'LOG_ERROR');

        if ($action == 'THROW_EXCEPTION') {
            throw new Exceptions\themeException("Asset not found [$url]");
        } elseif ($action == 'LOG_ERROR') {
            Log::warning("Asset not found [$url] in Theme [" . $this->name . "]");
        } else {
            // themes.asset_not_found = 'IGNORE'
            return '/' . $url;
        }
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function setParent(Theme $parent)
    {
        $this->parent = $parent;
    }
    
    /*--------------------------------------------------------------------------
    | Theme Translations
    |--------------------------------------------------------------------------*/

    public function getTranslation($key, $values = []) {
        $translator = app('translator');
        $translator->loadTheme($this, $key);

        if (\Lang::has($key)) {
            return \Lang::get($key, $values);
        } elseif ($parent = $this->getParent()) {
            return $parent->getTranslation($key, $values);
        } else {
            return $key;
        }
    }

    public function getRawTranslation($key) {
        $translator = app('translator');
        $translator->loadTheme($this, $key);

        if (\Lang::has($key)) {
            return $translator->getRawLine($key);
        } elseif ($parent = $this->getParent()) {
            return $parent->getTranslation($key);
        } else {
            return $key;
        }
    }

    /*--------------------------------------------------------------------------
    | Theme Settings
    |--------------------------------------------------------------------------*/

    public function setSetting($key, $value)
    {
        $this->settings[$key] = $value;
    }

    public function getSetting($key, $default = null)
    {
        if (Arr::has($this->settings,$key)) {
            return Arr::get($this->settings,$key);
        } elseif ($parent = $this->getParent()) {
            return $parent->getSetting($key, $default);
        } else {
            return $default;
        }
    }

    public function loadSettings($settings = [])
    {
        $this->settings = array_diff_key((array) $settings, array_flip([
            'name',
            'slug',
            'extends',
            'views-path',
            'public-path',
            'lang-path',
        ]));
    }

}
