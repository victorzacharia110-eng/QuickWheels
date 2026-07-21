<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Services\GeminiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ContractAnalysisController extends Controller
{
    protected GeminiService $gemini;

    public function __construct(GeminiService $gemini)
    {
        $this->gemini = $gemini;
    }

    public function analyze(Request $request, $employeeId, $documentId)
    {
        $owner = $request->user()->owner;
        if (!$owner) {
            return response()->json(['success' => false, 'message' => 'Owner profile not found'], 404);
        }

        $employee = Employee::where('owner_id', $owner->id)->find($employeeId);

        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Employee not found'], 404);
        }

        $document = EmployeeDocument::where('employee_id', $employeeId)->find($documentId);

        if (!$document) {
            return response()->json(['success' => false, 'message' => 'Document not found'], 404);
        }

        $mime = $document->file_mime_type ?? '';
        $isImage = is_string($mime) && str_starts_with($mime, 'image/');
        $isPdf = $mime === 'application/pdf';

        if (!$isImage && !$isPdf) {
            return response()->json([
                'success' => false,
                'message' => 'AI analysis is only available for image and PDF files',
            ], 422);
        }

        try {
            $fullPath = Storage::disk('public')->path($document->file_path);

            if (!file_exists($fullPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'File not found on server. Please re-upload the document.',
                ], 422);
            }

            if ($isImage) {
                $imageData = base64_encode(file_get_contents($fullPath));
                $analysis = $this->gemini->analyzeDocumentImage($imageData, $mime);
            } else {
                $text = $this->extractPdfText($fullPath);
                if (!empty($text)) {
                    $analysis = $this->gemini->analyzeContract($text);
                } else {
                    $analysis = ['error' => 'Could not extract text from PDF. Try uploading an image instead.'];
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Document analyze error', ['message' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to read document file. Please re-upload.',
            ], 500);
        }

        if (isset($analysis['error'])) {
            return response()->json([
                'success' => false,
                'message' => $analysis['error'],
            ], 422);
        }

        $document->update([
            'ai_analysis' => $analysis,
            'ai_analyzed_at' => now(),
            'is_verified' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Document analyzed successfully',
            'data' => [
                'document' => $document->toApiResponse(),
                'analysis' => $analysis,
            ],
        ]);
    }

    public function analyzeText(Request $request)
    {
        $ownerId = $request->user()->owner->id;

        $request->validate([
            'text' => 'required|string|max:50000',
        ]);

        $analysis = $this->gemini->analyzeContract($request->text);

        if (isset($analysis['error'])) {
            return response()->json([
                'success' => false,
                'message' => $analysis['error'],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'data' => ['analysis' => $analysis],
        ]);
    }

    protected function extractPdfText(string $path): string
    {
        try {
            $content = file_get_contents($path);
            $text = '';

            if (preg_match_all('/BT\s*.*?\s*ET/s', $content, $matches)) {
                foreach ($matches[0] as $block) {
                    if (preg_match_all('/\((.*?)\)/s', $block, $textMatches)) {
                        $text .= implode(' ', $textMatches[1]) . "\n";
                    }
                }
            }

            return trim($text);
        } catch (\Exception $e) {
            return '';
        }
    }
}
