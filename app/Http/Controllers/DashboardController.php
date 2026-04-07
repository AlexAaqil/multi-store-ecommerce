<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Enums\UserRoles;

class DashboardController extends Controller
{
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user->role === UserRoles::SUPER_ADMIN) {
            return inertia('dashboards/SuperAdmin', [
                'user' => $user,
                'stats' => ''
            ]);
        }

        if ($user->role === UserRoles::ADMIN) {
            return inertia('dashboards/Admin', [
                'user' => $user,
                'stats' => ''
            ]);
        }

        if ($user->role === UserRoles::SELLER) {
            return inertia('dashboards/Seller', [
                'user' => $user,
                'stats' => ''
            ]);
        }

        if ($user->role === UserRoles::CUSTOMER) {
            return inertia('dashboards/Customer', [
                'user' => $user,
                'stats' => ''
            ]);
        }

        return 'Unknown user role';
    }
}
