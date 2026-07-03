<?php

namespace App\Http\Controllers\Api\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(['success' => true, 'data' => []]);
    }

    public function show(Request $request, $id)
    {
        return response()->json(['success' => true, 'data' => null]);
    }
}
