<?php

namespace App\Models;

use App\Core\GenerateId\HasBigIntId;
use Illuminate\Database\Eloquent\Model;


class ServiceOption extends Model
{
    use HasBigIntId;

    protected $table = 'service_options';

    protected $fillable = [
        'service_id',
        'category_price_id',
    ];
    protected $casts = [
        'id' => 'string',
        'category_price_id' => 'string',
        'service_id' => 'string',
    ];

    /**
     * Mối quan hệ với Service
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function service()
    {
        return $this->belongsTo(Service::class);
    }
    /**
     * Mối quan hệ với CategoryPrice
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function categoryPrice()
    {
        return $this->belongsTo(CategoryPrice::class);
    }
}
