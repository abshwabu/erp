<?php

declare(strict_types=1);

namespace App\Modules\Sales\Models;

use App\Modules\CRM\Models\Activity;
use App\Modules\CRM\Models\Deal;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'customers';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'company',
        'job_title',
        'status',
        'source',
        'address',
        'city',
        'country',
        'website',
        'notes',
    ];

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'customer_id');
    }

    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class, 'customer_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class, 'customer_id');
    }
}
