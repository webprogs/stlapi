@extends('admin.layouts.app')

@section('title', 'Devices')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-white">Devices</h1>
    <a href="{{ route('admin.devices.create') }}"
       class="flex items-center px-4 py-2 bg-[#D4AF37] text-gray-900 font-semibold rounded-lg hover:bg-[#F6D365] transition-colors">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
        </svg>
        Add Device
    </a>
</div>

<div class="bg-gray-800 rounded-xl border border-gray-700">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-700/50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Device</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Transactions</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Earnings</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Last Seen</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-700">
                @forelse ($devices as $device)
                    <tr class="hover:bg-gray-700/50">
                        <td class="px-6 py-4">
                            <div>
                                <a href="{{ route('admin.devices.show', $device) }}" class="text-white hover:text-[#D4AF37] font-medium">
                                    {{ $device->device_name ?? 'Unnamed Device' }}
                                </a>
                                <p class="text-xs text-gray-500 font-mono">{{ Str::limit($device->device_id, 20) }}</p>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            @if ($device->is_active)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-500/20 text-green-400">
                                    Active
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-500/20 text-red-400">
                                    Inactive
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-gray-300">{{ number_format($device->transactions_count) }}</td>
                        <td class="px-6 py-4 text-[#D4AF37] font-medium">₱{{ number_format($device->transactions_sum_amount ?? 0, 2) }}</td>
                        <td class="px-6 py-4 text-sm text-gray-400">
                            {{ $device->last_seen_at ? $device->last_seen_at->diffForHumans() : 'Never' }}
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <a href="{{ route('admin.devices.show', $device) }}"
                                   class="p-2 text-gray-400 hover:text-white hover:bg-gray-700 rounded"
                                   title="View">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                </a>
                                <a href="{{ route('admin.devices.edit', $device) }}"
                                   class="p-2 text-gray-400 hover:text-white hover:bg-gray-700 rounded"
                                   title="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </a>
                                <form action="{{ route('admin.devices.regenerate-key', $device) }}" method="POST" class="inline"
                                      onsubmit="return confirm('Regenerate API key? The device will need to be reconfigured.')">
                                    @csrf
                                    <button type="submit"
                                            class="p-2 text-gray-400 hover:text-[#D4AF37] hover:bg-gray-700 rounded"
                                            title="Regenerate API Key">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                            No devices registered yet.
                            <a href="{{ route('admin.devices.create') }}" class="text-[#D4AF37] hover:underline">Add your first device</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
