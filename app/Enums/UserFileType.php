<?php

namespace App\Enums;

enum UserFileType: int
{
    case IDENTITY_CARD_FRONT = 1; // Hình ảnh mặt trước thẻ căn cước
    case IDENTITY_CARD_BACK = 2; // Hình ảnh mặt sau thẻ căn cước
    case LICENSE = 3; // Hình ảnh giấy phép
    case KTV_IMAGE_DISPLAY = 5; // Hình ảnh hiển thị cho khách hàng
    case FACE_WITH_IDENTITY_CARD = 6; // Hình ảnh mặt trước thẻ căn cước kèm theo khuôn mặt


    public function label(): string
    {
        return match ($this) {
            self::IDENTITY_CARD_FRONT => __('admin.ktv_apply.file_type.identity_card_front'),
            self::IDENTITY_CARD_BACK => __('admin.ktv_apply.file_type.identity_card_back'),
            self::LICENSE => __('admin.ktv_apply.file_type.license'),
            self::KTV_IMAGE_DISPLAY => __('admin.ktv_apply.file_type.ktv_image_display'),
            self::FACE_WITH_IDENTITY_CARD => __('admin.ktv_apply.file_type.face_with_identity_card'),
        };
    }

    public static function toOptions(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }
        return $options;
    }

    public static function getLabel(int $value): string
    {
        return self::tryFrom($value)?->label() ?? '';
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    // Các loại file này sẽ được upload lên disk private
    public static function getTypeUploadToPrivateDisk(): array
    {
        return [
            self::IDENTITY_CARD_FRONT->value,
            self::IDENTITY_CARD_BACK->value,
            self::LICENSE->value,
            self::FACE_WITH_IDENTITY_CARD->value,
        ];
    }
}
