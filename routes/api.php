<?php

use App\Http\Controllers\AgentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\IndustrialCategories;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RatingController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\ShopPageController;
use App\Http\Controllers\WishlistController;
use App\Http\Middleware\EnsureAgent;
use App\Http\Middleware\PreventAgents;
use App\Http\Controllers\RecommendationController;
use App\Http\Controllers\CartTrackingController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\AgentManualOrderController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AdminChatbotController;
use Illuminate\Support\Facades\Route;

// Public Routes
Route::post('auth/register', [AuthController::class, 'register']);
Route::post('auth/login', [AuthController::class,'login'])->name('login');
Route::post('auth/logout', [AuthController::class,'logout']);
Route::post('auth/refresh-token', [AuthController::class,'refreshToken']);
Route::post('auth/consume-payload', [AuthController::class, 'consumePayload']);
Route::post('auth/consume-impersonation-payload', [AuthController::class, 'consumeImpersonationPayload']);
Route::post('auth/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('auth/reset-password', [AuthController::class, 'resetPassword']);
Route::get('search/{keyword}', [SearchController::class, 'search']);
Route::get('search-keyword/{keyword}', [SearchController::class, 'searchKeyword']);
Route::get('semantic-search', [SearchController::class, 'semanticSearch']);
Route::post('visual-search', [SearchController::class, 'visualSearch']);
Route::get('home', [ProductController::class,'index']);
Route::post('products/batch', [CartController::class, 'getProductsByIds']);
Route::get('shop/filters', [ShopPageController::class,'filterProducts']);
Route::get('shop/category/{categoryName}', [ShopPageController::class,'getProductsByCategoryName']);
Route::get('products', [ProductController::class,'getAllProducts']);
Route::get('top-products', [ProductController::class,'getTopProducts']);
Route::get('products/{slug}', [ProductController::class,'getProductBySlug']);
Route::get('industrial-categories', [IndustrialCategories::class, 'index']);
Route::post('auth/verify-token', [AuthController::class,'verifyToken']);
Route::post('request-custom-product', [ProductController::class,'requestCustomProduct']);
Route::post('send-message', [MessageController::class, 'sendMessage']);
Route::get('shipping-destinations', [PaymentController::class, 'getShippingDestinations']);
Route::post('shipping-cost', [PaymentController::class, 'getShippingCost']);

// Public Rating Routes
Route::get('/ratings/product/{productId}', [RatingController::class, 'getProductRatings']);
Route::get('/ratings/product/{productId}/paginated', [RatingController::class, 'getPaginatedRatings']);
Route::get('/ratings/product/{productId}/stats', [RatingController::class, 'getProductStats']);

// Chatbot Routes (public - works for both guests and authenticated users)
Route::prefix('chatbot')->middleware('throttle:chatbot')->group(function () {
    Route::post('session', [ChatbotController::class, 'initSession'])->middleware('throttle:chatbot-session');
    Route::post('send', [ChatbotController::class, 'sendMessage']);
    Route::get('history', [ChatbotController::class, 'getHistory']);
});

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/wishlist', [WishlistController::class, 'index']);
    Route::post('/wishlist/add', [WishlistController::class, 'add']);
    Route::delete('/wishlist/remove', [WishlistController::class, 'remove']);
    
    // Recombee Track/Recommend
    Route::post('/cart/track', [CartTrackingController::class, 'track']);
    Route::get('/recommendations/user', [RecommendationController::class, 'forUser']);
    
    Route::post('ratings', [RatingController::class, 'submitRating']);
    Route::get('ratings/product/{productId}/user', [RatingController::class, 'getUserRating']);
    Route::post('apply-coupon', [PaymentController::class, 'applyCoupon']);
    Route::get('check-if-coupon-applied', [PaymentController::class, 'checkIfCouponApplied']);
    
    Route::get('agents/dashboard-info', [AgentController::class, 'dashboardInfo'])->middleware(EnsureAgent::class);
    Route::get('agents/dashboard-info/sales-performance/{year}', [AgentController::class, 'salesPerformance'])->middleware(EnsureAgent::class);
    Route::get('agents/dashboard-info/orders', [AgentController::class, 'recentOrders'])->middleware(EnsureAgent::class);
    Route::get('agents/dashboard-info/commission-and-bonus', [AgentController::class, 'commissionAndBonus'])->middleware(EnsureAgent::class);
    Route::get('agents/dashboard-info/commissions-timeline', [AgentController::class, 'commissionsTimeline'])->middleware(EnsureAgent::class);
    Route::get('agents/manual-orders', [AgentManualOrderController::class, 'index'])->middleware(EnsureAgent::class);
    Route::post('agents/manual-orders', [AgentManualOrderController::class, 'store'])->middleware(EnsureAgent::class);
    Route::get('agents/manual-orders/lookups/customers', [AgentManualOrderController::class, 'customerLookup'])->middleware(EnsureAgent::class);
    Route::get('agents/manual-orders/lookups/products', [AgentManualOrderController::class, 'productLookup'])->middleware(EnsureAgent::class);
    Route::get('agents/manual-orders/{order}', [AgentManualOrderController::class, 'show'])->middleware(EnsureAgent::class);
    Route::post('agents/{user}/impersonate', [AgentController::class, 'impersonate'])
        ->middleware('can:superAdminAccess')
        ->name('api.agents.impersonate');

    // Profile
    Route::get('profile', [ProfileController::class, 'show']);
    Route::patch('profile', [ProfileController::class, 'update']);
    Route::delete('profile', [ProfileController::class, 'destroy']);
    Route::get('profile/orders', [ProfileController::class, 'orders']);
    Route::get('profile/orders/{order}/invoice', [ProfileController::class, 'invoice']);
    Route::get('profile/agent-activity', [ProfileController::class, 'agentActivity']);

    // Stripe Checkout
    Route::post('checkout', [PaymentController::class, 'stripeCheckout'])->middleware(PreventAgents::class)->name('payment.checkout');

    // Admin Chatbot
    Route::prefix('admin-chatbot')->middleware('can:superAdminAccess')->group(function () {
        Route::post('session', [AdminChatbotController::class, 'initSession']);
        Route::post('send', [AdminChatbotController::class, 'sendMessage']);
        Route::get('history', [AdminChatbotController::class, 'getHistory']);
    });
});



// Tap Payment - Public Routes (no auth required)
Route::get('/payment/callback', [PaymentController::class, 'paymentCallback'])->name('payment.callback');
Route::post('/payment/webhook', [PaymentController::class, 'webhook'])->name('payment.webhook');

// Recommendation item public route
Route::get('/recommendations/item/{productId}', [RecommendationController::class, 'forItem']);
Route::get('/recommendations/frequent/{productId}', [RecommendationController::class, 'frequentlyBoughtTogether']);

// Stripe Payment - Public Routes (no auth required)
Route::get('/stripe/callback', [PaymentController::class, 'stripeCallback'])->name('stripe.callback');
Route::post('/stripe/webhook', [PaymentController::class, 'stripeWebhook'])->name('stripe.webhook');

// Route::get('phpinfo', function () {
//     return phpinfo();
// });
