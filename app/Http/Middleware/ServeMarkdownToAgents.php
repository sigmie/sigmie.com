<?php

namespace App\Http\Middleware;

use App\Http\Controllers\LlmsController;
use App\Services\Documentation;
use Closure;
use Illuminate\Http\Request;
use League\CommonMark\MarkdownConverter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Content negotiation for agents: a client that prefers text/markdown over
 * text/html gets the page as markdown at the same URL (the home page answers
 * with llms.txt). Browsers and Inertia visits prefer HTML and pass through.
 * Every response varies on Accept so a cache never mixes the two.
 */
class ServeMarkdownToAgents
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $request->prefers(['text/html', 'text/markdown']) === 'text/markdown'
            ? $this->markdown($request) ?? $next($request)
            : $next($request);

        $response->headers->set('Vary', 'Accept', false);

        return $response;
    }

    private function markdown(Request $request): ?Response
    {
        $converter = app(MarkdownConverter::class);

        if ($request->is('/')) {
            return app(LlmsController::class)($converter);
        }

        if (! preg_match('#^docs/([^/]+)/([^/]+)$#', $request->path(), $matches)) {
            return null;
        }

        $markdown = (new Documentation($converter))->agentPage($matches[1], $matches[2]);

        return $markdown === null ? null : response($markdown, 200, [
            'Content-Type' => 'text/markdown; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
