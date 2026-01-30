@extends('admin.layouts.app')

@section('title', 'Dashboard')

@section('content')
<h1 class="text-2xl font-bold text-white mb-6">Dashboard</h1>

<!-- Stats Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Total Earnings -->
    <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
        <div class="flex items-center">
            <div class="p-3 rounded-lg bg-[#D4AF37]/20">
                <svg class="w-6 h-6 text-[#D4AF37]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-400">Total Earnings</p>
                <p class="text-2xl font-bold text-white">₱{{ number_format($totalEarnings, 2) }}</p>
            </div>
        </div>
    </div>

    <!-- Today's Earnings -->
    <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
        <div class="flex items-center">
            <div class="p-3 rounded-lg bg-green-500/20">
                <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                </svg>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-400">Today's Earnings</p>
                <p class="text-2xl font-bold text-white">₱{{ number_format($todayEarnings, 2) }}</p>
            </div>
        </div>
    </div>

    <!-- Total Transactions -->
    <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
        <div class="flex items-center">
            <div class="p-3 rounded-lg bg-blue-500/20">
                <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path>
                </svg>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-400">Total Transactions</p>
                <p class="text-2xl font-bold text-white">{{ number_format($totalTransactions) }}</p>
            </div>
        </div>
    </div>

    <!-- Active Devices -->
    <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
        <div class="flex items-center">
            <div class="p-3 rounded-lg bg-purple-500/20">
                <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                </svg>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-400">Active Devices</p>
                <p class="text-2xl font-bold text-white">{{ $onlineDevices }} / {{ $activeDevices }}</p>
            </div>
        </div>
    </div>
</div>

<!-- Device Status Table -->
<div class="bg-gray-800 rounded-xl border border-gray-700 mb-8">
    <div class="px-6 py-4 border-b border-gray-700">
        <h2 class="text-lg font-semibold text-white">Device Status</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-700/50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Device</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Transactions</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Earnings</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Last Seen</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-700">
                @forelse ($devices as $device)
                    <tr class="hover:bg-gray-700/50">
                        <td class="px-6 py-4">
                            <a href="{{ route('admin.devices.show', $device) }}" class="text-white hover:text-[#D4AF37] font-medium">
                                {{ $device->device_name ?? 'Unnamed Device' }}
                            </a>
                            <p class="text-xs text-gray-500 font-mono">{{ Str::limit($device->device_id, 12) }}</p>
                        </td>
                        <td class="px-6 py-4">
                            @if ($device->last_seen_at && $device->last_seen_at->gte(now()->subMinutes(15)))
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-500/20 text-green-400">
                                    <span class="w-1.5 h-1.5 mr-1.5 rounded-full bg-green-400"></span>
                                    Online
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-500/20 text-gray-400">
                                    <span class="w-1.5 h-1.5 mr-1.5 rounded-full bg-gray-400"></span>
                                    Offline
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-gray-300">{{ number_format($device->transactions_count) }}</td>
                        <td class="px-6 py-4 text-[#D4AF37] font-medium">₱{{ number_format($device->transactions_sum_amount ?? 0, 2) }}</td>
                        <td class="px-6 py-4 text-sm text-gray-400">
                            {{ $device->last_seen_at ? $device->last_seen_at->diffForHumans() : 'Never' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                            No devices registered yet.
                            <a href="{{ route('admin.devices.create') }}" class="text-[#D4AF37] hover:underline">Add your first device</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Recent Transactions -->
<div class="bg-gray-800 rounded-xl border border-gray-700">
    <div class="px-6 py-4 border-b border-gray-700 flex items-center justify-between">
        <h2 class="text-lg font-semibold text-white">Recent Transactions</h2>
        <a href="{{ route('admin.transactions.index') }}" class="text-sm text-[#D4AF37] hover:underline">View all</a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-700/50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Transaction ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Device</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Game</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Amount</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Time</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-700">
                @forelse ($recentTransactions as $transaction)
                    <tr class="hover:bg-gray-700/50">
                        <td class="px-6 py-4">
                            <code class="text-xs text-[#D4AF37]">{{ $transaction->transaction_id }}</code>
                        </td>
                        <td class="px-6 py-4 text-gray-300">{{ $transaction->device->device_name ?? 'Unknown' }}</td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 bg-[#0D5C3D]/30 text-[#1A7B52] rounded text-xs font-medium">
                                {{ $transaction->game_type }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-[#D4AF37] font-medium">₱{{ number_format($transaction->amount, 2) }}</td>
                        <td class="px-6 py-4 text-sm text-gray-400">{{ $transaction->created_at->diffForHumans() }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-gray-500">No transactions yet</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
