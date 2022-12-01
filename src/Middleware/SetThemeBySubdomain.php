<?php namespace Igaster\LaravelTheme\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetThemeBySubdomain
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // dd($request->url());
        preg_match('/(?:http[s]*\:\/\/)*(.*?)\.(?=[^\/]*\..{2,5})/i', $request->url(), $match);
        
        if(!is_null($match) && isset($match[1]) && \Theme::exists($match[1])) {
            \Helper::setTheme($match[1]);

            \SEOMeta::setTitleDefault(\Theme::getSetting('title'));
        }

        return $next($request);
    }
}
