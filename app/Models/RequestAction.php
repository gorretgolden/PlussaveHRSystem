<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequestAction extends Model
{
    protected $table = 'request_actions';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = ['acted_at' => 'datetime'];
}
