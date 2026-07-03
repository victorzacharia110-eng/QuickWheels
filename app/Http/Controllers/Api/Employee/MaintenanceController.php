<?php

namespace App\Http\Controllers\Api\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MaintenanceController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(['success' => true, 'data' => []]);
    }

    public function store(Request $request)
    {
        return response()->json(['success' => true, 'message' => 'Maintenance record created', 'data' => null], 201);
    }

    public function update(Request $request, $id)
    {
        return response()->json(['success' => true, 'message' => 'Maintenance record updated', 'data' => null]);
    }

    public function complete(Request $request, $id)
    {
        return response()->json(['success' => true, 'message' => 'Maintenance completed', 'data' => null]);
    }
}
