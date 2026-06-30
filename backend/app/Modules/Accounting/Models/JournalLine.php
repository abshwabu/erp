<?php

declare(strict_types=1);

namespace App\Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class JournalLine extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'accounting_journal_lines';

    protected $fillable = [
        'journal_id',
        'account_id',
        'debit_cents',
        'credit_cents',
        'currency_code',
        'base_debit_cents',
        'base_credit_cents',
        'exchange_rate',
        'description',
        'cost_center_id',
        'project_id',
        'employee_id',
    ];

    protected $casts = [
        'debit_cents' => 'integer',
        'credit_cents' => 'integer',
        'base_debit_cents' => 'integer',
        'base_credit_cents' => 'integer',
        'exchange_rate' => 'decimal:6',
    ];

    public function journal()
    {
        return $this->belongsTo(Journal::class, 'journal_id');
    }

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }
}
