<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Training extends Model
{
    protected $table = 'trainings';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = ['training_date' => 'date', 'mandatory' => 'boolean'];

    public function attendees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'training_attendance', 'training_id', 'employee_id')
            ->withPivot('attended');
    }
}
