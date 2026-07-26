<?php

namespace App\Http\Controllers\Api\Technician;

use App\Http\Controllers\Controller;
use App\Models\OwnerReport;
use Illuminate\Http\Request;

class TechnicianOwnerReportController extends Controller
{
    public function index(Request $request)
    {
        $employeeId = $request->user()->employee?->id;

        if (!$employeeId) {
            return response()->json(['success' => true, 'data' => collect()]);
        }

        $reports = OwnerReport::with(['vehicle', 'owner'])
            ->where('technician_id', $employeeId)
            ->latest()
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $reports,
        ]);
    }

    public function show(Request $request, $id)
    {
        $employeeId = $request->user()->employee?->id;

        if (!$employeeId) {
            return response()->json(['success' => false, 'message' => 'Not a technician'], 403);
        }

        $report = OwnerReport::with(['vehicle', 'owner'])
            ->where('technician_id', $employeeId)
            ->find($id);

        if (!$report) {
            return response()->json(['success' => false, 'message' => 'Report not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    public function answer(Request $request, $id)
    {
        $employeeId = $request->user()->employee?->id;

        if (!$employeeId) {
            return response()->json(['success' => false, 'message' => 'Not a technician'], 403);
        }

        $report = OwnerReport::where('technician_id', $employeeId)->find($id);

        if (!$report) {
            return response()->json(['success' => false, 'message' => 'Report not found'], 404);
        }

        $validated = $request->validate([
            'question_id' => 'required|integer',
            'answer' => 'nullable|string',
        ]);

        $report->submitAnswers([[
            'question_id' => $validated['question_id'],
            'answer' => $validated['answer'],
        ]]);

        return response()->json([
            'success' => true,
            'message' => 'Answer saved',
        ]);
    }

    public function submit(Request $request, $id)
    {
        $employeeId = $request->user()->employee?->id;

        if (!$employeeId) {
            return response()->json(['success' => false, 'message' => 'Not a technician'], 403);
        }

        $report = OwnerReport::where('technician_id', $employeeId)->find($id);

        if (!$report) {
            return response()->json(['success' => false, 'message' => 'Report not found'], 404);
        }

        $validated = $request->validate([
            'technician_notes' => 'nullable|string',
            'technician_signature' => 'nullable|string',
        ]);

        $report->update([
            'technician_notes' => $validated['technician_notes'] ?? $report->technician_notes,
            'technician_signature' => $validated['technician_signature'] ?? $report->technician_signature,
            'technician_signed_at' => $validated['technician_signature'] ? now() : $report->technician_signed_at,
        ]);

        $report->submit();
        $report->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Report submitted',
            'data' => $report,
        ]);
    }
}
