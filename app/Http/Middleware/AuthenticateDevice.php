<?php

namespace App\Http\Middleware;

use App\Models\Device;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateDevice
{
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-API-KEY');

        if (!$apiKey) {
            return response()->json([
                'error' => 'Missing authentication header',
                'message' => 'X-API-Key header is required',
            ], 401);
        }

        $device = Device::where('api_key', $apiKey)
            ->first();

        if (!$device) {
            return response()->json([
                'error' => 'Invalid credentials',
                'message' => 'Device not found or API key is invalid',
            ], 401);
        }

        if (!$device->is_active) {
            return response()->json([
                'error' => 'Device inactive',
                'message' => 'This device has been deactivated',
            ], 403);
        }

        // Update last sync info
        $device->update([
            'last_sync_at' => now(),
            'last_ip' => $request->ip(),
        ]);

        // Attach device to request for use in controllers
        $request->merge(['device' => $device]);
        $request->setUserResolver(fn() => $device);

        return $next($request);
    }
}
