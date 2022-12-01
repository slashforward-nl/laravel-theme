<?php namespace Igaster\LaravelTheme\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetThemeByDomain
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
        if(\Theme::exists($request->getHost())) {
            \Theme::set($request->getHost());
        }

        return $next($request);
    }
}
