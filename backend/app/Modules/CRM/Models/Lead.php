<?php

declare(strict_types=1);

namespace App\Modules\CRM\Models;

use App\Modules\Core\Models\User;
use App\Modules\Sales\Models\Customer;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'crm_leads';

    protected $fillable = [
        'name',
        'company',
        'title',
        'email',
        'phone',
        'source',
        'status',
        'priority',
        'estimated_value',
        'currency',
        'assigned_to_user_id',
        'converted_customer_id',
        'converted_deal_id',
        'notes',
    ];

    protected $casts = [
        'estimated_value' => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'converted_customer_id');
    }

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class, 'converted_deal_id');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class, 'lead_id');
    }
}
