<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(['success' => true, 'data' => []]);
    }

    public function show(Request $request, $id)
    {
        return response()->json(['success' => true, 'data' => null]);
    }

    public function process(Request $request)
    {
        return response()->json(['success' => true, 'message' => 'Payment processed', 'data' => null]);
    }
}
