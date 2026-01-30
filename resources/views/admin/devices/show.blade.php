@extends('admin.layouts.app')

@section('title', $device->device_name ?? 'Device Details')

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.devices.index') }}" class="text-gray-400 hover:text-white flex items-center">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
        </svg>
        Back to Devices
    </a>
</div>

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-white">{{ $device->device_name ?? 'Unnamed Device' }}</h1>
        <p class="text-gray-400 font-mono text-sm mt-1">{{ $device->device_id }}</p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('admin.devices.edit', $device) }}"
           class="px-4 py-2 bg-gray-700 text-white rounded-lg hover:bg-gray-600 transition-colors">
            Edit
        </a>
        <form action="{{ route('admin.devices.regenerate-key', $device) }}" method="POST"
              onsubmit="return confirm('Regenerate API key? The device will need to be reconfigured.')">
            @csrf
            <button type="submit"
                    class="px-4 py-2 bg-[#D4AF37] text-gray-900 font-semibold rounded-lg hover:bg-[#F6D365] transition-colors">
                Regenerate API Key
            </button>
        </form>
    </div>
</div>

<!-- Stats -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
        <p class="text-sm text-gray-400 mb-1">Status</p>
        @if ($device->is_active)
            <span class="inline-flex items-center text-lg font-semibold text-green-400">
                <span class="w-2 h-2 mr-2 rounded-full bg-green-400"></span>
                Active
            </span>
        @else
            <span class="inline-flex items-center text-lg font-semibold text-red-400">
                <span class="w-2 h-2 mr-2 rounded-full bg-red-400"></span>
                Inactive
            </span>
        @endif
    </div>

    <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
        <p class="text-sm text-gray-400 mb-1">Total Transactions</p>
        <p class="text-2xl font-bold text-white">{{ number_format($device->transactions->count()) }}</p>
    </div>

    <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
        <p class="text-sm text-gray-400 mb-1">Total Earnings</p>
        <p class="text-2xl font-bold text-[#D4AF37]">₱{{ number_format($device->transactions->sum('amount'), 2) }}</p>
    </div>

    <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
        <p class="text-sm text-gray-400 mb-1">Today's Earnings</p>
        <p class="text-2xl font-bold text-green-400">₱{{ number_format($todayEarnings, 2) }}</p>
    </div>
</div>

<!-- Device Info -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <div class="bg-gray-800 rounded-xl border border-gray-700 p-6">
        <h2 class="text-lg font-semibold text-white mb-4">Device Information</h2>
        <dl class="space-y-3">
            <div class="flex justify-between">
                <dt class="text-gray-400">Device ID</dt>
                <dd class="text-white font-mono text-sm">{{ $device->device_id }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-gray-400">Created</dt>
                <dd class="text-white">{{ $device->created_at->format('M d, Y H:i') }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-gray-400">Last Seen</dt>
                <dd class="text-white">
                    {{ $device->last_seen_at ? $device->last_seen_at->format('M d, Y H:i') . ' (' . $device->last_seen_at->diffForHumans() . ')' : 'Never' }}
                </dd>
            </div>
        </dl>
    </div>

    <div class="bg-gray-800 rounded-xl border border-gray-700 p-6">
        <h2 class="text-lg font-semibold text-white mb-4">Quick Actions</h2>
        <div class="space-y-3">
            <form action="{{ route('admin.devices.update', $device) }}" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="device_name" value="{{ $device->device_name }}">
                <input type="hidden" name="is_active" value="{{ $device->is_active ? '0' : '1' }}">
                <button type="submit"
                        class="w-full px-4 py-2 {{ $device->is_active ? 'bg-red-600 hover:bg-red-700' : 'bg-green-600 hover:bg-green-700' }} text-white rounded-lg transition-colors">
                    {{ $device->is_active ? 'Deactivate Device' : 'Activate Device' }}
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Recent Transactions -->
<div class="bg-gray-800 rounded-xl border border-gray-700">
    <div class="px-6 py-4 border-b border-gray-700">
        <h2 class="text-lg font-semibold text-white">Recent Transactions</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-700/50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Transaction ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Game</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Numbers</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Amount</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Draw</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Created</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-700">
                @forelse ($device->transactions as $transaction)
                    <tr class="hover:bg-gray-700/50">
                        <td class="px-6 py-4">
                            <code class="text-xs text-[#D4AF37]">{{ $transaction->transaction_id }}</code>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 bg-[#0D5C3D]/30 text-[#1A7B52] rounded text-xs font-medium">
                                {{ $transaction->game_type }}
                            </span>
                        </td>
                        <td class="px-6 py-4 font-mono text-sm text-gray-300">
                            {{ is_array($transaction->numbers) ? implode(', ', $transaction->numbers) : $transaction->numbers }}
                        </td>
                        <td class="px-6 py-4 text-[#D4AF37] font-medium">₱{{ number_format($transaction->amount, 2) }}</td>
                        <td class="px-6 py-4 text-gray-300">
                            {{ $transaction->draw_date->format('M d') }} @ {{ $transaction->draw_time }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-400">{{ $transaction->created_at->diffForHumans() }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-500">No transactions yet</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
