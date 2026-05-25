<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Docs chat agent — public abuse protection
    |--------------------------------------------------------------------------
    |
    | The chat endpoint is public, so cost is bounded on three axes:
    |   - per-IP throttle (burst + sustained) via the `agent-chat` limiter
    |   - a global daily message budget enforced in DocsChatController
    |   - per-request output + history caps to keep each call cheap
    |
    */

    'chat' => [
        // Master switch — the chat is opt-in and OFF by default. Set
        // AGENT_CHAT_ENABLED=true to expose the widget + endpoints.
        'enabled' => (bool) env('AGENT_CHAT_ENABLED', false),

        // Per-IP throttle (see RateLimiter::for('agent-chat') in AppServiceProvider).
        'per_minute' => (int) env('AGENT_CHAT_PER_MINUTE', 6),
        'per_hour' => (int) env('AGENT_CHAT_PER_HOUR', 30),

        // Global daily message budget across ALL users. Protects the Anthropic
        // bill even under distributed abuse. Resets at midnight (app timezone).
        'daily_budget' => (int) env('AGENT_CHAT_DAILY_BUDGET', 2000),

        // Per-request caps.
        'max_output_tokens' => (int) env('AGENT_CHAT_MAX_OUTPUT_TOKENS', 600),
        'max_history_messages' => (int) env('AGENT_CHAT_MAX_HISTORY_MESSAGES', 6),

        // Reject prompts longer than this (characters) before hitting the model.
        'max_prompt_chars' => (int) env('AGENT_CHAT_MAX_PROMPT_CHARS', 2000),
    ],

];
