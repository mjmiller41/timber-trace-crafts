<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EtsyController;
use App\Http\Controllers\Admin\InboxController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\MessageController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\ShippingMethodController;
use App\Http\Controllers\Admin\TagController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\EtsyWebhookController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\JournalController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\OrderStatusController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RestockController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\SitemapController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\Route;

// Sitemap & robots
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

// /about → /about-us permanent redirect
Route::permanentRedirect('/about', '/about-us');

// Public pages
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/shop', [ShopController::class, 'index'])->name('shop');
Route::get('/product/{slug}', [ProductController::class, 'show'])->name('product.show');

// Cart
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
Route::patch('/cart/{rowKey}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/{rowKey}', [CartController::class, 'remove'])->name('cart.remove');
Route::post('/cart/coupon', [CartController::class, 'applyCoupon'])->name('cart.coupon');
Route::delete('/cart/coupon', [CartController::class, 'removeCoupon'])->name('cart.coupon.remove');

// Checkout
Route::middleware('throttle:60,1')->group(function () {
    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/checkout', [CheckoutController::class, 'process'])->name('checkout.process');
    Route::post('/checkout/payment-intent', [CheckoutController::class, 'createPaymentIntent'])->name('checkout.payment-intent');
    Route::get('/checkout/confirmation/{order}', [CheckoutController::class, 'confirmation'])->name('checkout.confirmation');
});

// Order status lookup (no login)
Route::get('/order-status', [OrderStatusController::class, 'index'])->name('order.status');
Route::post('/order-status', [OrderStatusController::class, 'lookup'])->name('order.status.lookup');

// Restock request
Route::post('/restock-request', [RestockController::class, 'store'])->name('restock.store');

// Journal
Route::get('/journal', [JournalController::class, 'index'])->name('journal.index');
Route::get('/journal/feed.xml', [JournalController::class, 'feed'])->name('journal.feed');
Route::get('/journal/tag/{tag:slug}', [JournalController::class, 'tag'])->name('journal.tag');
Route::get('/journal/{slug}', [JournalController::class, 'show'])->name('journal.show');

// Newsletter
Route::post('/newsletter', [NewsletterController::class, 'store'])->name('newsletter.store');

// Contact
Route::get('/contact', [ContactController::class, 'index'])->name('contact.index');
Route::post('/contact', [ContactController::class, 'submit'])->middleware('honeypot')->name('contact.submit');

// Static pages
Route::get('/{slug}', [PageController::class, 'show'])->name('page.show')
    ->where('slug', 'faq|privacy-policy|terms-and-conditions|return-policy|shipping-policy|about-us');

// Auth
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('honeypot');
    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
});
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Email verification
Route::middleware('auth')->group(function () {
    Route::get('/email/verify', [AuthController::class, 'showVerificationNotice'])->name('verification.notice');
    Route::post('/email/verification-notification', [AuthController::class, 'resendVerificationEmail'])
        ->middleware('throttle:6,1')
        ->name('verification.send');
    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();

        return redirect()->route('account.dashboard')->with('status', 'email-verified');
    })->middleware('signed')->name('verification.verify');
});

// Account (requires auth)
Route::middleware('auth')->prefix('account')->name('account.')->group(function () {
    Route::get('/', [AccountController::class, 'dashboard'])->name('dashboard');
    Route::get('/orders', [AccountController::class, 'orders'])->name('orders');
    Route::get('/orders/{order}', [AccountController::class, 'orderShow'])->name('orders.show');
    Route::get('/orders/{order}/invoice', [AccountController::class, 'orderInvoice'])->name('orders.invoice');
    Route::get('/wishlist', [AccountController::class, 'wishlist'])->name('wishlist');
    Route::post('/wishlist', [AccountController::class, 'wishlistAdd'])->name('wishlist.add');
    Route::delete('/wishlist/{variant}', [AccountController::class, 'wishlistRemove'])->name('wishlist.remove');
    Route::get('/addresses', [AccountController::class, 'addresses'])->name('addresses');
    Route::post('/addresses', [AccountController::class, 'addressStore'])->name('addresses.store');
    Route::put('/addresses/{address}', [AccountController::class, 'addressUpdate'])->name('addresses.update');
    Route::delete('/addresses/{address}', [AccountController::class, 'addressDelete'])->name('addresses.delete');
    Route::get('/profile', [AccountController::class, 'profile'])->name('profile');
    Route::put('/profile', [AccountController::class, 'profileUpdate'])->name('profile.update');
});

// Admin
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Orders
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.status');
    Route::post('/orders/{order}/shipment', [OrderController::class, 'addShipment'])->name('orders.shipment');
    Route::get('/orders/{order}/packing-slip', [OrderController::class, 'packingSlip'])->name('orders.packing-slip');

    // Products
    Route::resource('products', App\Http\Controllers\Admin\ProductController::class)->except(['destroy']);
    Route::delete('/products/{product}', [App\Http\Controllers\Admin\ProductController::class, 'destroy'])->name('products.destroy');

    // Categories & Tags
    Route::resource('categories', CategoryController::class)->except(['show']);
    Route::resource('tags', TagController::class)->except(['show']);

    // Media
    Route::get('/media', [MediaController::class, 'index'])->name('media.index');
    Route::post('/media', [MediaController::class, 'store'])->name('media.store');
    Route::delete('/media/{media}', [MediaController::class, 'destroy'])->name('media.destroy');
    Route::get('/media/{media}', [MediaController::class, 'show'])->name('media.show');

    // Coupons
    Route::resource('coupons', CouponController::class)->except(['show']);

    // Customers
    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('/customers/{user}', [CustomerController::class, 'show'])->name('customers.show');

    // Reviews
    Route::get('/reviews', [ReviewController::class, 'index'])->name('reviews.index');
    Route::patch('/reviews/{review}', [ReviewController::class, 'updateStatus'])->name('reviews.status');

    // Journal
    Route::resource('journal', App\Http\Controllers\Admin\JournalController::class)->except(['show']);
    Route::get('journal/import', [App\Http\Controllers\Admin\JournalController::class, 'importIndex'])->name('journal.import');
    Route::post('journal/import/{filename}', [App\Http\Controllers\Admin\JournalController::class, 'import'])->name('journal.import.file');

    // Messages
    Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
    Route::patch('/messages/{submission}', [MessageController::class, 'updateStatus'])->name('messages.status');

    // Inbox (IMAP)
    Route::get('/inbox', [InboxController::class, 'index'])->name('inbox.index');
    Route::get('/inbox/compose', [InboxController::class, 'compose'])->name('inbox.compose');
    Route::post('/inbox/send', [InboxController::class, 'send'])->name('inbox.send');
    Route::get('/inbox/{msgno}', [InboxController::class, 'show'])->name('inbox.show');
    Route::get('/inbox/{msgno}/reply', [InboxController::class, 'reply'])->name('inbox.reply');
    Route::delete('/inbox/{msgno}', [InboxController::class, 'destroy'])->name('inbox.destroy');

    // Restock requests
    Route::get('/restock', [App\Http\Controllers\Admin\RestockController::class, 'index'])->name('restock.index');
    Route::post('/restock/{variant}/notify', [App\Http\Controllers\Admin\RestockController::class, 'notify'])->name('restock.notify');

    // Pages
    Route::get('/pages', [App\Http\Controllers\Admin\PageController::class, 'index'])->name('pages.index');
    Route::get('/pages/{page}/edit', [App\Http\Controllers\Admin\PageController::class, 'edit'])->name('pages.edit');
    Route::put('/pages/{page}', [App\Http\Controllers\Admin\PageController::class, 'update'])->name('pages.update');

    // Reports
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');

    // Settings
    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');

    // Shipping methods
    Route::resource('shipping', ShippingMethodController::class)->except(['show']);

    // Etsy sync
    Route::get('/etsy', [EtsyController::class, 'index'])->name('etsy.index');
    Route::get('/etsy/connect', [EtsyController::class, 'connect'])->name('etsy.connect');
    Route::get('/etsy/callback', [EtsyController::class, 'callback'])->name('etsy.callback');
    Route::post('/etsy/disconnect', [EtsyController::class, 'disconnect'])->name('etsy.disconnect');
    Route::post('/etsy/sync/products', [EtsyController::class, 'syncProducts'])->name('etsy.sync.products');
    Route::post('/etsy/push/product/{product}', [EtsyController::class, 'pushProduct'])->name('etsy.push.product');
    Route::post('/etsy/sync/inventory', [EtsyController::class, 'syncInventory'])->name('etsy.sync.inventory');
    Route::post('/etsy/sync/orders', [EtsyController::class, 'syncOrders'])->name('etsy.sync.orders');
    Route::post('/etsy/sync/reviews', [EtsyController::class, 'syncReviews'])->name('etsy.sync.reviews');
});

// Etsy webhook — public, no auth (Etsy calls this directly)
Route::post('/webhooks/etsy', [EtsyWebhookController::class, 'handle'])->name('webhooks.etsy');
