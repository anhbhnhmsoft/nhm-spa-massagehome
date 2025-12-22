<?php

namespace App\Http\Requests\Chat;

use App\Core\Controller\ListRequest;

class ListMessagesRequest extends ListRequest
{
    protected array $allowedSorts = ['id', 'created_at'];

}


