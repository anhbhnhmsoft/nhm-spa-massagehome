<?php

namespace App\Http\Requests\API\Agency;

use App\Core\Controller\ListRequest;

class ListKtvRequest extends ListRequest
{
    /**
     * @var array Các cột được phép sort.
     */
    protected array $allowedSorts = ['id', 'created_at', 'name', 'phone'];

    /**
     * @var array Các key được phép filter.
     */
    protected array $allowedFilters = ['keyword'];

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
}
