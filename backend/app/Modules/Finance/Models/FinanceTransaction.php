<?php

declare(strict_types=1);

namespace App\Modules\Finance\Models;

use App\Modules\Accounting\Models\Account;
use App\Modules\Accounting\Models\Journal;
use App\Modules\Core\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinanceTransaction extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'finance_transactions';

    protected $fillable = [
        'transaction_number',
        'type',
        'category_id',
        'account_id',
        'payment_account_id',
        'journal_id',
        'amount_cents',
        'currency_code',
        'transaction_date',
        'payment_method',
        'reference',
        'payee_payer',
        'description',
        'status',
        'receipt_url',
        'created_by_id',
    ];

    protected $casts = [
        'amount_cents'     => 'integer',
        'transaction_date' => 'date:Y-m-d',
    ];

    protected $appends = [
        'amount',
    ];

    public function getAmountAttribute(): float
    {
        return round(($this->amount_cents ?? 0) / 100, 2);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(FinanceCategory::class, 'category_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function paymentAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'payment_account_id');
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class, 'journal_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }
}
