<?php

namespace App\Http\Controllers\Products;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Models\Products\ProductImage;
use App\Models\User;

class ProductImageController extends Controller
{
    protected function getUser(): User
    {
        $user = Auth::user();

        return $user;
    }
    public function destroy(ProductImage $image)
    {
        $shop = $this->getUser()->shops()->first();

        if (!$shop || $image->product->shop_id !== $shop->id) {
            abort(403);
        }

        $path = 'products/' . $image->name;
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }

        $image->delete();

        return redirect()
            ->back()
            ->with([
                'message' => 'Image deleted successfully', 
                'type' => 'success'
            ]);
    }
}
