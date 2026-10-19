<?php

declare(strict_types=1);

namespace App\Modules\Finance\Models;

use App\Modules\Accounting\Models\Account;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinanceCategory extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'finance_categories';

    protected $fillable = [
        'name',
        'type',
        'color',
        'description',
        'account_id',
        'is_system',
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(FinanceTransaction::class, 'category_id');
    }
}
