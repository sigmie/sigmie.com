<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Contracts\Auth\Authenticatable;
use Sigmie\AgentTools\Http\Controllers\AgentChatController;
use Sigmie\AgentTools\Http\Controllers\AgentConversationsController;
use Sigmie\AgentTools\Http\Controllers\AgentDebugController;
use Sigmie\AgentTools\Laravel\ActiveConversationStore;
use Sigmie\AgentTools\Laravel\AgentDebugRepository;

$middleware = config('agent-tools.route_middleware', ['web', 'auth']);
$prefix = trim((string) config('agent-tools.route_prefix', 'sigmie'), '/');

$routes = function () {
    Route::get('/agent', function () {
        $title = config('agent-tools.agent_ui_title');

        return Inertia::render('SigmieAgent', [
            'csrf' => csrf_token(),
            'routePrefix' => (string) config('agent-tools.route_prefix', 'sigmie'),
            'agentTitle' => is_string($title) && $title !== ''
                ? $title
                : (string) config('app.name', 'Agent'),
            'agentSubtitle' => (string) config('agent-tools.agent_ui_subtitle', ''),
        ]);
    })->name('sigmie.agent');

    Route::post('/api/agent-chat', AgentChatController::class)->name('sigmie.agent.chat');

    Route::get('/api/agent-conversations', [AgentConversationsController::class, 'index'])->name('sigmie.agent.conversations.index');
    Route::get('/api/agent-conversations/{conversationId}/messages', [AgentConversationsController::class, 'messages'])
        ->name('sigmie.agent.conversations.messages');
    Route::post('/api/agent-conversations/active', [AgentConversationsController::class, 'setActive'])->name('sigmie.agent.conversations.active');

    Route::get('/api/agent-debug', AgentDebugController::class)->name('sigmie.agent.debug');

    Route::post('/api/agent-reset', function (Request $request) {
        $sessionKey = (string) config('agent-tools.session_conversation_key', 'agent_conversation_id');
        $request->session()->forget($sessionKey);

        $user = $request->user();
        if ($user instanceof Authenticatable) {
            app(ActiveConversationStore::class)->clear($user);
        }

        app(AgentDebugRepository::class)->clear($request);

        return response()->noContent();
    })->name('sigmie.agent.reset');
};

if ($prefix !== '') {
    Route::middleware($middleware)->prefix($prefix)->group($routes);
} else {
    Route::middleware($middleware)->group($routes);
}
