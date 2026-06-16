<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Models\User;

class Journal extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $table = 'accounting_journals';

    protected $fillable = [
        'reference',
        'description',
        'journal_date',
        'period_id',
        'status',
        'source_type',
        'source_id',
        'reversal_of_id',
        'posted_at',
        'posted_by_id',
    ];

    protected $casts = [
        'journal_date' => 'date',
        'posted_at' => 'datetime',
    ];

    public function period()
    {
        return $this->belongsTo(FiscalPeriod::class, 'period_id');
    }

    public function lines()
    {
        return $this->hasMany(JournalLine::class, 'journal_id');
    }

    public function reversalOf()
    {
        return $this->belongsTo(Journal::class, 'reversal_of_id');
    }

    public function postedBy()
    {
        return $this->belongsTo(User::class, 'posted_by_id');
    }
}
