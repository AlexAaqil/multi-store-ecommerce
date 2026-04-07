<?php

namespace App\Http\Controllers;

use App\Models\Shops\Shop;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GuestPagesController extends Controller
{
    public function homePage(Request $request)
    {
        $query = Shop::with('category')
            ->where('is_active', true);
        
        // Prioritize verified shops
        $query->orderBy('is_verified', 'desc');
        
        // Optional: Filter by category if requested
        if ($request->has('category') && $request->category !== 'All') {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('name', $request->category);
            });
        }
        
        // Random ordering for variety (or use latest() for newest first)
        $query->inRandomOrder();
        
        $shops = $query->limit(4)->get();
        
        // If not enough shops, get more from active but not verified
        if ($shops->count() < 4) {
            $additional = Shop::where('is_active', true)
                ->whereNotIn('id', $shops->pluck('id'))
                ->inRandomOrder()
                ->limit(4 - $shops->count())
                ->get();
            
            $shops = $shops->concat($additional);
        }

        // Transform shops to include full image URLs
        $featured_shops = $shops->map(function ($shop) {
            return [
                'id' => $shop->id,
                'name' => $shop->name,
                'slug' => $shop->public_slug,
                'category' => $shop->category?->name,
                'rating' => 4.9, // TODO Replace with actual rating when available
                'reviews_count' => 312, // TODO Replace with actual count when available
                'logo_image' => $shop->logo_url_full,
                'cover_image' => $shop->cover_url_full,
                'status' => $shop->is_active ? 'Open' : 'Closed',
                'status_class' => $shop->is_active ? 'badge-green' : 'badge-gray',
                'is_active' => $shop->is_active,
                'is_verified' => $shop->is_verified,
            ];
        });
        
        return Inertia::render('guest/homepage/Home', [
            'featured_shops' => $featured_shops,
            'total_shops' => Shop::where('is_active', true)->count(),
        ]);
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
