<?php

namespace App\Http\Controllers\Api;

use App\Enums\LeadSource;
use App\Http\Controllers\Controller;
use App\Services\LeadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeadApiController extends Controller
{
    public function store(Request $request, LeadService $leads): JsonResponse
    {
        $data = $request->validate([
            'customer_name' => 'required|string|max:150',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'car_brand' => 'nullable|string|max:100',
            'price' => 'nullable|numeric|min:0',
            'freelancer_id' => 'nullable|uuid|exists:freelancers,freelancer_id',
        ]);

        $lead = $leads->create($data, null, LeadSource::ExternalApi);

        return response()->json([
            'lead_id' => $lead->lead_id,
            'lead_number' => $lead->lead_number,
            'status' => $lead->status->value,
        ], 201);
    }
}
