<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteContent extends Model
{
    protected $fillable = ['section', 'key', 'value', 'type'];

    protected $casts = [
        'value' => 'string',
    ];

    public static function getBySection(string $section)
    {
        return static::where('section', $section)->get()->pluck('value', 'key');
    }

    public static function getAllGrouped()
    {
        return static::all()->groupBy('section')->map(function ($items) {
            return $items->pluck('value', 'key');
        });
    }
}
