<?php

namespace App\Http\Controllers\Api\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(['success' => true, 'data' => []]);
    }

    public function show(Request $request, $id)
    {
        return response()->json(['success' => true, 'data' => null]);
    }

    public function updateStatus(Request $request, $id)
    {
        return response()->json(['success' => true, 'message' => 'Status updated']);
    }

    public function confirm(Request $request, $id)
    {
        return response()->json(['success' => true, 'message' => 'Booking confirmed']);
    }
}
