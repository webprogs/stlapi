<?php

namespace App\Http\Controllers;

use App\Models\Device;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Device::query();

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('search')) {
            $query->where('device_name', 'like', '%' . $request->search . '%');
        }

        $devices = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json($devices);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'device_name' => 'required|string|max:255',
        ]);

        $device = Device::create([
            'device_name' => $request->device_name,
        ]);

        return response()->json([
            'message' => 'Device created successfully',
            'device' => [
                'id' => $device->id,
                'uuid' => $device->uuid,
                'device_name' => $device->device_name,
                'api_key' => $device->api_key,
                'is_active' => $device->is_active,
                'created_at' => $device->created_at,
            ],
        ], 201);
    }

    public function show(Device $device): JsonResponse
    {
        return response()->json([
            'id' => $device->id,
            'uuid' => $device->uuid,
            'device_name' => $device->device_name,
            'api_key' => $device->api_key,
            'is_active' => $device->is_active,
            'last_sync_at' => $device->last_sync_at,
            'last_ip' => $device->last_ip,
            'created_at' => $device->created_at,
            'updated_at' => $device->updated_at,
            'transactions_count' => $device->transactions()->count(),
            'device_users_count' => $device->deviceUsers()->count(),
        ]);
    }

    public function update(Request $request, Device $device): JsonResponse
    {
        $request->validate([
            'device_name' => 'sometimes|string|max:255',
            'is_active' => 'sometimes|boolean',
        ]);

        $device->update($request->only(['device_name', 'is_active']));

        return response()->json([
            'message' => 'Device updated successfully',
            'device' => $device->fresh(),
        ]);
    }

    public function destroy(Device $device): JsonResponse
    {
        $device->delete();

        return response()->json([
            'message' => 'Device deleted successfully',
        ]);
    }

    public function regenerateKey(Device $device): JsonResponse
    {
        $newKey = $device->regenerateApiKey();

        return response()->json([
            'message' => 'API key regenerated successfully',
            'api_key' => $newKey,
        ]);
    }
}
