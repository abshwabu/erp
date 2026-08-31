<?php

declare(strict_types=1);

namespace App\Modules\Support\Models;

use App\Modules\Core\Models\User;
use App\Modules\Sales\Models\Customer;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicket extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'support_tickets';

    protected $fillable = [
        'ticket_number',
        'subject',
        'customer_id',
        'assigned_to',
        'contact_email',
        'contact_name',
        'status',
        'priority',
        'channel',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportTicketMessage::class, 'ticket_id');
    }

    public static function nextNumber(): string
    {
        $last = static::query()->orderByDesc('ticket_number')->value('ticket_number');
        $seq  = $last ? ((int) str_replace('TCK-', '', $last)) + 1 : 1;

        return 'TCK-' . str_pad((string) $seq, 5, '0', STR_PAD_LEFT);
    }
}
