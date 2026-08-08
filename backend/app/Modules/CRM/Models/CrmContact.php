<?php

declare(strict_types=1);

namespace App\Modules\CRM\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CrmContact extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'crm_contacts';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'company',
        'status',
    ];
}
