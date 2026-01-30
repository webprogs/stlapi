<?php

namespace App\Http\Controllers;

use App\Models\Device;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DeviceController extends Controller
{
    public function index(): JsonResponse
    {
        $devices = Device::with(['transactions' => function ($query) {
            $query->selectRaw('device_id, COUNT(*) as count, SUM(amount) as total')
                ->groupBy('device_id');
        }])->get();

        $devices = $devices->map(function ($device) {
            return [
                'id' => $device->id,
                'device_id' => $device->device_id,
                'device_name' => $device->device_name,
                'is_active' => $device->is_active,
                'last_seen_at' => $device->last_seen_at,
                'created_at' => $device->created_at,
                'transaction_count' => $device->transactions->first()->count ?? 0,
                'total_earnings' => $device->transactions->first()->total ?? 0,
            ];
        });

        return response()->json([
            'success' => true,
            'devices' => $devices,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'device_name' => 'nullable|string|max:100',
        ]);

        $device = Device::create([
            'device_id' => Str::uuid()->toString(),
            'api_key' => Device::generateApiKey(),
            'device_name' => $request->device_name,
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'device' => [
                'id' => $device->id,
                'device_id' => $device->device_id,
                'device_name' => $device->device_name,
                'api_key' => $device->api_key, // Show on creation only
                'is_active' => $device->is_active,
                'created_at' => $device->created_at,
            ],
        ], 201);
    }

    public function show(Device $device): JsonResponse
    {
        $device->load(['transactions', 'drawResults', 'users']);

        return response()->json([
            'success' => true,
            'device' => [
                'id' => $device->id,
                'device_id' => $device->device_id,
                'device_name' => $device->device_name,
                'is_active' => $device->is_active,
                'last_seen_at' => $device->last_seen_at,
                'created_at' => $device->created_at,
                'statistics' => [
                    'total_transactions' => $device->transactions->count(),
                    'total_earnings' => $device->getTotalEarnings(),
                    'today_earnings' => $device->getTodayEarnings(),
                    'users_count' => $device->users->count(),
                ],
            ],
        ]);
    }

    public function update(Request $request, Device $device): JsonResponse
    {
        $request->validate([
            'device_name' => 'nullable|string|max:100',
            'is_active' => 'nullable|boolean',
        ]);

        $device->update($request->only(['device_name', 'is_active']));

        return response()->json([
            'success' => true,
            'device' => [
                'id' => $device->id,
                'device_id' => $device->device_id,
                'device_name' => $device->device_name,
                'is_active' => $device->is_active,
                'last_seen_at' => $device->last_seen_at,
            ],
        ]);
    }

    public function destroy(Device $device): JsonResponse
    {
        $device->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Device deactivated',
        ]);
    }

    public function regenerateKey(Device $device): JsonResponse
    {
        $newApiKey = Device::generateApiKey();
        $device->update(['api_key' => $newApiKey]);

        return response()->json([
            'success' => true,
            'api_key' => $newApiKey,
            'message' => 'API key regenerated. Please update the device.',
        ]);
    }
}
