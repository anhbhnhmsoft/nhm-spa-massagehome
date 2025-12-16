<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeoCachingPlace extends Model
{
    protected $table = 'geo_caching_places';

    protected $fillable = [
        'place_id',
        'formatted_address',
        'keyword',
        'raw_data',
        'latitude',
        'longitude',
    ];

    protected $casts = [
        'raw_data' => 'array',
    ];
}
