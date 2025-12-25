<?php

namespace App\Http\Requests;

use App\Core\Controller\ListRequest;

class ListKTVRequest extends ListRequest
{
    protected array $allowedSorts = ['created_at', 'reviews_received_avg_rating', 'reviews_received_count'];

}
