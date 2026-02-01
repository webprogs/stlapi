<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PingController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $device = $request->get('device');

        return response()->json([
            'status' => 'ok',
            'server_time' => now()->toIso8601String(),
            'device' => [
                'uuid' => $device->uuid,
                'name' => $device->device_name,
                'last_sync_at' => $device->last_sync_at?->toIso8601String(),
            ],
        ]);
    }
}
