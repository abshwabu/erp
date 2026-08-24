<?php

declare(strict_types=1);

use App\Modules\Core\Models\Tenant;
use App\Modules\Core\Models\User;
use App\Modules\Inventory\Models\Product;
use App\Modules\Inventory\Models\ProductCategory;
use Database\Seeders\TenantRoleSeeder;
use Illuminate\Support\Facades\Hash;

it('handles complete drag-and-drop storefront builder lifecycle: site creation, section customization, public store browsing, and checkout', function (): void {
    $tenant = Tenant::create([
        'name'   => 'Storefront Builder Tenant',
        'slug'   => 'sbtest',
        'status' => 'active',
    ]);

    $data = $tenant->run(function () use ($tenant) {
        (new TenantRoleSeeder())->run();

        $admin = User::create([
            'tenant_id' => $tenant->getKey(),
            'name'      => 'Store Admin',
            'email'     => 'admin@sb.test',
            'password'  => Hash::make('password123'),
            'is_active' => true,
        ]);
        $admin->assignRole('Admin');

        $category = ProductCategory::create([
            'name'        => 'Apparel',
            'slug'        => 'apparel',
            'is_active'   => true,
        ]);

        $product = Product::create([
            'category_id'   => $category->id,
            'name'          => 'Organic Cotton Hoodie',
            'sku'           => 'HOD-ORG-01',
            'type'          => 'stockable',
            'selling_price' => 65.00,
            'cost_price'    => 25.00,
            'status'        => 'active',
        ]);

        return ['admin' => $admin, 'product' => $product];
    });

    $loginResponse = $this->postJson('http://sbtest.localhost/api/auth/login', [
        'email'    => 'admin@sb.test',
        'password' => 'password123',
    ]);
    $token = $loginResponse->json('access_token');
    $headers = ['Authorization' => "Bearer {$token}"];

    // 1. Create a Storefront Website
    $createResponse = $this->withHeaders($headers)->postJson('http://sbtest.localhost/api/ecommerce/storefronts', [
        'name'        => 'Urban Style Outfitters',
        'title'       => 'Urban Style Handcrafted Fashion',
        'description' => 'A premier modern boutique for streetwear and quality gear.',
    ]);
    $createResponse->assertStatus(201);
    $storefrontId = $createResponse->json('data.id');
    $slug         = $createResponse->json('data.slug');
    $homePageId   = $createResponse->json('data.pages.0.id');

    expect($storefrontId)->not->toBeNull();
    expect($slug)->toBe('urban-style-outfitters');
    expect($createResponse->json('data.pages.0.sections'))->toHaveCount(6);

    // 2. Update Page Sections (Drag-and-Drop Editor payload)
    $newSections = [
        [
            'id'    => 'hero-custom',
            'type'  => 'hero',
            'props' => [
                'headline'    => 'Summer 2026 Collection',
                'subheadline' => 'Explore breathable sustainable hoodies and essentials.',
                'button_text' => 'Browse Now',
                'button_link' => '#catalog',
            ],
        ],
        [
            'id'    => 'products-custom',
            'type'  => 'product_grid',
            'props' => [
                'title'   => 'Trending Apparel',
                'columns' => 4,
            ],
        ],
    ];

    $updateSectionsResponse = $this->withHeaders($headers)->putJson(
        "http://sbtest.localhost/api/ecommerce/storefronts/{$storefrontId}/pages/{$homePageId}",
        [
            'sections' => $newSections,
        ]
    );
    $updateSectionsResponse->assertStatus(200);
    expect($updateSectionsResponse->json('data.sections'))->toHaveCount(2);

    // 3. Update Storefront Theme Config
    $updateThemeResponse = $this->withHeaders($headers)->putJson(
        "http://sbtest.localhost/api/ecommerce/storefronts/{$storefrontId}",
        [
            'theme_config' => [
                'primary_color' => '#10b981',
                'banner_text'   => 'Free worldwide shipping on all orders over $75!',
                'show_banner'   => true,
            ],
        ]
    );
    $updateThemeResponse->assertStatus(200);
    expect($updateThemeResponse->json('data.theme_config.primary_color'))->toBe('#10b981');

    // 4. Public Customer Storefront API (No Auth)
    $publicStoreResponse = $this->getJson("http://sbtest.localhost/api/store/{$slug}");
    $publicStoreResponse->assertStatus(200);
    expect($publicStoreResponse->json('data.storefront.name'))->toBe('Urban Style Outfitters');
    expect($publicStoreResponse->json('data.products'))->toHaveCount(1);
    expect($publicStoreResponse->json('data.products.0.name'))->toBe('Organic Cotton Hoodie');

    // 5. Customer places an order through the public store
    $checkoutResponse = $this->postJson("http://sbtest.localhost/api/store/{$slug}/checkout", [
        'customer_name'    => 'Alex Customer',
        'customer_email'   => 'alex@customer.test',
        'shipping_address' => '456 Fashion Ave, Los Angeles, CA',
        'items'            => [
            [
                'product_id'  => $data['product']->id,
                'name'        => 'Organic Cotton Hoodie',
                'quantity'    => 2,
                'price_cents' => 6500,
            ],
        ],
    ]);
    $checkoutResponse->assertStatus(201);
    expect($checkoutResponse->json('data.order_number'))->toStartWith('#WEB-');
    expect($checkoutResponse->json('data.total_cents'))->toBe(13000);

    // 6. Verify order is visible in Merchant's order list
    $ordersListResponse = $this->withHeaders($headers)->getJson('http://sbtest.localhost/api/ecommerce/orders');
    $ordersListResponse->assertStatus(200);
    expect($ordersListResponse->json('data.data'))->toHaveCount(1);
    expect($ordersListResponse->json('data.data.0.customer_name'))->toBe('Alex Customer');
    expect($ordersListResponse->json('data.data.0.total_cents'))->toBe(13000);
});
