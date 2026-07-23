<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Guarantor extends Model
{
    protected $fillable = [
        'employee_id',
        'name',
        'phone',
        'email',
        'address',
        'nida_number',
        'relationship',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
