<?php

declare(strict_types=1);

namespace App\Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StorefrontPage extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'storefront_pages';

    protected $fillable = [
        'storefront_id',
        'slug',
        'title',
        'sections',
        'is_published',
        'order',
    ];

    protected $casts = [
        'sections'     => 'array',
        'is_published' => 'boolean',
        'order'        => 'integer',
    ];

    public function storefront(): BelongsTo
    {
        return $this->belongsTo(Storefront::class, 'storefront_id');
    }

    /**
     * Default starter sections for new e-commerce storefronts.
     */
    public static function defaultStarterSections(string $storeName): array
    {
        return [
            [
                'id' => 'hero-1',
                'type' => 'hero',
                'props' => [
                    'headline' => 'Welcome to ' . $storeName,
                    'subheadline' => 'Discover our premium selection of quality products handcrafted for you.',
                    'button_text' => 'Shop All Products',
                    'button_link' => '#products',
                    'background_color' => '#1e293b',
                    'text_color' => '#ffffff',
                    'align' => 'center',
                ],
            ],
            [
                'id' => 'features-1',
                'type' => 'features',
                'props' => [
                    'title' => 'Why Shop With Us',
                    'items' => [
                        [
                            'icon' => 'truck',
                            'title' => 'Fast Express Delivery',
                            'description' => 'Fast & secure doorstep delivery on all orders.',
                        ],
                        [
                            'icon' => 'shield',
                            'title' => 'Secure Payment',
                            'description' => '100% encrypted, trusted checkout and transactions.',
                        ],
                        [
                            'icon' => 'refresh-cw',
                            'title' => 'Easy Returns',
                            'description' => '30-day risk-free money-back guarantee.',
                        ],
                    ],
                ],
            ],
            [
                'id' => 'products-1',
                'type' => 'product_grid',
                'props' => [
                    'title' => 'Featured Products',
                    'subtitle' => 'Top selling items from our collection',
                    'columns' => 4,
                    'limit' => 8,
                    'show_price' => true,
                    'show_add_to_cart' => true,
                ],
            ],
            [
                'id' => 'promo-1',
                'type' => 'promo_banner',
                'props' => [
                    'headline' => 'Limited Time Special Offer',
                    'badge' => 'SPECIAL DEAL',
                    'code' => 'SAVE20',
                    'description' => 'Get 20% off your entire first purchase with coupon code.',
                    'background_color' => '#4f46e5',
                ],
            ],
            [
                'id' => 'testimonials-1',
                'type' => 'testimonials',
                'props' => [
                    'title' => 'What Our Customers Say',
                    'items' => [
                        [
                            'quote' => 'Super fast shipping and incredible product quality. Will definitely buy again!',
                            'author' => 'Sarah M.',
                            'rating' => 5,
                        ],
                        [
                            'quote' => 'Customer service was extremely helpful and resolved my query in minutes.',
                            'author' => 'David K.',
                            'rating' => 5,
                        ],
                    ],
                ],
            ],
            [
                'id' => 'footer-1',
                'type' => 'footer',
                'props' => [
                    'store_name' => $storeName,
                    'tagline' => 'Your trusted online shopping destination.',
                    'contact_email' => 'support@example.com',
                    'show_socials' => true,
                ],
            ],
        ];
    }
}
