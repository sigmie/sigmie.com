<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('agent_conversation_messages')) {
            return;
        }

        Schema::table('agent_conversation_messages', function (Blueprint $table) {
            $table->json('topic')->nullable()->after('meta');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('agent_conversation_messages')) {
            return;
        }

        Schema::table('agent_conversation_messages', function (Blueprint $table) {
            $table->dropColumn('topic');
        });
    }
};
