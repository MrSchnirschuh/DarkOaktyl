<?php

namespace DarkOak\Http\Controllers\Admin;

use Illuminate\View\View;
use Illuminate\Http\Request;
use DarkOak\Http\Controllers\Controller;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class BaseController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        if (!$user || (!$user->root_admin && !$user->admin_role_id)) {
            throw new AccessDeniedHttpException('You do not have permission to access this resource.');
        }

        return view('templates/base.core');
    }
}

