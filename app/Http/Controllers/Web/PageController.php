<?php

namespace App\Http\Controllers\Web;

use App\Core\Controller\BaseController;
use Illuminate\Http\Request;

use App\Models\Page;

class PageController extends BaseController
{
    public function show($slug)
    {
        $page = Page::where('slug', $slug)->where('is_active', true)->firstOrFail();

        return view('web.show', compact('page'));
    }
}
