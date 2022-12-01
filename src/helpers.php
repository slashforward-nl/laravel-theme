<?php
if (!function_exists('themes_path')) {

    function themes_path($filename = null)
    {
        return app('slashforward.themes')->themes_path($filename);
    }
}

if (!function_exists('theme_url')) {

    function theme_url($url)
    {
        return app('slashforward.themes')->url($url);
    }

}

// public static function __(string $string, $vars = array())
// {
//     return self::trans($string, $vars);
// }

if (!function_exists('theme_trans')) {

    function theme_trans(string $key, $vars = []) : string
    {
        
    }
}