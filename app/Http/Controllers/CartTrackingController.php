<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Jobs\SendRecombeeInteractionJob;
use App\Traits\HttpResponses;
use Illuminate\Support\Facades\Validator;

class CartTrackingController extends Controller
{
    use HttpResponses;

    public function track(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|integer',
            'price' => 'nullable|numeric'
        ]);

        if ($validator->fails()) {
            return $this->response_error('Validation error', $validator->errors(), 422);
        }

        $userId = $request->user()->id;

        SendRecombeeInteractionJob::dispatch('cart_add', [
            'user_id' => $userId,
            'product_id' => $request->product_id,
            'price' => $request->price ?? 0
        ]);

        return $this->response_success([], 'Cart addition tracked successfully');
    }
}
