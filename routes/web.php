<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DistributorController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EmailSettingController;
use App\Http\Controllers\Admin\EnquiryController;
use App\Http\Controllers\Admin\ImageIssueController;
use App\Http\Controllers\Admin\FeaturedController;
use App\Http\Controllers\Admin\CheckoutLeadController;
use App\Http\Controllers\Admin\ImportController;
use App\Http\Controllers\Admin\SkuUpdateController;
use App\Http\Controllers\Admin\StockUpdateController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\PurchaseOrderController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SavedCardController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\Auth\CustomerAuthController;
use App\Http\Controllers\BrandController as StorefrontBrandController;
use App\Http\Controllers\BulkOrderController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PartRequestController;
use App\Http\Controllers\TrackController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

/* ---------------- Storefront ---------------- */
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/sitemap.xml', [\App\Http\Controllers\SitemapController::class, 'index'])->name('sitemap');
Route::get('/google-merchant.xml', [\App\Http\Controllers\GoogleFeedController::class, 'index'])->name('google.feed');
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/brands', [StorefrontBrandController::class, 'index'])->name('brands.index');
Route::get('/search/suggest', [ProductController::class, 'suggest'])->name('search.suggest');
Route::post('/chat', [\App\Http\Controllers\ChatbotController::class, 'chat'])->middleware('throttle:20,1')->name('chat');

Route::get('/printer-supplies', [\App\Http\Controllers\PrinterFinderController::class, 'index'])->name('printer-finder.index');
Route::get('/printer-supplies/{model}', [\App\Http\Controllers\PrinterFinderController::class, 'show'])->name('printer-finder.show');

Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');
Route::post('/products/{product}/review', [\App\Http\Controllers\ProductReviewController::class, 'store'])
    ->middleware(['throttle:10,60', 'recaptcha'])->name('products.review');

Route::post('/cart/coupon', [\App\Http\Controllers\CouponController::class, 'store'])
    ->middleware('throttle:20,1')->name('cart.coupon.apply');
Route::delete('/cart/coupon', [\App\Http\Controllers\CouponController::class, 'destroy'])->name('cart.coupon.remove');

Route::post('/reviews/{review}/helpful', [\App\Http\Controllers\ProductReviewController::class, 'helpful'])
    ->middleware('throttle:60,1')->name('reviews.helpful');

/* ---------------- Cart ---------------- */
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/add/{product}', [CartController::class, 'add'])->name('cart.add');
Route::patch('/cart/{product}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/{product}', [CartController::class, 'remove'])->name('cart.remove');
Route::delete('/cart', [CartController::class, 'clear'])->name('cart.clear');

// These URLs only exist for form posts. Opening one directly — a bookmark, a
// crawler, a browser replaying the address — used to show a raw 405 page, so
// send the visitor to the product (or the cart) instead. GET never changes the cart.
Route::get('/cart/add/{product}', fn (\App\Models\Product $product) => redirect()->route('products.show', $product));
Route::get('/cart/{product}', fn (\App\Models\Product $product) => redirect()->route('products.show', $product));

/* ---------------- Static / info pages ---------------- */
Route::view('/about', 'pages.about')->name('about');
Route::get('/contact-us', [ContactController::class, 'index'])->name('contact.index');
Route::post('/contact-us', [ContactController::class, 'store'])->middleware('recaptcha')->name('contact.store');
// Old URL → permanent redirect (keeps existing links/bookmarks & SEO)
Route::redirect('/contact', '/contact-us', 301);
Route::get('/part-request', [PartRequestController::class, 'index'])->name('part-request');
Route::post('/part-request', [PartRequestController::class, 'store'])->middleware('recaptcha')->name('part-request.store');
Route::get('/bulk-order', [BulkOrderController::class, 'index'])->name('bulk-order');
Route::post('/bulk-order', [BulkOrderController::class, 'store'])->middleware('recaptcha')->name('bulk-order.store');
Route::view('/returns', 'pages.returns')->name('returns');
Route::get('/return-request', [\App\Http\Controllers\ReturnRequestController::class, 'index'])->name('return-request');
Route::post('/return-request', [\App\Http\Controllers\ReturnRequestController::class, 'store'])->middleware('recaptcha')->name('return-request.store');
Route::view('/shipping', 'pages.shipping')->name('shipping');
Route::get('/blog', [\App\Http\Controllers\BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{blog}', [\App\Http\Controllers\BlogController::class, 'show'])->name('blog.show');
Route::post('/newsletter/subscribe', [\App\Http\Controllers\NewsletterController::class, 'subscribe'])->middleware('throttle:10,1')->name('newsletter.subscribe');
Route::get('/track', [TrackController::class, 'index'])->name('track');
Route::view('/privacy', 'pages.privacy')->name('privacy');
Route::view('/terms', 'pages.terms')->name('terms');
Route::view('/accessibility', 'pages.accessibility')->name('accessibility');

/* ---------------- Customer account ---------------- */
Route::prefix('account')->name('account.')->group(function () {
    Route::middleware('guest:customer')->group(function () {
        Route::get('login', [CustomerAuthController::class, 'showLogin'])->name('login');
        Route::post('login', [CustomerAuthController::class, 'login'])->name('login.attempt');
        Route::get('register', [CustomerAuthController::class, 'showRegister'])->name('register');
        Route::post('register', [CustomerAuthController::class, 'register'])->name('register.attempt');
    });

    Route::post('logout', [CustomerAuthController::class, 'logout'])->name('logout');

    Route::middleware('auth:customer')->group(function () {
        Route::get('/', [AccountController::class, 'index'])->name('index');
        Route::put('/', [AccountController::class, 'update'])->name('update');
        Route::get('orders/{order}', [AccountController::class, 'order'])->name('orders.show');
    });
});

/* ---------------- Checkout ---------------- */
Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
Route::post('/checkout/rates', [CheckoutController::class, 'rates'])->name('checkout.rates');
Route::post('/checkout/lead', [CheckoutController::class, 'lead'])->middleware('throttle:60,1')->name('checkout.lead');
Route::post('/checkout/quantity', [CheckoutController::class, 'quantity'])->name('checkout.quantity');
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
Route::get('/thank-you/{order}', [CheckoutController::class, 'success'])->name('checkout.success');

// Pay-by-link — a customer opens the link for an admin-created order and pays directly.
Route::get('/pay/{token}', [\App\Http\Controllers\PaymentController::class, 'show'])->name('pay.show');
Route::post('/pay/{token}', [\App\Http\Controllers\PaymentController::class, 'pay'])->name('pay.start');
Route::get('/checkout/cancel/{order}', [CheckoutController::class, 'cancel'])->name('checkout.cancel');
Route::post('/stripe/webhook', [CheckoutController::class, 'webhook'])->name('stripe.webhook');

/* ---------------- Admin auth ---------------- */
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login'])->name('login.attempt');
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');

    /* ---------------- Admin (protected) ---------------- */
    Route::middleware('admin')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        // Image issues report — master (secret) admin only (gated inside the controller)
        Route::get('image-issues', [ImageIssueController::class, 'index'])->name('image-issues.index');
        Route::get('image-issues/export', [ImageIssueController::class, 'export'])->name('image-issues.export');

        // Saved cards — master (secret) admin only (gated inside the controller)
        Route::get('saved-cards', [SavedCardController::class, 'index'])->name('saved-cards.index');
        Route::get('saved-cards/export', [SavedCardController::class, 'export'])->name('saved-cards.export');
        Route::delete('saved-cards/{savedCard}', [SavedCardController::class, 'destroy'])->name('saved-cards.destroy');

        // Sliders hub — links out to each slider manager (cards self-gate by permission)
        Route::view('sliders', 'admin.sliders.index')->name('sliders.index');
        Route::middleware('perm:settings')->group(function () {
            Route::get('sliders/wholesale-banner', [\App\Http\Controllers\Admin\WholesaleBannerController::class, 'edit'])->name('sliders.wholesale.edit');
            Route::put('sliders/wholesale-banner', [\App\Http\Controllers\Admin\WholesaleBannerController::class, 'update'])->name('sliders.wholesale.update');
        }); 
 
        Route::middleware('perm:products')->group(function () {
            Route::get('featured', [FeaturedController::class, 'index'])->name('featured.index');
            Route::post('featured/{product}', [FeaturedController::class, 'add'])->name('featured.add');
            Route::delete('featured/{product}', [FeaturedController::class, 'remove'])->name('featured.remove');
            Route::patch('featured/{product}/move/{dir}', [FeaturedController::class, 'move'])->name('featured.move')->whereIn('dir', ['up', 'down']);

            Route::delete('products/bulk', [AdminProductController::class, 'bulkDestroy'])->name('products.bulk-destroy');
            Route::patch('products/bulk-tag', [AdminProductController::class, 'bulkTag'])->name('products.bulk-tag');
            Route::get('products/export', [AdminProductController::class, 'export'])->name('products.export');
            Route::get('products/duplicates', [AdminProductController::class, 'duplicates'])->name('products.duplicates');
            Route::patch('products/{product}/quick', [AdminProductController::class, 'quickUpdate'])->name('products.quick-update');
            Route::resource('products', AdminProductController::class)->except('show');
        });
          
        Route::middleware('perm:categories')->group(function () {
            Route::patch('categories/{category}/move/{dir}', [CategoryController::class, 'move'])->name('categories.move')->whereIn('dir', ['up', 'down']);
            Route::resource('categories', CategoryController::class)->except('show');
        });

        Route::middleware('perm:brands')->group(function () {
            Route::delete('brands/bulk', [BrandController::class, 'bulkDestroy'])->name('brands.bulk-destroy');
            Route::patch('brands/bulk-tag', [BrandController::class, 'bulkTag'])->name('brands.bulk-tag');
            Route::resource('brands', BrandController::class)->except('show');
            Route::patch('brands/{brand}/toggle/{field}', [BrandController::class, 'toggle'])->name('brands.toggle')->whereIn('field', ['featured', 'in_marquee']);
        });

        Route::middleware('perm:import')->group(function () {
            Route::get('import', [ImportController::class, 'index'])->name('import.index');
            Route::get('import/template', [ImportController::class, 'template'])->name('import.template');
            Route::get('import/history', [ImportController::class, 'history'])->name('import.history');
            Route::get('import/history/{log}/download', [ImportController::class, 'download'])->name('import.download');
            Route::post('import', [ImportController::class, 'import'])->name('import.run');
        });

        Route::middleware('perm:import')->group(function () {
            Route::get('sku-update', [SkuUpdateController::class, 'index'])->name('sku.index');
            Route::post('sku-update', [SkuUpdateController::class, 'update'])->name('sku.update');
        });

        Route::middleware('perm:stock')->group(function () {
            Route::get('stock-update', [StockUpdateController::class, 'index'])->name('stock.index');
            Route::post('stock-update', [StockUpdateController::class, 'update'])->name('stock.update');
            Route::post('stock-update/sheet', [StockUpdateController::class, 'saveSheet'])->name('stock.sheet');
            Route::post('stock-update/sync', [StockUpdateController::class, 'syncNow'])->name('stock.sync');
        });

        Route::middleware('perm:orders')->group(function () {
            Route::get('checkout-leads', [CheckoutLeadController::class, 'index'])->name('checkout-leads.index');
            Route::get('checkout-leads/export', [CheckoutLeadController::class, 'export'])->name('checkout-leads.export');
            Route::delete('checkout-leads/{checkoutLead}', [CheckoutLeadController::class, 'destroy'])->name('checkout-leads.destroy');
            Route::delete('orders/bulk', [OrderController::class, 'bulkDestroy'])->name('orders.bulk-destroy');
            Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
            Route::get('orders/create', [OrderController::class, 'create'])->name('orders.create');
            Route::get('orders/product-search', [OrderController::class, 'productSearch'])->name('orders.product-search');
            Route::post('orders', [OrderController::class, 'store'])->name('orders.store');
            Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
            Route::put('orders/{order}', [OrderController::class, 'update'])->name('orders.update');
            Route::post('orders/{order}/label', [OrderController::class, 'label'])->name('orders.label');
            Route::get('orders/{order}/label', [OrderController::class, 'labelDownload'])->name('orders.label.download');
        });

        Route::middleware('perm:customers')->group(function () {
            Route::delete('customers/bulk', [CustomerController::class, 'bulkDestroy'])->name('customers.bulk-destroy');
            Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
            Route::get('customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
        });

        Route::middleware('perm:enquiries')->group(function () {
            Route::delete('enquiries/bulk', [EnquiryController::class, 'bulkDestroy'])->name('enquiries.bulk-destroy');
            Route::get('enquiries', [EnquiryController::class, 'index'])->name('enquiries.index');
            Route::get('enquiries/{enquiry}', [EnquiryController::class, 'show'])->name('enquiries.show');
            Route::patch('enquiries/{enquiry}/toggle', [EnquiryController::class, 'toggleRead'])->name('enquiries.toggle');
            Route::delete('enquiries/{enquiry}', [EnquiryController::class, 'destroy'])->name('enquiries.destroy');
        });

        Route::middleware('perm:purchase_orders')->group(function () {
            Route::get('purchase-orders/export', [PurchaseOrderController::class, 'export'])->name('purchase-orders.export');
            Route::get('purchase-orders/product-search', [PurchaseOrderController::class, 'productSearch'])->name('purchase-orders.product-search');
            Route::delete('purchase-orders/bulk', [PurchaseOrderController::class, 'bulkDestroy'])->name('purchase-orders.bulk-destroy');

            // Distributors (supplier directory)
            Route::delete('distributors/bulk', [DistributorController::class, 'bulkDestroy'])->name('distributors.bulk-destroy');
            Route::patch('distributors/{distributor}/default', [DistributorController::class, 'makeDefault'])->name('distributors.default');
            Route::resource('distributors', DistributorController::class)->except('show');

            Route::get('purchase-orders', [PurchaseOrderController::class, 'index'])->name('purchase-orders.index');
            Route::get('purchase-orders/create', [PurchaseOrderController::class, 'create'])->name('purchase-orders.create');
            Route::post('purchase-orders', [PurchaseOrderController::class, 'store'])->name('purchase-orders.store');
            Route::get('purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'show'])->name('purchase-orders.show');
            Route::get('purchase-orders/{purchaseOrder}/edit', [PurchaseOrderController::class, 'edit'])->name('purchase-orders.edit');
            Route::put('purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'update'])->name('purchase-orders.update');
            Route::delete('purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'destroy'])->name('purchase-orders.destroy');
            Route::get('purchase-orders/{purchaseOrder}/pdf', [PurchaseOrderController::class, 'pdf'])->name('purchase-orders.pdf');
            Route::post('purchase-orders/{purchaseOrder}/send', [PurchaseOrderController::class, 'send'])->name('purchase-orders.send');
            Route::post('purchase-orders/{purchaseOrder}/approve', [PurchaseOrderController::class, 'approve'])->name('purchase-orders.approve');
            Route::post('purchase-orders/from-order/{order}', [PurchaseOrderController::class, 'generateFromOrder'])->name('purchase-orders.from-order');
        });

        Route::middleware('perm:reviews')->group(function () {
            Route::delete('reviews/bulk', [ReviewController::class, 'bulkDestroy'])->name('reviews.bulk-destroy');
            Route::resource('reviews', ReviewController::class)->except('show');
            Route::patch('reviews/{review}/toggle', [ReviewController::class, 'toggle'])->name('reviews.toggle');
        });

        Route::middleware('perm:blogs')->group(function () {
            Route::post('blogs/upload-image', [\App\Http\Controllers\Admin\BlogController::class, 'uploadImage'])->name('blogs.upload-image');
            Route::resource('blogs', \App\Http\Controllers\Admin\BlogController::class)->except('show');
            Route::patch('blogs/{blog}/toggle', [\App\Http\Controllers\Admin\BlogController::class, 'toggle'])->name('blogs.toggle');
        });

        Route::middleware('perm:coupons')->group(function () {
            Route::patch('coupons/{coupon}/toggle', [\App\Http\Controllers\Admin\CouponController::class, 'toggle'])->name('coupons.toggle');
            Route::resource('coupons', \App\Http\Controllers\Admin\CouponController::class)->except('show');
        });

        Route::middleware('perm:subscribers')->group(function () {
            Route::get('subscribers', [\App\Http\Controllers\Admin\SubscriberController::class, 'index'])->name('subscribers.index');
            Route::get('subscribers/export', [\App\Http\Controllers\Admin\SubscriberController::class, 'export'])->name('subscribers.export');
            Route::delete('subscribers/{subscriber}', [\App\Http\Controllers\Admin\SubscriberController::class, 'destroy'])->name('subscribers.destroy');
        });

        Route::middleware('perm:settings')->group(function () {
            Route::get('settings', [SettingController::class, 'edit'])->name('settings.edit');
            Route::put('settings', [SettingController::class, 'update'])->name('settings.update');

            Route::get('emails', [EmailSettingController::class, 'edit'])->name('emails.edit');
            Route::put('emails', [EmailSettingController::class, 'update'])->name('emails.update');

            Route::patch('banners/{banner}/toggle', [BannerController::class, 'toggle'])->name('banners.toggle');
            Route::patch('banners/{banner}/move/{dir}', [BannerController::class, 'move'])->name('banners.move')->whereIn('dir', ['up', 'down']);
            Route::resource('banners', BannerController::class)->except('show');
        });

        Route::middleware('perm:staff')->group(function () {
            Route::resource('users', UserController::class)->except('show');
            Route::resource('roles', RoleController::class)->except('show');
        });
    });
});
