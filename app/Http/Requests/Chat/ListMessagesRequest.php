<?php

namespace App\Http\Requests\Chat;

use App\Core\Controller\ListRequest;

class ListMessagesRequest extends ListRequest
{
    protected array $allowedSorts = ['id', 'created_at'];

    protected array $allowedFilters = ['room_id'];

    public function rules(): array
    {
        $rules = parent::rules();

        // phải truyền room_id để lấy messages
        $rules['filter.room_id'] = 'required|string|max:255';

        return $rules;
    }
}


