<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class AccountType extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'accounting_account_types';

    protected $fillable = [
        'name',
        'normal_balance',
        'report_section',
        'sort_order',
    ];

    public function accounts()
    {
        return $this->hasMany(Account::class, 'account_type_id');
    }
}
