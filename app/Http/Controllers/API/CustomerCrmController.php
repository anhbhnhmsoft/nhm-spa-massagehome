<?php

namespace App\Http\Controllers\API;

use App\Core\Controller\BaseController;
use App\Enums\DemandStatus;
use App\Models\CustomerCrmData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerCrmController extends BaseController
{
    /**
     * Lấy thông tin nhu cầu & CRM của khách hàng hiện tại
     */
    public function getPreferences(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return $this->sendError('Unauthorized', [], 401);
        }

        $crmData = CustomerCrmData::firstOrCreate(
            ['user_id' => $user->id],
            [
                'languages' => ['vi'],
                'demand_status' => DemandStatus::EXPLORING->value,
            ]
        );

        return $this->sendSuccess([
            'languages' => $crmData->languages ?? ['vi'],
            'province_id' => $crmData->province_id,
            'district_id' => $crmData->district_id,
            'ward_id' => $crmData->ward_id,
            'address_detail' => $crmData->address_detail,
            'preferred_services' => $crmData->preferred_services ?? [],
            'preferred_techniques' => $crmData->preferred_techniques ?? [],
            'preferred_time_slots' => $crmData->preferred_time_slots ?? [],
            'demand_status' => $crmData->demand_status?->value ?? $crmData->demand_status,
            'customer_rank' => $crmData->customer_rank?->value ?? $crmData->customer_rank,
            'total_spent' => (float)($crmData->total_spent ?? 0),
            'booking_count' => (int)($crmData->booking_count ?? 0),
            'aov' => (float)($crmData->aov ?? 0),
        ], 'Lấy thông tin CRM thành công');
    }

    /**
     * Cập nhật nhu cầu & CRM của khách hàng hiện tại
     */
    public function updatePreferences(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return $this->sendError('Unauthorized', [], 401);
        }

        $validated = $request->validate([
            'languages' => 'nullable|array',
            'province_id' => 'nullable|numeric',
            'district_id' => 'nullable|numeric',
            'ward_id' => 'nullable|numeric',
            'address_detail' => 'nullable|string|max:255',
            'preferred_services' => 'nullable|array',
            'preferred_techniques' => 'nullable|array',
            'preferred_time_slots' => 'nullable|array',
            'demand_status' => 'nullable|integer',
        ]);

        if (isset($validated['demand_status'])) {
            $validated['demand_status'] = (int)$validated['demand_status'];
        }

        $crmData = CustomerCrmData::firstOrCreate(['user_id' => $user->id]);
        $crmData->update(array_filter($validated, fn ($val) => !is_null($val)));

        return $this->sendSuccess([
            'languages' => $crmData->languages ?? ['vi'],
            'province_id' => $crmData->province_id,
            'district_id' => $crmData->district_id,
            'ward_id' => $crmData->ward_id,
            'address_detail' => $crmData->address_detail,
            'preferred_services' => $crmData->preferred_services ?? [],
            'preferred_techniques' => $crmData->preferred_techniques ?? [],
            'preferred_time_slots' => $crmData->preferred_time_slots ?? [],
            'demand_status' => $crmData->demand_status?->value ?? $crmData->demand_status,
            'customer_rank' => $crmData->customer_rank?->value ?? $crmData->customer_rank,
        ], 'Cập nhật nhu cầu thành công');
    }
}
