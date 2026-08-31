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

class Deal extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'crm_deals';

    protected $fillable = [
        'title',
        'customer_id',
        'lead_id',
        'stage',
        'amount',
        'currency',
        'probability',
        'expected_close_date',
        'actual_close_date',
        'assigned_to_user_id',
        'lost_reason',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'probability' => 'integer',
        'expected_close_date' => 'date',
        'actual_close_date' => 'date',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class, 'deal_id');
    }
}
