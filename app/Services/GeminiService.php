<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected string $apiKey;
    protected string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta';

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key', '');
    }

    public function analyzeContract(string $text): array
    {
        if (empty($this->apiKey)) {
            return ['error' => 'Gemini API key not configured'];
        }

        $prompt = <<<PROMPT
You are a contract analysis AI. Analyze the following contract text and extract structured data. Return ONLY valid JSON (no markdown, no code fences) with these fields:

{
  "contract_type": "rental" or "hire_purchase",
  "parties": {
    "owner": "owner/company name",
    "driver": "driver name"
  },
  "vehicle": {
    "type": "vehicle type",
    "make": "make/brand",
    "model": "model",
    "plate_number": "license plate",
    "year": "year if available"
  },
  "terms": {
    "start_date": "YYYY-MM-DD or null",
    "end_date": "YYYY-MM-DD or null",
    "duration_months": number or null,
    "monthly_payment": number or null,
    "total_amount": number or null,
    "deposit": number or null,
    "late_fee_per_day": number or null
  },
  "conditions": [
    "list of key conditions/obligations"
  ],
  "restrictions": [
    "list of restrictions"
  ],
  "insurance": {
    "required": boolean,
    "provider": "insurance provider or null",
    "coverage": "coverage type or null"
  },
  "mileage_limit": "daily/weekly limit or null",
  "fuel_policy": "fuel policy description or null",
  "penalties": [
    {"type": "type", "amount": "amount or description"}
  ],
  "special_notes": ["any other important notes"]
}

Contract text:
{$text}
PROMPT;

        try {
            $response = Http::timeout(60)
                ->post("{$this->baseUrl}/models/gemini-2.0-flash:generateContent?key={$this->apiKey}", [
                    'contents' => [
                        ['parts' => [['text' => $prompt]]],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.1,
                        'maxOutputTokens' => 4096,
                    ],
                ]);

            if ($response->failed()) {
                Log::error('Gemini API error', ['status' => $response->status(), 'body' => $response->body()]);
                return ['error' => 'AI analysis failed: ' . $response->body()];
            }

            $result = $response->json();
            $text = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';

            $text = trim($text);
            if (str_starts_with($text, '```')) {
                $text = preg_replace('/^```(?:json)?\s*/i', '', $text);
                $text = preg_replace('/\s*```$/', '', $text);
            }

            $decoded = json_decode($text, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Gemini JSON parse error', ['text' => $text, 'error' => json_last_error_msg()]);
                return ['error' => 'Failed to parse AI response', 'raw_text' => $text];
            }

            return $this->sanitizeUtf8($decoded);
        } catch (\Exception $e) {
            Log::error('Gemini analysis exception', ['message' => $e->getMessage()]);
            return ['error' => 'AI analysis failed: ' . $e->getMessage()];
        }
    }

    public function extractTextFromImage(string $imageBase64, string $mimeType = 'image/jpeg'): string
    {
        if (empty($this->apiKey)) {
            return '';
        }

        $prompt = "Extract all text from this image. If it's a contract or legal document, preserve the structure and formatting. Return only the extracted text.";

        try {
            $response = Http::timeout(60)
                ->post("{$this->baseUrl}/models/gemini-2.0-flash:generateContent?key={$this->apiKey}", [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt],
                                ['inline_data' => ['mime_type' => $mimeType, 'data' => $imageBase64]],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.1,
                        'maxOutputTokens' => 8192,
                    ],
                ]);

            if ($response->failed()) {
                return '';
            }

            $result = $response->json();
            return $result['candidates'][0]['content']['parts'][0]['text'] ?? '';
        } catch (\Exception $e) {
            Log::error('Gemini image OCR exception', ['message' => $e->getMessage()]);
            return '';
        }
    }

    public function analyzeDocument(string $text, string $docType = 'contract'): array
    {
        if ($docType === 'identification') {
            return $this->analyzeIdentification($text);
        }
        return $this->analyzeContract($text);
    }

    public function analyzeDocumentImage(string $imageBase64, string $mimeType = 'image/jpeg', string $docType = 'contract'): array
    {
        if ($docType === 'identification') {
            $extractedText = $this->extractTextFromImage($imageBase64, $mimeType);
            if (empty($extractedText)) {
                return ['error' => 'Could not extract text from image'];
            }
            return $this->analyzeIdentification($extractedText);
        }

        $extractedText = $this->extractTextFromImage($imageBase64, $mimeType);
        if (empty($extractedText)) {
            return ['error' => 'Could not extract text from image'];
        }
        return $this->analyzeContract($extractedText);
    }

    public function analyzeDocumentPdf(string $pdfBase64, string $docType = 'contract'): array
    {
        if (empty($this->apiKey)) {
            return ['error' => 'Gemini API key not configured'];
        }

        if ($docType === 'identification') {
            $prompt = "Extract all personal information, ID numbers, dates, license details, and any text from this identification document. Return the extracted text only.";
        } else {
            $prompt = "Extract all text from this contract document. Preserve structure, headings, and formatting. Return only the extracted text.";
        }

        try {
            $response = Http::timeout(90)
                ->post("{$this->baseUrl}/models/gemini-2.0-flash:generateContent?key={$this->apiKey}", [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt],
                                ['inline_data' => ['mime_type' => 'application/pdf', 'data' => $pdfBase64]],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.1,
                        'maxOutputTokens' => 16384,
                    ],
                ]);

            if ($response->failed()) {
                Log::error('Gemini PDF API error', ['status' => $response->status(), 'body' => $response->body()]);
                return ['error' => 'AI PDF analysis failed: ' . $response->body()];
            }

            $result = $response->json();
            $extractedText = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';

            if (empty(trim($extractedText))) {
                return ['error' => 'Could not extract text from PDF'];
            }

            if ($docType === 'identification') {
                return $this->analyzeIdentification($extractedText);
            }
            return $this->analyzeContract($extractedText);
        } catch (\Exception $e) {
            Log::error('Gemini PDF analysis exception', ['message' => $e->getMessage()]);
            return ['error' => 'AI PDF analysis failed: ' . $e->getMessage()];
        }
    }

    public function analyzeIdentification(string $text): array
    {
        if (empty($this->apiKey)) {
            return ['error' => 'Gemini API key not configured'];
        }

        $prompt = <<<PROMPT
You are an identification document analysis AI. Analyze the following text extracted from a driver's identification document, license, or national ID card. Return ONLY valid JSON (no markdown, no code fences) with these fields:

{
  "document_type": "license" or "national_id" or "passport" or "other",
  "personal_info": {
    "full_name": "full name as shown",
    "id_number": "ID number or license number",
    "date_of_birth": "YYYY-MM-DD or as shown",
    "gender": "male/female or as shown",
    "nationality": "nationality",
    "address": "address if available"
  },
  "document_info": {
    "issue_date": "YYYY-MM-DD or as shown",
    "expiry_date": "YYYY-MM-DD or as shown",
    "issuing_authority": "issuing authority or institution"
  },
  "license_info": {
    "license_class": "class/category if it's a license",
    "license_number": "license number",
    "valid_until": "expiry date if shown",
    "vehicle_types": ["list of vehicle types allowed"]
  },
  "special_notes": ["any other important observations about the document"]
}

Document text:
{$text}
PROMPT;

        try {
            $response = Http::timeout(60)
                ->post("{$this->baseUrl}/models/gemini-2.0-flash:generateContent?key={$this->apiKey}", [
                    'contents' => [
                        ['parts' => [['text' => $prompt]]],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.1,
                        'maxOutputTokens' => 4096,
                    ],
                ]);

            if ($response->failed()) {
                Log::error('Gemini API error', ['status' => $response->status(), 'body' => $response->body()]);
                return ['error' => 'AI analysis failed: ' . $response->body()];
            }

            $result = $response->json();
            $text = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';

            $text = trim($text);
            if (str_starts_with($text, '```')) {
                $text = preg_replace('/^```(?:json)?\s*/i', '', $text);
                $text = preg_replace('/\s*```$/', '', $text);
            }

            $decoded = json_decode($text, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Gemini JSON parse error', ['text' => $text, 'error' => json_last_error_msg()]);
                return ['error' => 'Failed to parse AI response', 'raw_text' => $text];
            }

            return $this->sanitizeUtf8($decoded);
        } catch (\Exception $e) {
            Log::error('Gemini identification analysis exception', ['message' => $e->getMessage()]);
            return ['error' => 'AI analysis failed: ' . $e->getMessage()];
        }
    }

    private function sanitizeUtf8($data)
    {
        if (is_string($data) || is_array($data)) {
            $json = json_encode($data, JSON_INVALID_UTF8_SUBSTITUTE);
            if ($json !== false) {
                return json_decode($json, is_array($data));
            }
        }
        return $data;
    }
}
