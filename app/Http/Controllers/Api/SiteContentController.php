<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SiteContent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SiteContentController extends Controller
{
    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => SiteContent::getAllGrouped(),
        ]);
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'contents' => 'required|array',
            'contents.*.section' => 'required|string',
            'contents.*.key' => 'required|string',
            'contents.*.value' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        foreach ($request->contents as $item) {
            SiteContent::updateOrCreate(
                ['section' => $item['section'], 'key' => $item['key']],
                ['value' => $item['value'] ?? '', 'type' => $item['type'] ?? 'text']
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Site content updated successfully',
            'data' => SiteContent::getAllGrouped(),
        ]);
    }
}
