<?php

declare(strict_types=1);

namespace App\Modules\HR\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\HR\Models\Employee;
use App\Modules\HR\Models\EmployeeDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EmployeeDocumentController extends Controller
{
    public function index(Request $request, string $employeeId)
    {
        $employee = Employee::findOrFail($employeeId);

        $query = EmployeeDocument::where('employee_id', $employee->id);

        if ($type = $request->query('document_type')) {
            $query->where('document_type', $type);
        }

        $documents = $query->orderBy('created_at', 'desc')->get();

        return response()->json($documents);
    }

    public function store(Request $request, string $employeeId)
    {
        $employee = Employee::findOrFail($employeeId);

        $request->validate([
            'title' => 'required|string|max:255',
            'document_type' => 'required|string|in:cv,contract,education,id_proof,certification,tax,other',
            'file' => 'required|file|max:25600', // up to 25MB
            'expiry_date' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
        ]);

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $mimeType = $file->getMimeType();
        $fileSize = $file->getSize();

        // Store file under employee directory
        $path = $file->store("hr/employees/{$employee->id}/documents", 'public');

        $document = EmployeeDocument::create([
            'employee_id' => $employee->id,
            'title' => $request->input('title'),
            'document_type' => $request->input('document_type'),
            'file_path' => $path,
            'file_name' => $originalName,
            'file_size' => $fileSize,
            'file_type' => $mimeType,
            'expiry_date' => $request->input('expiry_date') ?: null,
            'notes' => $request->input('notes') ?: null,
            'uploaded_by_user_id' => auth()->id(),
        ]);

        return response()->json($document, 201);
    }

    public function show(string $employeeId, string $documentId)
    {
        $document = EmployeeDocument::where('employee_id', $employeeId)->findOrFail($documentId);

        return response()->json($document);
    }

    public function download(string $employeeId, string $documentId)
    {
        $document = EmployeeDocument::where('employee_id', $employeeId)->findOrFail($documentId);

        if (!Storage::disk('public')->exists($document->file_path)) {
            return response()->json(['message' => 'Document file not found on disk'], 404);
        }

        return Storage::disk('public')->download($document->file_path, $document->file_name);
    }

    public function destroy(string $employeeId, string $documentId)
    {
        $document = EmployeeDocument::where('employee_id', $employeeId)->findOrFail($documentId);

        if (Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }

        $document->delete();

        return response()->json(null, 204);
    }
}
