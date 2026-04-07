<?php

namespace App\Http\Controllers\Shops;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Shops\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Http\Requests\Shops\ShopRequest;

class ShopController extends Controller
{
    protected function user(): User
    {
        return Auth::user();
    }

    public function index()
    {
        $shops = $this->user()->shops()
            ->latest()
            ->paginate(10);

        return inertia('shops/Index', [
            'shops' => $shops,
            'hasShop' => $this->user()->shops()->exists()
        ]);
    }

    public function create()
    {
        // Redirect if user already has a shop
        if ($this->user()->shops()->exists()) {
            return redirect()->route('shops.index')->with([
                'message' => 'You already have a shop. Only one shop is allowed per account.',
                'type' => 'error'
            ]);
        }

        return inertia('shops/Create');
    }

    public function store(ShopRequest $request)
    {
        if ($this->user()->shops()->exists()) {
            return redirect()->route('shops.index')->with([
                'message' => 'You already have a shop. Only one shop is allowed per account.',
                'type' => 'error'
            ]);
        }

        $validated = $request->validated();

        // Handle custom slug
        $customSlug = $request->custom_slug ? Str::slug($request->custom_slug) : null;

        $shop = $this->user()->shops()->create([
            'name' => $validated['name'],
            'custom_slug' => $customSlug,
            'description' => $validated['description'] ?? null,
            'contact_email' => $validated['contact_email'] ?? null,
            'contact_phone' => $validated['contact_phone'] ?? null,
            'shop_category_id' => $validated['shop_category_id'] ?? null
        ]);
        
        // Handle logo upload with custom name
        if ($request->hasFile('logo')) {
            $logo_path = $this->uploadImage($request->file('logo'), 'logo', $shop);
            $shop->update(['logo_image' => $logo_path]);
        }
        
        // Handle cover upload with custom name
        if ($request->hasFile('cover')) {
            $cover_path = $this->uploadImage($request->file('cover'), 'cover', $shop);
            $shop->update(['cover_image' => $cover_path]);
        }

        return redirect()->route('shops.index')->with([
            'message' => "Shop {$shop->name} created successfully",
            'type' => 'success'
        ]);
    }

    public function edit(Shop $shop)
    {
        // Ensure user owns this shop
        if ($shop->owner_id !== $this->user()->id) {
            abort(403);
        }
        
        return inertia('shops/Edit', [
            'shop' => $shop
        ]);
    }

    public function update(ShopRequest $request, Shop $shop)
    {
        // Ensure user owns this shop
        if ($shop->owner_id !== $this->user()->id) {
            abort(403);
        }
        
        $validated = $request->validated();

        // Handle custom slug update
        if ($request->has('custom_slug')) {
            $customSlug = $request->custom_slug ? Str::slug($request->custom_slug) : null;
            $validated['custom_slug'] = $customSlug;
        }
        
        // Handle logo upload with custom name
        if ($request->hasFile('logo')) {
            // Delete old logo if exists
            if ($shop->logo_image) {
                Storage::disk('public')->delete($shop->logo_image);
            }
            $logo_path = $this->uploadImage($request->file('logo'), 'logo', $shop);
            $validated['logo_image'] = $logo_path;
        }
        
        // Handle cover upload with custom name
        if ($request->hasFile('cover')) {
            // Delete old cover if exists
            if ($shop->cover_image) {
                Storage::disk('public')->delete($shop->cover_image);
            }
            $cover_path = $this->uploadImage($request->file('cover'), 'cover', $shop);
            $validated['cover_image'] = $cover_path;
        }
        
        $shop->update($validated);

        return redirect()->route('shops.index')->with([
            'message' => "Shop {$shop->name} updated successfully",
            'type' => 'success'
        ]);
    }

    public function destroy(Shop $shop)
    {
        // Ensure user owns this shop
        if ($shop->owner_id !== $this->user()->id) {
            abort(403);
        }
        
        // Delete associated images
        if ($shop->logo_image) {
            Storage::disk('public')->delete($shop->logo_image);
        }

        if ($shop->cover_image) {
            Storage::disk('public')->delete($shop->cover_image);
        }
        
        $shop->delete();

        return redirect()->back()->with([
            'message' => 'Shop deleted successfully',
            'type' => 'success'
        ]);
    }

    private function uploadImage($file, string $type, Shop $shop): string
    {
        $extension = $file->getClientOriginalExtension();
        $timestamp = now()->format('dmY_His');
        $slug = Str::slug($shop->name, '_');

        $filename = "{$slug}_{$type}_{$shop->id}_{$timestamp}.{$extension}";

        // Determine directory
        $directory = $type === 'logo' ? 'shops/logos' : 'shops/covers';
        
        // Store the file and get the path
        $path = $file->storeAs($directory, $filename, 'public');
        
        // Return the file name
        return $filename;
    }
}