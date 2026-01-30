<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DeviceManagementController extends Controller
{
    public function index()
    {
        $devices = Device::withCount('transactions')
            ->withSum('transactions', 'amount')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.devices.index', compact('devices'));
    }

    public function create()
    {
        return view('admin.devices.create');
    }

    public function store(Request $request)
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

        return redirect()->route('admin.devices.show', $device)
            ->with('success', 'Device created successfully!')
            ->with('new_api_key', $device->api_key);
    }

    public function show(Device $device)
    {
        $device->load(['transactions' => function ($query) {
            $query->orderBy('created_at', 'desc')->limit(20);
        }]);

        $todayEarnings = $device->transactions()
            ->whereDate('created_at', today())
            ->sum('amount');

        return view('admin.devices.show', compact('device', 'todayEarnings'));
    }

    public function edit(Device $device)
    {
        return view('admin.devices.edit', compact('device'));
    }

    public function update(Request $request, Device $device)
    {
        $request->validate([
            'device_name' => 'nullable|string|max:100',
            'is_active' => 'boolean',
        ]);

        $device->update([
            'device_name' => $request->device_name,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('admin.devices.show', $device)
            ->with('success', 'Device updated successfully!');
    }

    public function destroy(Device $device)
    {
        $device->update(['is_active' => false]);

        return redirect()->route('admin.devices.index')
            ->with('success', 'Device deactivated successfully!');
    }

    public function regenerateKey(Device $device)
    {
        $newApiKey = Device::generateApiKey();
        $device->update(['api_key' => $newApiKey]);

        return redirect()->route('admin.devices.show', $device)
            ->with('success', 'API key regenerated successfully!')
            ->with('new_api_key', $newApiKey);
    }
}
