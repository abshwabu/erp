<?php

declare(strict_types=1);

namespace App\Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Storefront extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'storefronts';

    protected $fillable = [
        'name',
        'slug',
        'title',
        'description',
        'logo_url',
        'theme_config',
        'is_published',
        'custom_domain',
    ];

    protected $casts = [
        'theme_config' => 'array',
        'is_published' => 'boolean',
    ];

    public function pages(): HasMany
    {
        return $this->hasMany(StorefrontPage::class, 'storefront_id')->orderBy('order');
    }

    public function homePage(): HasOne
    {
        return $this->hasOne(StorefrontPage::class, 'storefront_id')->where('slug', 'home');
    }
}
