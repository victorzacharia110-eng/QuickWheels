<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Controller;
use App\Models\OwnerReport;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OwnerReportController extends Controller
{
    public function index(Request $request)
    {
        $ownerId = $request->user()->owner->id;

        $query = OwnerReport::with(['vehicle', 'technician'])
            ->where('owner_id', $ownerId)
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhereHas('vehicle', function ($vq) use ($search) {
                      $vq->where('name', 'like', "%{$search}%")
                         ->orWhere('registration', 'like', "%{$search}%");
                  });
            });
        }

        $reports = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $reports,
        ]);
    }

    public function stats(Request $request)
    {
        $ownerId = $request->user()->owner->id;
        $base = OwnerReport::where('owner_id', $ownerId);

        return response()->json([
            'success' => true,
            'data' => [
                'total' => (clone $base)->count(),
                'draft' => (clone $base)->where('status', 'draft')->count(),
                'pending' => (clone $base)->where('status', 'pending_technician')->count(),
                'submitted' => (clone $base)->where('status', 'submitted')->count(),
                'reviewed' => (clone $base)->where('status', 'reviewed')->count(),
                'verified' => (clone $base)->where('status', 'verified')->count(),
                'completed' => (clone $base)->where('status', 'completed')->count(),
            ],
        ]);
    }

    public function show(Request $request, $id)
    {
        $ownerId = $request->user()->owner->id;

        $report = OwnerReport::with(['vehicle', 'technician'])
            ->where('owner_id', $ownerId)
            ->find($id);

        if (!$report) {
            return response()->json(['success' => false, 'message' => 'Report not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'technician_id' => 'nullable|exists:employees,id',
            'questions' => 'required|array|min:1',
            'questions.*.question' => 'required|string',
            'questions.*.type' => 'nullable|string|in:text,textarea,checkbox,radio',
            'questions.*.options' => 'nullable|array',
            'questions.*.required' => 'nullable|boolean',
            'status' => 'nullable|string|in:draft,pending_technician',
        ]);

        $ownerId = $request->user()->owner->id;

        $report = OwnerReport::create([
            'owner_id' => $ownerId,
            'vehicle_id' => $validated['vehicle_id'] ?? null,
            'technician_id' => $validated['technician_id'] ?? null,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'] ?? 'draft',
        ]);

        $report->setQuestions($validated['questions']);
        $report->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Report created',
            'data' => $report,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $ownerId = $request->user()->owner->id;

        $report = OwnerReport::where('owner_id', $ownerId)->find($id);

        if (!$report) {
            return response()->json(['success' => false, 'message' => 'Report not found'], 404);
        }

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'technician_id' => 'nullable|exists:employees,id',
            'questions' => 'sometimes|required|array|min:1',
            'questions.*.question' => 'required_with:questions|string',
            'questions.*.type' => 'nullable|string|in:text,textarea,checkbox,radio',
            'questions.*.options' => 'nullable|array',
            'questions.*.required' => 'nullable|boolean',
            'status' => 'nullable|string|in:draft,pending_technician',
        ]);

        $updateData = array_filter([
            'title' => $validated['title'] ?? null,
            'description' => $validated['description'] ?? null,
            'vehicle_id' => $validated['vehicle_id'] ?? null,
            'technician_id' => $validated['technician_id'] ?? null,
            'status' => $validated['status'] ?? null,
        ], fn($v) => $v !== null);

        $report->update($updateData);

        if (isset($validated['questions'])) {
            $report->setQuestions($validated['questions']);
            $report->refresh();
        }

        return response()->json([
            'success' => true,
            'message' => 'Report updated',
            'data' => $report,
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $ownerId = $request->user()->owner->id;

        $report = OwnerReport::where('owner_id', $ownerId)->find($id);

        if (!$report) {
            return response()->json(['success' => false, 'message' => 'Report not found'], 404);
        }

        $report->delete();

        return response()->json([
            'success' => true,
            'message' => 'Report deleted',
        ]);
    }

    public function answer(Request $request, $id)
    {
        $ownerId = $request->user()->owner->id;

        $report = OwnerReport::where('owner_id', $ownerId)->find($id);

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

    public function review(Request $request, $id)
    {
        $ownerId = $request->user()->owner->id;

        $report = OwnerReport::where('owner_id', $ownerId)->find($id);

        if (!$report) {
            return response()->json(['success' => false, 'message' => 'Report not found'], 404);
        }

        $report->review();
        $report->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Report reviewed',
            'data' => $report,
        ]);
    }

    public function verify(Request $request, $id)
    {
        $ownerId = $request->user()->owner->id;

        $report = OwnerReport::where('owner_id', $ownerId)->find($id);

        if (!$report) {
            return response()->json(['success' => false, 'message' => 'Report not found'], 404);
        }

        $report->verify(
            $request->input('owner_signature'),
            $request->input('owner_notes')
        );
        $report->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Report verified',
            'data' => $report,
        ]);
    }

    public function complete(Request $request, $id)
    {
        $ownerId = $request->user()->owner->id;

        $report = OwnerReport::where('owner_id', $ownerId)->find($id);

        if (!$report) {
            return response()->json(['success' => false, 'message' => 'Report not found'], 404);
        }

        $report->complete();
        $report->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Report completed',
            'data' => $report,
        ]);
    }

    public function cancel(Request $request, $id)
    {
        $ownerId = $request->user()->owner->id;

        $report = OwnerReport::where('owner_id', $ownerId)->find($id);

        if (!$report) {
            return response()->json(['success' => false, 'message' => 'Report not found'], 404);
        }

        $report->cancel();
        $report->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Report cancelled',
            'data' => $report,
        ]);
    }

    public function technicians(Request $request)
    {
        $ownerId = $request->user()->owner->id;

        $technicians = Employee::where('owner_id', $ownerId)
            ->where('position', 'Technician')
            ->where('status', 'active')
            ->select('id', 'name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $technicians,
        ]);
    }

    public function exportPdf(Request $request, $id)
    {
        return $this->export($request, $id, 'pdf');
    }

    public function exportDocx(Request $request, $id)
    {
        return $this->export($request, $id, 'docx');
    }

    public function exportXlsx(Request $request, $id)
    {
        return $this->export($request, $id, 'xlsx');
    }

    private function export(Request $request, $id, $format)
    {
        $ownerId = $request->user()->owner->id;

        $report = OwnerReport::with(['vehicle', 'technician'])
            ->where('owner_id', $ownerId)
            ->find($id);

        if (!$report) {
            return response()->json(['success' => false, 'message' => 'Report not found'], 404);
        }

        if ($format === 'pdf') {
            $html = $this->buildHtml($report);
            $pdf = \Barryvdh\DomPDF\PDF::loadHtml($html);
            $pdf->setPaper('a4');
            return $pdf->download("report-{$report->id}.pdf");
        }

        if ($format === 'docx') {
            $phpWord = new \PhpOffice\PhpWord\PhpWord();
            $section = $phpWord->addSection();
            $section->addTitle($report->title, 1);
            if ($report->description) {
                $section->addParagraph($report->description);
            }
            $section->addParagraph("Status: {$report->status}");
            $section->addParagraph("Created: {$report->created_at}");
            foreach ($report->questions ?? [] as $q) {
                $section->addHeading($q['question'], 2);
                $section->addParagraph('Answer: ' . ($q['answer'] ?? 'Not answered'));
            }
            $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
            $path = storage_path("report-{$report->id}.docx");
            $writer->save($path);
            return response()->download($path)->deleteFileAfterSend(true);
        }

        if ($format === 'xlsx') {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setCellValue('A1', 'Title');
            $sheet->setCellValue('B1', $report->title);
            $sheet->setCellValue('A2', 'Status');
            $sheet->setCellValue('B2', $report->status);
            $sheet->setCellValue('A3', 'Created');
            $sheet->setCellValue('B3', $report->created_at);
            $row = 5;
            foreach ($report->questions ?? [] as $q) {
                $sheet->setCellValue("A{$row}", $q['question']);
                $sheet->setCellValue("B{$row}", $q['answer'] ?? 'Not answered');
                $row++;
            }
            $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
            $path = storage_path("report-{$report->id}.xlsx");
            $writer->save($path);
            return response()->download($path)->deleteFileAfterSend(true);
        }
    }

    private function buildHtml($report)
    {
        $questionsHtml = '';
        foreach ($report->questions ?? [] as $q) {
            $questionsHtml .= "<tr><td>{$q['question']}</td><td>" . ($q['answer'] ?? 'N/A') . "</td></tr>";
        }

        return "<!DOCTYPE html><html><head><style>body{font-family:sans-serif}table{width:100%;border-collapse:collapse}td,th{border:1px solid #ccc;padding:8px;text-align:left}</style></head><body>
        <h1>{$report->title}</h1>
        <p>{$report->description}</p>
        <p><strong>Status:</strong> {$report->status}</p>
        <p><strong>Created:</strong> {$report->created_at}</p>
        <h2>Questions</h2>
        <table><tr><th>Question</th><th>Answer</th></tr>{$questionsHtml}</table>
        </body></html>";
    }
}
