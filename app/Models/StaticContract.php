<?php

namespace App\Models;

use App\Core\GenerateId\HasBigIntId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class StaticContract extends Model
{
    use SoftDeletes, HasBigIntId, HasTranslations;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'static_contract';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'type',
        'path',
        'note',
        'slug',
    ];

    public array $translatable = [
        'note',
        'path',
    ];
}
