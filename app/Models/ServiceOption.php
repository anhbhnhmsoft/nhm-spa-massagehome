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
        'duration',
        'price',
    ];
    protected $casts = [
        'id' => 'string',
        'service_id' => 'string',
        'price' => 'decimal:2',
    ];

    /**
     * Mối quan hệ với Service
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
