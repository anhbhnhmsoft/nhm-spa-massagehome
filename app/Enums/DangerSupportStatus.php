<?php

namespace App\Enums;

enum DangerSupportStatus: int
{
    case PENDING = 0;
    case CONFIRMED = 1;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::PENDING => __('admin.common.status.pending'),
            self::CONFIRMED => __('admin.common.status.confirmed'),
        };
    }

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::PENDING => 'danger',
            self::CONFIRMED => 'success',
        };
    }
}
