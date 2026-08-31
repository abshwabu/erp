<?php

declare(strict_types=1);

namespace App\Modules\Support\Models;

use App\Modules\Core\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class SupportKnowledgeArticle extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'support_knowledge_articles';

    protected $fillable = [
        'title',
        'slug',
        'category',
        'content',
        'summary',
        'is_published',
        'views_count',
        'helpful_count',
        'author_id',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'views_count' => 'integer',
        'helpful_count' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->title) . '-' . Str::lower(Str::random(5));
            }
        });
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
