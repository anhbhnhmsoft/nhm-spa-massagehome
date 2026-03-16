<?php

namespace App\Enums;

enum GeminiAiModel: string
{
    /**
     * Recommend model for general use.
     */
    case GEMINI_2_5_FLASH_LITE = 'gemini-2.5-flash-lite';

    case GEMINI_2_5_FLASH = 'gemini-2.5-flash';


    public function endpoint(): string
    {
        return "https://generativelanguage.googleapis.com/v1beta/models/{$this->value}:generateContent";
    }
}
