<?php

declare(strict_types=1);

namespace Sigmie\AgentTools\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Sigmie\AgentTools\Laravel\AgentDebugRepository;

/**
 * Returns persisted agent debug turns (system prompt, unified search, tool calls).
 *
 * Query: {@code conversation_id} optional — defaults to the active conversation in session.
 * Storage is keyed by authenticated user + conversation id so turns survive full page reloads.
 */
class AgentDebugController extends Controller
{
    public function __construct(
        private readonly AgentDebugRepository $debugRepo,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        return response()->json([
            'turns' => $this->debugRepo->all($request),
        ]);
    }
}
