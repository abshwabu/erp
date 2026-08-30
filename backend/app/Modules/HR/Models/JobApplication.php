<?php

declare(strict_types=1);

namespace App\Modules\HR\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobApplication extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'hr_job_applications';

    protected $fillable = [
        'job_posting_id',
        'applicant_name',
        'applicant_email',
        'applicant_phone',
        'resume_url',
        'photo_url',
        'cover_letter',
        'custom_form_responses',
        'status',
        'rating',
        'notes',
        'submitted_at',
    ];

    protected $casts = [
        'custom_form_responses' => 'array',
        'submitted_at' => 'datetime',
        'rating' => 'integer',
    ];

    public function jobPosting(): BelongsTo
    {
        return $this->belongsTo(JobPosting::class, 'job_posting_id');
    }
}
