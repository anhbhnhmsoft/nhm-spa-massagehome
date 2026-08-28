<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Province extends Model
{
    protected $fillable = ['name', 'code', 'division_type'];

    /**
     * Lấy mảng tùy chọn Tỉnh / Thành phố cho Filament Select [name => name]
     */
    public static function toOptions(): array
    {
        return static::query()->orderBy('name', 'asc')->pluck('name', 'name')->toArray();
    }
}
