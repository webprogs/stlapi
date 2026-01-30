@extends('admin.layouts.app')

@section('title', 'Edit Device')

@section('content')
<div class="max-w-2xl">
    <div class="mb-6">
        <a href="{{ route('admin.devices.show', $device) }}" class="text-gray-400 hover:text-white flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
            Back to Device
        </a>
    </div>

    <h1 class="text-2xl font-bold text-white mb-6">Edit Device</h1>

    <div class="bg-gray-800 rounded-xl border border-gray-700 p-6">
        <form action="{{ route('admin.devices.update', $device) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-6">
                <label for="device_name" class="block text-sm font-medium text-gray-300 mb-2">
                    Device Name
                </label>
                <input type="text"
                       id="device_name"
                       name="device_name"
                       value="{{ old('device_name', $device->device_name) }}"
                       class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#D4AF37] focus:border-transparent"
                       placeholder="e.g., Outlet 1 - Main Register">
            </div>

            <div class="mb-6">
                <label class="flex items-center">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox"
                           name="is_active"
                           value="1"
                           {{ $device->is_active ? 'checked' : '' }}
                           class="h-4 w-4 text-[#D4AF37] focus:ring-[#D4AF37] border-gray-600 rounded bg-gray-700">
                    <span class="ml-2 text-gray-300">Device is active</span>
                </label>
                <p class="mt-1 text-sm text-gray-500">Inactive devices cannot sync data</p>
            </div>

            <div class="p-4 bg-gray-700/50 rounded-lg mb-6">
                <p class="text-sm text-gray-400">
                    <span class="font-medium text-gray-300">Device ID:</span>
                    <span class="font-mono">{{ $device->device_id }}</span>
                </p>
            </div>

            <div class="flex gap-4">
                <a href="{{ route('admin.devices.show', $device) }}"
                   class="px-4 py-2 bg-gray-700 text-white rounded-lg hover:bg-gray-600 transition-colors">
                    Cancel
                </a>
                <button type="submit"
                        class="px-4 py-2 bg-[#D4AF37] text-gray-900 font-semibold rounded-lg hover:bg-[#F6D365] transition-colors">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
