<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentController extends Controller
{
    public function index(Request $request, $employeeId)
    {
        $ownerId = $request->user()->owner->id;
        $employee = Employee::where('owner_id', $ownerId)->find($employeeId);

        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Employee not found'], 404);
        }

        $documents = EmployeeDocument::where('employee_id', $employeeId)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($d) => $d->toApiResponse());

        return response()->json(['success' => true, 'data' => $documents]);
    }

    public function store(Request $request, $employeeId)
    {
        $ownerId = $request->user()->owner->id;
        $employee = Employee::where('owner_id', $ownerId)->find($employeeId);

        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Employee not found'], 404);
        }

        $request->validate([
            'file' => 'required|file|max:20480|mimes:pdf,jpg,jpeg,png,doc,docx',
            'document_type' => 'required|string|in:contract,license,nida,insurance,medical,background_check,other',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'expires_at' => 'nullable|date|after:today',
        ]);

        $file = $request->file('file');
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('documents/employees/' . $employee->id, $filename, 'public');

        $document = EmployeeDocument::create([
            'employee_id' => $employee->id,
            'owner_id' => $ownerId,
            'document_type' => $request->document_type,
            'title' => $request->title,
            'description' => $request->description,
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'expires_at' => $request->expires_at,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Document uploaded successfully',
            'data' => $document->toApiResponse(),
        ], 201);
    }

    public function show(Request $request, $employeeId, $documentId)
    {
        $ownerId = $request->user()->owner->id;
        $document = EmployeeDocument::where('employee_id', $employeeId)
            ->whereHas('employee', fn($q) => $q->where('owner_id', $ownerId))
            ->find($documentId);

        if (!$document) {
            return response()->json(['success' => false, 'message' => 'Document not found'], 404);
        }

        return response()->json(['success' => true, 'data' => $document->toApiResponse()]);
    }

    public function download(Request $request, $employeeId, $documentId)
    {
        $ownerId = $request->user()->owner->id;
        $document = EmployeeDocument::where('employee_id', $employeeId)
            ->whereHas('employee', fn($q) => $q->where('owner_id', $ownerId))
            ->find($documentId);

        if (!$document) {
            return response()->json(['success' => false, 'message' => 'Document not found'], 404);
        }

        return Storage::disk('public')->download($document->file_path, $document->file_name);
    }

    public function destroy(Request $request, $employeeId, $documentId)
    {
        $ownerId = $request->user()->owner->id;
        $document = EmployeeDocument::where('employee_id', $employeeId)
            ->whereHas('employee', fn($q) => $q->where('owner_id', $ownerId))
            ->find($documentId);

        if (!$document) {
            return response()->json(['success' => false, 'message' => 'Document not found'], 404);
        }

        Storage::disk('public')->delete($document->file_path);
        $document->delete();

        return response()->json(['success' => true, 'message' => 'Document deleted successfully']);
    }

    public function verify(Request $request, $employeeId, $documentId)
    {
        $ownerId = $request->user()->owner->id;
        $document = EmployeeDocument::where('employee_id', $employeeId)
            ->whereHas('employee', fn($q) => $q->where('owner_id', $ownerId))
            ->find($documentId);

        if (!$document) {
            return response()->json(['success' => false, 'message' => 'Document not found'], 404);
        }

        $document->update(['is_verified' => !$document->is_verified]);

        return response()->json([
            'success' => true,
            'message' => $document->is_verified ? 'Document verified' : 'Verification removed',
            'data' => $document->toApiResponse(),
        ]);
    }
}
