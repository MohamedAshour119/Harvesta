<?php

namespace App\Http\Controllers;

use App\Models\WishlistItem;
use App\Traits\HttpResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Jobs\SendRecombeeInteractionJob;

class WishlistController extends Controller
{
    use HttpResponses;
    /**
     * Display a listing of the user's wishlist items.
     */
    public function index(Request $request)
    {
        $user = $request->user(); // Get authenticated user via injected Request

        if (!$user) {
            return $this->response_error('User not authenticated.', [], 401);
        }

        $wishlistItems = WishlistItem::where('user_id', $user->id)
            ->with(['product', 'variant']) // Eager load product and variant details
            ->get();


        // You might want to transform the data to match IFavoriteItem structure for the frontend
        $transformedItems = $wishlistItems->map(function ($item) {
            $item_image = $item->product->getFirstMediaUrl('main_images', 'product_card');
            // Compute display price using discount if available, and include original price
            $displayPrice = $item->variant && isset($item->variant->price_after_discount) && $item->variant->price_after_discount < $item->variant->unit_price_usd
                ? (float)$item->variant->price_after_discount
                : ($item->variant ? (float)$item->variant->unit_price_usd : 0);
            return [
                'productId'     => $item->product_id,
                'variantId'     => $item->variant_id,
                'productName'   => $item->product ? $item->product->name : null,
                'variantName'   => $item->variant ? $item->variant->variant_name : null,
                'variantValue'  => $item->variant ? $item->variant->variant_value : null,
                'price'         => $displayPrice,
                'originalPrice' => $item->variant ? (float)$item->variant->unit_price_usd : 0,
                'image'         => $item_image,
            ];
        });

        return $this->response_success($transformedItems);
    }

    /**
     * Store a newly created wishlist item in storage.
     */
    public function add(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'variant_id' => 'required|exists:product_variants,id', // Ensure this table name is correct
        ]);

        if ($validator->fails()) {
            return $this->response_error('Validation failed',['errors' => $validator->errors()], 422);
        }

        // Check if the item already exists for this user
        $existingItem = WishlistItem::where('user_id', $user->id)
            ->where('product_id', $request->product_id)
            ->where('variant_id', $request->variant_id)
            ->first();

        if ($existingItem) {
            return $this->response_error('Item already in wishlist.', ['message' => 'Item already in wishlist.'], 200); // Or 409 Conflict
        }

        $wishlistItem = WishlistItem::create([
            'user_id' => $user->id,
            'product_id' => $request->product_id,
            'variant_id' => $request->variant_id,
        ]);

        SendRecombeeInteractionJob::dispatch('add_bookmark', [
            'user_id' => $user->id,
            'product_id' => $request->product_id
        ]);

        return $this->response_success(['message' => 'Item added to wishlist successfully.', 'item' => $wishlistItem], 201);
    }

    /**
     * Remove the specified wishlist item from storage.
     */
    public function remove(Request $request)
    {
        $user = $request->user();

        // Frontend sends product_id and variant_id in the request body for DELETE
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|integer',
            'variant_id' => 'required|exists:product_variants,id',
        ]);

        if ($validator->fails()) {
            return $this->response_error('Validation failed',['errors' => $validator->errors()], 422);
        }

        $deleted = WishlistItem::where('user_id', $user->id)
            ->where('product_id', $request->product_id)
            ->where('variant_id', $request->variant_id)
            ->delete();

        if ($deleted) {
            SendRecombeeInteractionJob::dispatch('remove_bookmark', [
                'user_id' => $user->id,
                'product_id' => $request->product_id
            ]);
            return $this->response_success([], ['message' => 'Item removed from wishlist successfully.'], 200);
        }

        return $this->response_error('Item not found in wishlist or could not be removed.', ['message' => 'Item not found in wishlist or could not be removed.'], 404);
    }
}
