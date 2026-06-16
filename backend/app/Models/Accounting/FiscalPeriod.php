<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class FiscalPeriod extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'accounting_fiscal_periods';

    protected $fillable = [
        'year',
        'month',
        'start_date',
        'end_date',
        'status',
    ];

    public function journals()
    {
        return $this->hasMany(Journal::class, 'period_id');
    }

    public function isOpen()
    {
        return $this->status === 'open';
    }
}
