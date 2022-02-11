<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetThemeFromDomain
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
            \Helper::setTheme($request->getHost());

            \SEOMeta::setTitleDefault(\Theme::getSetting('title'));
        }

        // Register broadcasting
        // app()->register(\Illuminate\Broadcasting\BroadcastServiceProvider::class);
        // app()->register(\App\Providers\BroadcastServiceProvider::class);

        return $next($request);
    }
}
