<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('name');
            $t->string('file_path');
            $t->string('file_name');
            $t->string('mime_type')->default('application/octet-stream');
            $t->unsignedBigInteger('file_size_bytes')->default(0);
            $t->string('folder')->default('general');
            $t->json('tags')->nullable();
            $t->text('description')->nullable();
            $t->uuid('uploaded_by')->nullable(); // references users.id
            $t->timestamps();

            $t->foreign('uploaded_by')->references('id')->on('users')->nullOnDelete();
            $t->index(['folder', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
