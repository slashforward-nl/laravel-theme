<?php namespace Slashforward\LaravelTheme\Middleware;

use Closure;
use Slashforward\LaravelTheme\Facades\Theme;

class SetThemeByName
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $themeName
     * @return mixed
     */
    public function handle($request, Closure $next, $themeName)
    {
        Theme::set($themeName);
        return $next($request);
    }
}
