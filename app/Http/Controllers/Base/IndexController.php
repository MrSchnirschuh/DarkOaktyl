<?php

namespace DarkOak\Http\Controllers\Base;

use Illuminate\View\View;
use DarkOak\Http\Controllers\Controller;
use Illuminate\View\Factory as ViewFactory;
use DarkOak\Contracts\Repository\ServerRepositoryInterface;

class IndexController extends Controller
{
    /**
     * IndexController constructor.
     */
    public function __construct(
        protected ServerRepositoryInterface $repository,
        protected ViewFactory $view
    ) {
    }

    /**
     * Returns listing of user's servers.
     */
    public function index(): View
    {
        return view('templates/base.core');
    }
}

