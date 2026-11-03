<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $table = 'audit_log';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = ['created_at' => 'datetime'];

    public static function record(?string $actor, ?string $role, string $action, ?string $ref, string $detail = ''): void
    {
        static::create([
            'actor' => $actor,
            'actor_role' => $role,
            'action' => $action,
            'ref' => $ref,
            'detail' => $detail,
            'created_at' => now(),
        ]);
    }
}
