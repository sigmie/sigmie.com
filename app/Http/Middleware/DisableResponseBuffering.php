<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tells nginx (and other reverse proxies) not to buffer the response, so
 * streamed agent tokens flush to the client immediately instead of being
 * held until the upstream closes — which otherwise surfaces as a 502.
 */
class DisableResponseBuffering
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Accel-Buffering', 'no');
        $response->headers->set('Cache-Control', 'no-cache, no-transform');

        return $response;
    }
}
