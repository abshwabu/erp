<?php

declare(strict_types=1);

namespace App\Modules\CRM\Models;

use App\Modules\Core\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class LeadForm extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'crm_lead_forms';

    protected $fillable = [
        'title',
        'slug',
        'source',
        'form_type',
        'headline',
        'description',
        'custom_questions',
        'thank_you_title',
        'thank_you_message',
        'redirect_url',
        'default_priority',
        'default_estimated_value',
        'assigned_to_user_id',
        'is_active',
        'views_count',
        'submissions_count',
        'theme_color',
        'created_by_user_id',
    ];

    protected $casts = [
        'custom_questions' => 'array',
        'is_active' => 'boolean',
        'default_estimated_value' => 'decimal:2',
        'views_count' => 'integer',
        'submissions_count' => 'integer',
    ];

    protected $appends = [
        'conversion_rate',
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

    public function getConversionRateAttribute(): float
    {
        if ($this->views_count <= 0) {
            return 0.0;
        }

        return round(($this->submissions_count / $this->views_count) * 100, 1);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class, 'lead_form_id');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
