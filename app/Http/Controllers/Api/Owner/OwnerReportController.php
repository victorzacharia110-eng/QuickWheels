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

        $questions = array_map(function ($q, $i) {
            if (!isset($q['id'])) $q['id'] = $i + 1;
            if (!isset($q['type'])) $q['type'] = 'text';
            if (!isset($q['required'])) $q['required'] = false;
            return $q;
        }, $validated['questions'], array_keys($validated['questions']));

        $report = OwnerReport::create([
            'owner_id' => $ownerId,
            'vehicle_id' => $validated['vehicle_id'] ?? null,
            'technician_id' => $validated['technician_id'] ?? null,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'] ?? 'draft',
            'questions' => $questions,
        ]);

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
            $pdf = app(\Barryvdh\DomPDF\PDF::class)->loadHtml($html);
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
        $status = $report->status;
        $statusColors = [
            'draft' => '#6C63FF',
            'pending_technician' => '#3b82f6',
            'submitted' => '#FFD93D',
            'reviewed' => '#a855f7',
            'verified' => '#00C4D4',
            'completed' => '#4ADE80',
            'cancelled' => '#ff6b6b',
        ];
        $statusColor = $statusColors[$status] ?? '#6C63FF';
        $statusLabel = ucfirst(str_replace('_', ' ', $status));
        $vehicleName = $report->vehicle ? "{$report->vehicle->name} ({$report->vehicle->registration})" : 'N/A';
        $technicianName = $report->technician ? $report->technician->name : 'Unassigned';

        $questionsHtml = '';
        $num = 1;
        foreach ($report->questions ?? [] as $q) {
            $answer = $q['answer'] ?? '<span style="color:#999;font-style:italic">Not answered</span>';
            $bg = $num % 2 === 0 ? '#f8fafc' : '#ffffff';
            $questionsHtml .= "<tr style=\"background:{$bg}\">
                <td style=\"padding:10px 14px;border-bottom:1px solid #e2e8f0;font-weight:600;color:#1e293b;width:5%\">{$num}</td>
                <td style=\"padding:10px 14px;border-bottom:1px solid #e2e8f0;color:#334155;width:50%\">{$q['question']}</td>
                <td style=\"padding:10px 14px;border-bottom:1px solid #e2e8f0;color:#0f172a;width:45%\">{$answer}</td>
            </tr>";
            $num++;
        }

        $signatureHtml = '';
        if ($report->technician_signature || $report->owner_signature) {
            $sigContent = '';
            if ($report->technician_signature) {
                $sigDate = $report->technician_signed_at ? $report->technician_signed_at->format('M d, Y, h:i A') : '';
                $sigContent .= "<div style=\"text-align:center;flex:1\">
                    <div style=\"font-size:9px;text-transform:uppercase;letter-spacing:1px;color:#94a3b8;margin-bottom:8px;font-weight:600\">Technician Signature</div>
                    <img src=\"{$report->technician_signature}\" style=\"max-height:80px;max-width:200px;border:1px solid #e2e8f0;border-radius:6px;padding:8px;background:#fff\" />
                    <div style=\"font-size:8px;color:#94a3b8;margin-top:4px\">{$technicianName} &mdash; {$sigDate}</div>
                </div>";
            }
            if ($report->owner_signature) {
                $sigDate = $report->owner_signed_at ? $report->owner_signed_at->format('M d, Y, h:i A') : '';
                $sigContent .= "<div style=\"text-align:center;flex:1\">
                    <div style=\"font-size:9px;text-transform:uppercase;letter-spacing:1px;color:#94a3b8;margin-bottom:8px;font-weight:600\">Owner Signature</div>
                    <img src=\"{$report->owner_signature}\" style=\"max-height:80px;max-width:200px;border:1px solid #e2e8f0;border-radius:6px;padding:8px;background:#fff\" />
                    <div style=\"font-size:8px;color:#94a3b8;margin-top:4px\">{$sigDate}</div>
                </div>";
            }
            $signatureHtml = "<div style=\"background:#f1f5f9;border-radius:8px;padding:20px;margin-top:24px;border:1px solid #e2e8f0\">
                <div style=\"font-size:10px;text-transform:uppercase;letter-spacing:1.5px;color:#00C4D4;font-weight:700;margin-bottom:14px\">Signatures</div>
                <div style=\"display:flex;gap:20px;justify-content:center\">{$sigContent}</div>
            </div>";
        }

        $notesHtml = '';
        if ($report->technician_notes || $report->owner_notes) {
            $notesContent = '';
            if ($report->technician_notes) {
                $notesContent .= "<div style=\"margin-bottom:8px\"><span style=\"font-weight:700;color:#00C4D4;font-size:9px;text-transform:uppercase;letter-spacing:1px\">Technician:</span> <span style=\"color:#334155\">{$report->technician_notes}</span></div>";
            }
            if ($report->owner_notes) {
                $notesContent .= "<div><span style=\"font-weight:700;color:#4ADE80;font-size:9px;text-transform:uppercase;letter-spacing:1px\">Owner:</span> <span style=\"color:#334155\">{$report->owner_notes}</span></div>";
            }
            $notesHtml = "<div style=\"background:#f8fafc;border-radius:8px;padding:16px;margin-top:20px;border:1px solid #e2e8f0\">
                <div style=\"font-size:10px;text-transform:uppercase;letter-spacing:1.5px;color:#00C4D4;font-weight:700;margin-bottom:10px\">Notes</div>
                {$notesContent}
            </div>";
        }

        return "<!DOCTYPE html>
<html>
<head>
    <meta charset=\"UTF-8\">
    <style>
        @page { margin: 40px 30px; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #1e293b; line-height: 1.5; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    </style>
</head>
<body>
    <!-- Header Bar -->
    <div style=\"background:linear-gradient(135deg, #0a0818, #1a1640);padding:24px 30px;border-radius:0 0 12px 12px;margin-bottom:28px\">
        <div style=\"display:flex;justify-content:space-between;align-items:center\">
            <div>
                <div style=\"font-size:18px;font-weight:800;color:#ffffff;letter-spacing:0.5px\">QuickWheels</div>
                <div style=\"font-size:9px;color:rgba(255,255,255,0.5);text-transform:uppercase;letter-spacing:2px;margin-top:2px\">Fleet Management Report</div>
            </div>
            <div style=\"text-align:right\">
                <div style=\"display:inline-block;background:{$statusColor};color:#fff;padding:4px 12px;border-radius:20px;font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:1px\">{$statusLabel}</div>
                <div style=\"font-size:8px;color:rgba(255,255,255,0.4);margin-top:4px\">{$report->created_at?->format('M d, Y \\a\\t h:i A')}</div>
            </div>
        </div>
    </div>

    <!-- Report Title -->
    <div style=\"padding:0 10px\">
        <h1 style=\"font-size:22px;font-weight:800;color:#0a0818;margin-bottom:6px\">{$report->title}</h1>
        " . ($report->description ? "<p style=\"font-size:11px;color:#64748b;margin-bottom:18px;line-height:1.6\">{$report->description}</p>" : "") . "

        <!-- Info Grid -->
        <div style=\"display:flex;gap:12px;margin-bottom:24px\">
            <div style=\"flex:1;background:#f1f5f9;border-radius:8px;padding:14px;border:1px solid #e2e8f0\">
                <div style=\"font-size:8px;text-transform:uppercase;letter-spacing:1.5px;color:#00C4D4;font-weight:700;margin-bottom:4px\">Vehicle</div>
                <div style=\"font-size:11px;font-weight:600;color:#0f172a\">{$vehicleName}</div>
            </div>
            <div style=\"flex:1;background:#f1f5f9;border-radius:8px;padding:14px;border:1px solid #e2e8f0\">
                <div style=\"font-size:8px;text-transform:uppercase;letter-spacing:1.5px;color:#00C4D4;font-weight:700;margin-bottom:4px\">Technician</div>
                <div style=\"font-size:11px;font-weight:600;color:#0f172a\">{$technicianName}</div>
            </div>
            <div style=\"flex:1;background:#f1f5f9;border-radius:8px;padding:14px;border:1px solid #e2e8f0\">
                <div style=\"font-size:8px;text-transform:uppercase;letter-spacing:1.5px;color:#00C4D4;font-weight:700;margin-bottom:4px\">Report ID</div>
                <div style=\"font-size:11px;font-weight:600;color:#0f172a\">#{$report->id}</div>
            </div>
        </div>

        <!-- Questions Table -->
        <div style=\"margin-bottom:24px\">
            <div style=\"font-size:10px;text-transform:uppercase;letter-spacing:1.5px;color:#00C4D4;font-weight:700;margin-bottom:10px\">Questions & Answers</div>
            <table style=\"width:100%;border-collapse:collapse;border-radius:8px;overflow:hidden;border:1px solid #e2e8f0\">
                <thead>
                    <tr style=\"background:linear-gradient(135deg, #0a0818, #1a1640)\">
                        <th style=\"padding:10px 14px;text-align:left;font-size:9px;text-transform:uppercase;letter-spacing:1px;color:#ffffff;font-weight:700;width:5%\">#</th>
                        <th style=\"padding:10px 14px;text-align:left;font-size:9px;text-transform:uppercase;letter-spacing:1px;color:#ffffff;font-weight:700;width:50%\">Question</th>
                        <th style=\"padding:10px 14px;text-align:left;font-size:9px;text-transform:uppercase;letter-spacing:1px;color:#ffffff;font-weight:700;width:45%\">Answer</th>
                    </tr>
                </thead>
                <tbody>{$questionsHtml}</tbody>
            </table>
        </div>

        <!-- Signatures -->
        {$signatureHtml}

        <!-- Notes -->
        {$notesHtml}

        <!-- Footer -->
        <div style=\"margin-top:30px;padding-top:14px;border-top:1px solid #e2e8f0;display:flex;justify-content:space-between;align-items:center\">
            <div style=\"font-size:8px;color:#94a3b8\">Generated by QuickWheels Fleet Management</div>
            <div style=\"font-size:8px;color:#94a3b8\">{$report->created_at?->format('M d, Y \\a\\t h:i A')}</div>
        </div>
    </div>
</body>
</html>";
    }
}
