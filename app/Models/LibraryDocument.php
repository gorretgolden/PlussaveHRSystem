<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LibraryDocument extends Model
{
    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = ['approved_date' => 'date', 'acknowledgements' => 'integer'];
}
