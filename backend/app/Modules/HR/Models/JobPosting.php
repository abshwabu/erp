<?php

declare(strict_types=1);

namespace App\Modules\HR\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class JobPosting extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'hr_job_postings';

    protected $fillable = [
        'title',
        'slug',
        'department_id',
        'position_id',
        'location',
        'employment_type',
        'experience_level',
        'min_salary',
        'max_salary',
        'salary_currency',
        'description',
        'requirements',
        'benefits',
        'deadline',
        'status',
        'custom_form_schema',
        'views_count',
        'created_by_user_id',
    ];

    protected $casts = [
        'custom_form_schema' => 'array',
        'deadline' => 'date',
        'min_salary' => 'decimal:2',
        'max_salary' => 'decimal:2',
        'views_count' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->slug)) {
                $baseSlug = Str::slug($model->title);
                $model->slug = $baseSlug . '-' . Str::lower(Str::random(6));
            }
        });
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'position_id');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class, 'job_posting_id');
    }
}
