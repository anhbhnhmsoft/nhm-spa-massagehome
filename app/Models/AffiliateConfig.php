<?php

namespace App\Models;

use App\Core\GenerateId\HasBigIntId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AffiliateConfig extends Model
{
    use SoftDeletes, HasBigIntId;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'affiliate_configs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'target_role',
        'commission_rate',
        'min_commission',
        'max_commission',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'id' => 'string',
        'commission_rate' => 'decimal:2',
        'min_commission' => 'decimal:2',
        'max_commission' => 'decimal:2',
        'is_active' => 'boolean',
    ];
}
