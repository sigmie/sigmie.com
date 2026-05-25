<?php

declare(strict_types=1);

namespace Sigmie\AgentTools\Laravel\Models;

use Illuminate\Database\Eloquent\Model;

class AgentUserMemory extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'user_id', 'fact', 'category', 'topic'];

    protected $table = 'agent_user_memory';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'topic' => 'array',
        ];
    }
}
