<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Controller;
use App\Models\Maintenance;
use App\Models\Employee;
use Illuminate\Http\Request;

class MaintenanceController extends Controller
{
    public function index(Request $request)
    {
        $ownerId = $request->user()->owner->id;

        $query = Maintenance::with(['vehicle', 'employee', 'items'])
            ->where('owner_id', $ownerId)
            ->latest();

        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        if ($request->has('search') && $request->search !== '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('report_number', 'like', "%{$search}%")
                  ->orWhereHas('vehicle', function ($vq) use ($search) {
                      $vq->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('employee', function ($eq) use ($search) {
                      $eq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $reports = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $reports->map(fn($m) => $m->toApiResponse()),
            'meta' => [
                'current_page' => $reports->currentPage(),
                'last_page' => $reports->lastPage(),
                'total' => $reports->total(),
                'per_page' => $reports->perPage(),
            ],
        ]);
    }

    public function show(Request $request, $id)
    {
        $ownerId = $request->user()->owner->id;

        $report = Maintenance::with(['vehicle', 'employee', 'items'])
            ->where('owner_id', $ownerId)
            ->find($id);

        if (!$report) {
            return response()->json(['success' => false, 'message' => 'Report not found'], 404);
        }

        $report->markViewed();
        $report->refresh();
        $report->processWorkflow();

        return response()->json([
            'success' => true,
            'data' => $report->toApiResponse(),
        ]);
    }

    public function confirm(Request $request, $id)
    {
        $ownerId = $request->user()->owner->id;

        $report = Maintenance::with(['vehicle', 'employee', 'items'])
            ->where('owner_id', $ownerId)
            ->find($id);

        if (!$report) {
            return response()->json(['success' => false, 'message' => 'Report not found'], 404);
        }

        $report->confirm();
        $report->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Service confirmed',
            'data' => $report->toApiResponse(),
        ]);
    }

    public function verify(Request $request, $id)
    {
        $ownerId = $request->user()->owner->id;

        $report = Maintenance::with(['vehicle', 'employee', 'items'])
            ->where('owner_id', $ownerId)
            ->find($id);

        if (!$report) {
            return response()->json(['success' => false, 'message' => 'Report not found'], 404);
        }

        $ownerSignature = $request->input('owner_signature');
        $report->verify($ownerSignature);
        $report->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Service verified and signed',
            'data' => $report->toApiResponse(),
        ]);
    }

    public function stats(Request $request)
    {
        $ownerId = $request->user()->owner->id;
        $all = Maintenance::where('owner_id', $ownerId);

        return response()->json([
            'success' => true,
            'data' => [
                'total' => (clone $all)->count(),
                'submitted' => (clone $all)->where('status', 'submitted')->count(),
                'viewed' => (clone $all)->where('status', 'viewed')->count(),
                'processing' => (clone $all)->where('status', 'processing')->count(),
                'confirmed' => (clone $all)->where('status', 'confirmed')->count(),
                'verified' => (clone $all)->where('status', 'verified')->count(),
                'completed' => (clone $all)->where('status', 'completed')->count(),
            ],
        ]);
    }
}
