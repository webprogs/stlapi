<?php

namespace App\Http\Middleware;

use App\Models\Device;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-API-Key');

        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'error' => 'API key is required',
            ], 401);
        }

        $device = Device::where('api_key', $apiKey)
            ->where('is_active', true)
            ->first();

        if (!$device) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid or inactive API key',
            ], 401);
        }

        // Update last seen timestamp
        $device->updateLastSeen();

        // Attach device to request for use in controllers
        $request->merge(['device' => $device]);
        $request->setUserResolver(fn () => $device);

        return $next($request);
    }
}
