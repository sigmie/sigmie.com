<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Laravel AI SDK creates agent_conversations.user_id and
 * agent_conversation_messages.user_id as bigint (foreignId).
 * Agent-tools uses string user tokens (e.g. "demo-user", UUIDs).
 * This migration converts both columns to nullable varchar(255).
 */
return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            return;
        }

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE agent_conversations ALTER COLUMN user_id TYPE VARCHAR(255) USING user_id::TEXT');
            DB::statement('ALTER TABLE agent_conversation_messages ALTER COLUMN user_id TYPE VARCHAR(255) USING user_id::TEXT');

            return;
        }

        // MySQL / MariaDB
        Schema::table('agent_conversations', function ($table) {
            $table->string('user_id')->nullable()->change();
        });

        Schema::table('agent_conversation_messages', function ($table) {
            $table->string('user_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            return;
        }

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE agent_conversations ALTER COLUMN user_id TYPE BIGINT USING user_id::BIGINT');
            DB::statement('ALTER TABLE agent_conversation_messages ALTER COLUMN user_id TYPE BIGINT USING user_id::BIGINT');

            return;
        }

        Schema::table('agent_conversations', function ($table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
        });

        Schema::table('agent_conversation_messages', function ($table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
        });
    }
};
