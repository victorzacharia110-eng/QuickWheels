<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Owner;
use Illuminate\Http\Request;

class PublicController extends Controller
{
    public function businesses()
    {
        $businesses = Owner::where('is_verified', true)
            ->select('id', 'business_name', 'business_description', 'business_address', 'total_vehicles', 'slug', 'rating', 'reviews_count')
            ->orderBy('business_name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $businesses,
        ]);
    }

    public function business($slug)
    {
        $business = Owner::where('slug', $slug)
            ->select('id', 'business_name', 'business_description', 'business_address', 'business_phone', 'business_email', 'business_website', 'total_vehicles', 'rating', 'reviews_count', 'slug')
            ->first();

        if (!$business) {
            return response()->json(['success' => false, 'message' => 'Business not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $business,
        ]);
    }

    public function businessVehicles($slug)
    {
        $business = Owner::where('slug', $slug)->first();

        if (!$business) {
            return response()->json(['success' => false, 'message' => 'Business not found'], 404);
        }

        $vehicles = $business->vehicles()
            ->where('is_active', true)
            ->select('id', 'name', 'type', 'registration', 'year', 'price', 'image', 'status', 'color', 'make', 'model', 'fuel_type', 'seats')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $vehicles,
        ]);
    }
}
