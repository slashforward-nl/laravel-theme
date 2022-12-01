<?php namespace Igaster\LaravelTheme\Translation;

use Igaster\LaravelTheme\Facades\Theme;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Translation\FileLoader;

class ThemeFileLoader extends FileLoader
{
    /**
     * The default path for the loader.
     *
     * @var string
     */
    protected $initialPath;

    /**
     * Create a new file loader instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @param  string  $path
     * @return void
     */
    public function __construct(Filesystem $files, $path)
    {
        $this->initialPath = $path;

        parent::__construct($files, $path);
    }

    public function getIntitialPath() {
        return $this->initialPath;
    }

    public function setPath($path) {
        $this->path = $path;

        // [$namespace, $group, $item] = \Lang::parseKey($key);

        // dd($namespace);

        // $this->loadPath($path, 'en', $group);

        // app('translator')->setLoaded([]);
        // parent::setLoaded([]);
    }
}
