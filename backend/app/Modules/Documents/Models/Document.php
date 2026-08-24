<?php

declare(strict_types=1);

namespace App\Modules\Documents\Models;

use App\Modules\Core\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'documents';

    protected $fillable = [
        'name',
        'file_path',
        'file_name',
        'mime_type',
        'file_size_bytes',
        'folder',
        'tags',
        'description',
        'uploaded_by',
    ];

    protected $casts = [
        'file_size_bytes' => 'integer',
        'tags'            => 'array',
    ];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
