<?php

namespace App\Models;

use App\Support\GeneratesRefs;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HrRequest extends Model
{
    use GeneratesRefs;

    protected const REF_PREFIX = 'R-';

    protected const REF_MIN = 1031;

    protected $table = 'requests';

    protected $guarded = [];

    protected $casts = [
        'fields' => 'array',
        'payload' => 'array',
        'chain' => 'array',
        'stage' => 'integer',
    ];

    public function actions(): HasMany
    {
        return $this->hasMany(RequestAction::class, 'request_id')->orderBy('acted_at')->orderBy('id');
    }
}
