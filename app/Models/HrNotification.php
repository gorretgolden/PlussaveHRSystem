<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HrNotification extends Model
{
    protected $table = 'notifications';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = ['is_read' => 'boolean', 'created_at' => 'datetime'];
}
