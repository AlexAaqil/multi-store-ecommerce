<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Enums\UserRoles;
use App\Models\User;
use App\Models\Shops\Shop;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user->role === UserRoles::SUPER_ADMIN) {
            return inertia('dashboards/SuperAdmin', [
                'user' => $user,
                'stats' => $this->getSuperAdminStats()
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

    private function getSuperAdminStats()
    {
        $now = now();
        $startOfWeek = $now->copy()->startOfWeek();
        $startOfMonth = $now->copy()->startOfMonth();
        $startOfYear = $now->copy()->startOfYear();

        return [
            'total_users' => User::whereNot('role', UserRoles::SUPER_ADMIN)->count(),
            'total_sellers' => User::where('role', UserRoles::SELLER)->count(),
            'total_customers' => User::where('role', UserRoles::CUSTOMER)->count(),

            'total_shops' => Shop::count(),
            'active_shops' => Shop::where('is_active', true)->count(),
            'verified_shops' => Shop::where('is_verified', true)->count(),
            'new_shops_today' => Shop::whereDate('created_at', $now->toDateString())->count(),
            'new_shops_this_week' => Shop::where('created_at', '>=', $startOfWeek)->count(),
            'new_shops_this_month' => Shop::where('created_at', '>=', $startOfMonth)->count(),
            'shops_percentage_change' => $this->calculatePercentageChange(
                Shop::whereBetween('created_at', [$startOfWeek->copy()->subWeek(), $startOfWeek])->count(),
                Shop::where('created_at', '>=', $startOfWeek)->count()
            ),
        ];
    }

    /**
     * Calculate percentage change between two values
     */
    private function calculatePercentageChange(float $oldValue, float $newValue): float
    {
        if ($oldValue == 0) {
            return $newValue > 0 ? 100 : 0;
        }
        
        return round((($newValue - $oldValue) / $oldValue) * 100, 1);
    }
}
