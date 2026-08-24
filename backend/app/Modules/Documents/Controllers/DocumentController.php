<?php

declare(strict_types=1);

namespace App\Modules\Documents\Controllers;

use App\Http\Controllers\BaseController;
use App\Modules\Documents\Models\Document;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $query = Document::with('uploader:id,name,email')
            ->orderByDesc('created_at');

        if ($request->filled('folder')) {
            $query->where('folder', $request->input('folder'));
        }

        if ($request->filled('search')) {
            $search = '%' . $request->input('search') . '%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                  ->orWhere('file_name', 'like', $search)
                  ->orWhere('description', 'like', $search);
            });
        }

        $docs = $query->paginate(25);

        return $this->successResponse($docs);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'folder'      => ['sometimes', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'tags'        => ['nullable', 'array'],
            'file'        => ['required', 'file', 'max:20480'], // max 20MB
        ]);

        $uploadedFile = $request->file('file');
        $tenantId = tenant()?->getKey() ?? 'default';
        $path = $uploadedFile->store("tenants/{$tenantId}/documents");

        $doc = Document::create([
            'name'            => $data['name'],
            'file_path'       => $path,
            'file_name'       => $uploadedFile->getClientOriginalName(),
            'mime_type'       => $uploadedFile->getClientMimeType() ?: 'application/octet-stream',
            'file_size_bytes' => $uploadedFile->getSize(),
            'folder'          => $data['folder'] ?? 'general',
            'tags'            => $data['tags'] ?? null,
            'description'     => $data['description'] ?? null,
            'uploaded_by'     => $request->user()?->id,
        ]);

        return $this->createdResponse($doc->load('uploader:id,name'));
    }

    public function show(string $id): JsonResponse
    {
        $doc = Document::with('uploader:id,name,email')->findOrFail($id);

        return $this->successResponse($doc);
    }

    public function download(string $id): StreamedResponse|JsonResponse
    {
        $doc = Document::findOrFail($id);

        if (!Storage::exists($doc->file_path)) {
            return $this->errorResponse('File not found on storage.', 404);
        }

        return Storage::download($doc->file_path, $doc->file_name);
    }

    public function destroy(string $id): JsonResponse
    {
        $doc = Document::findOrFail($id);

        if (Storage::exists($doc->file_path)) {
            Storage::delete($doc->file_path);
        }

        $doc->delete();

        return $this->noContentResponse();
    }
}
