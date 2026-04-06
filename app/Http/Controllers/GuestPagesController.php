<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class GuestPagesController extends Controller
{
    public function homePage()
    {
        return Inertia::render('guest/homepage/Home');
    }

    public function dealsAndOffersPage()
    {
        return Inertia::render('guest/dealspage/Deals');
    }

    public function about()
    {
        return 'about-page';
    }
}
