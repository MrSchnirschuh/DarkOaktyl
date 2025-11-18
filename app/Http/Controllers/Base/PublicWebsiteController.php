<?php

namespace DarkOak\Http\Controllers\Base;

use Illuminate\View\View;
use DarkOak\Http\Controllers\Controller;
use Illuminate\View\Factory as ViewFactory;

class PublicWebsiteController extends Controller
{
    /**
     * PublicWebsiteController constructor.
     */
    public function __construct(
        protected ViewFactory $view
    ) {
    }

    /**
     * Returns the public website landing page.
     */
    public function index(): View
    {
        return view('templates/public.home');
    }

    /**
     * Returns the documentation page.
     */
    public function documentation(): View
    {
        return view('templates/public.documentation');
    }
}
