<?php

namespace App\Http\Controllers\Api\Contracts;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ContractTemplateController extends Controller
{
    protected $templates = [
        'hire_purchase' => [
            'type' => 'hire_purchase',
            'title' => 'Hire Purchase Agreement',
            'fields' => [
                'contract_number', 'driver_name', 'driver_phone', 'driver_email',
                'vehicle_name', 'vehicle_registration', 'start_date', 'end_date',
                'total_amount', 'deposit', 'weekly_amount', 'payment_frequency',
            ],
            'terms' => 'The Driver agrees to pay the weekly amount until the total amount is fully paid. Upon full payment, ownership of the vehicle transfers to the Driver.',
        ],
        'rental' => [
            'type' => 'rental',
            'title' => 'Rental Agreement',
            'fields' => [
                'contract_number', 'driver_name', 'driver_phone', 'driver_email',
                'vehicle_name', 'vehicle_registration', 'start_date', 'end_date',
                'daily_amount', 'deposit', 'payment_frequency',
            ],
            'terms' => 'The Driver agrees to rent the vehicle for the specified period at the agreed rate. The vehicle remains the property of the Owner.',
        ],
    ];

    public function getTemplates()
    {
        return response()->json([
            'success' => true,
            'data' => array_values($this->templates),
        ]);
    }

    public function getTemplate($type)
    {
        if (!isset($this->templates[$type])) {
            return response()->json([
                'success' => false,
                'message' => "Template '{$type}' not found",
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->templates[$type],
        ]);
    }

    public function preview(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:hire_purchase,rental',
            'data' => 'required|array',
        ]);

        $template = $this->templates[$validated['type']];
        $data = $validated['data'];

        return response()->json([
            'success' => true,
            'data' => [
                'type' => $template['type'],
                'title' => $template['title'],
                'content' => $data,
                'terms' => $template['terms'],
                'preview' => true,
            ],
        ]);
    }
}
