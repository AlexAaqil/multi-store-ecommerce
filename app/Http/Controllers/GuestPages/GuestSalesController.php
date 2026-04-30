<?php

namespace App\Http\Controllers\GuestPages;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class GuestSalesController extends Controller
{
    public function checkoutPage()
    {
        return inertia('guest/sales/CheckoutPage');
    }

    public function processCheckout(Request $request) 
    {
        dd($request);
    }
}