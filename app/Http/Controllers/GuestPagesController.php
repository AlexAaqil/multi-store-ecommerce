<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class GuestPagesController extends Controller
{
    public function home()
    {
        return Inertia::render('guest/homepage/Home');
    }

    public function about()
    {
        return 'about-page';
    }
}
