<?php 

namespace Slashforward\LaravelTheme\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
 
class CreateJsonTranslations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'theme:create-json-translations';
 
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a marketing email to a user';
 
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }
 
    /**
     * Execute the console command.
     *
     * @param  \App\Support\DripEmailer  $drip
     * @return mixed
     */
    public function handle()
    {
        $themes = \Theme::all();
        $themesCollection = collect([]);
        // $theme_indexes = array_keys($themes);

        $index = 0;
        foreach($themes as $theme) {
            $themesCollection->push([
                'index' => $index,
                'slug' => $theme->getSlug(),
                'name' => $theme->getName(),
                'parent' => $theme->getParent() ? $theme->getParent()->getName() : '',
            ]);

            $index++;
        }

        $this->table([
            'Index',
            'Slug',
            'Name',
            'Parent',
        ], $themesCollection);

        $index = $this->ask('What theme do you want to translate (index)');
        $locale = $this->ask('What language do you want to translate (nl,en,fr,de,etc..)');

        // $index = 0;
        // $locale = "nl";

        $theme = \Theme::set($themesCollection[$index]['name']);

        $path = $theme->getLangPath(\Theme::getDefaultLangPath());
        $fullPath = $path.DIRECTORY_SEPARATOR.$locale;

        $files = \File::allFiles($fullPath);

        // dd($files);

        $array = [];
        foreach ($files as $file) {
            if($file->getExtension() != "php") {
                continue;
            }

            $pathInfo = pathinfo($file);

            $extendedDir = str_replace($fullPath, "", $pathInfo['dirname']);
            $baseName = str_replace('.php', '', $pathInfo['filename']);
            
            $key = str_replace("/", ".", ltrim("$extendedDir.$baseName", "/."));

            $contents = require $file;
            $contents = Arr::undot($contents);

            // $part = $fullPath

            Arr::set($array, $key, $contents);
        }

        // Stukje om alles volledig plat te slaan.
        $ritit = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($array));
        $result = array();
        foreach ($ritit as $leafValue) {
            $keys = array();
            foreach (range(0, $ritit->getDepth()) as $depth) {
                $keys[] = $ritit->getSubIterator($depth)->key();
            }
            $result[ join('.', $keys) ] = $leafValue;
        }

        // dd($array);

        $filePath = sprintf('%s.json', $fullPath);

        // dump($filePath);

        dump(File::put($filePath, json_encode($result, JSON_PRETTY_PRINT)));

            // $storage->put($filePath, json_encode($array, JSON_PRETTY_PRINT));
        

        // dd($array);

        // $path = resource_path("lang/$locale");


        // $drip->send(User::find($this->argument('user')));
    }
}