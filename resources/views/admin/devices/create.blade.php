@extends('admin.layouts.app')

@section('title', 'Add Device')

@section('content')
<div class="max-w-2xl">
    <div class="mb-6">
        <a href="{{ route('admin.devices.index') }}" class="text-gray-400 hover:text-white flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
            Back to Devices
        </a>
    </div>

    <h1 class="text-2xl font-bold text-white mb-6">Add New Device</h1>

    <div class="bg-gray-800 rounded-xl border border-gray-700 p-6">
        <form action="{{ route('admin.devices.store') }}" method="POST">
            @csrf

            <div class="mb-6">
                <label for="device_name" class="block text-sm font-medium text-gray-300 mb-2">
                    Device Name (Optional)
                </label>
                <input type="text"
                       id="device_name"
                       name="device_name"
                       value="{{ old('device_name') }}"
                       class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#D4AF37] focus:border-transparent"
                       placeholder="e.g., Outlet 1 - Main Register">
                <p class="mt-1 text-sm text-gray-500">A friendly name to identify this device</p>
            </div>

            <div class="p-4 bg-blue-500/10 border border-blue-500/30 rounded-lg mb-6">
                <div class="flex">
                    <svg class="w-5 h-5 text-blue-400 mr-3 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div class="text-sm text-blue-300">
                        <p class="font-medium mb-1">Important</p>
                        <p>After creating the device, you'll receive a unique API key. Make sure to copy and save it securely - it will only be shown once!</p>
                    </div>
                </div>
            </div>

            <div class="flex gap-4">
                <a href="{{ route('admin.devices.index') }}"
                   class="px-4 py-2 bg-gray-700 text-white rounded-lg hover:bg-gray-600 transition-colors">
                    Cancel
                </a>
                <button type="submit"
                        class="px-4 py-2 bg-[#D4AF37] text-gray-900 font-semibold rounded-lg hover:bg-[#F6D365] transition-colors">
                    Create Device
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
