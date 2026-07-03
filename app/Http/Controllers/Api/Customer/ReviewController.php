<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(['success' => true, 'data' => []]);
    }

    public function store(Request $request, $vehicleId)
    {
        return response()->json(['success' => true, 'message' => 'Review submitted', 'data' => null], 201);
    }
}
