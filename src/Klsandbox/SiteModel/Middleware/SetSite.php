<?php

namespace Klsandbox\SiteModel\Middleware;

use Closure;
use Klsandbox\SiteModel\Site;

class SetSite
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $host = $request->getHost();
        $site = Site::setSiteByHost($host);

        $response = $next($request);

        return $response;
    }
}
