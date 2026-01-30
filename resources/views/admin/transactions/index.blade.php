@extends('admin.layouts.app')

@section('title', 'Transactions')

@section('content')
<h1 class="text-2xl font-bold text-white mb-6">Transactions</h1>

<!-- Filters -->
<div class="bg-gray-800 rounded-xl border border-gray-700 p-4 mb-6">
    <form action="{{ route('admin.transactions.index') }}" method="GET" class="flex flex-wrap gap-4">
        <div class="flex-1 min-w-[200px]">
            <label class="block text-sm font-medium text-gray-400 mb-1">Device</label>
            <select name="device_id"
                    class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-[#D4AF37]">
                <option value="">All Devices</option>
                @foreach ($devices as $device)
                    <option value="{{ $device->id }}" {{ request('device_id') == $device->id ? 'selected' : '' }}>
                        {{ $device->device_name ?? 'Device ' . Str::limit($device->device_id, 8) }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="flex-1 min-w-[200px]">
            <label class="block text-sm font-medium text-gray-400 mb-1">Date</label>
            <input type="date"
                   name="date"
                   value="{{ request('date') }}"
                   class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-[#D4AF37]">
        </div>

        <div class="flex-1 min-w-[200px]">
            <label class="block text-sm font-medium text-gray-400 mb-1">Game Type</label>
            <select name="game_type"
                    class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-[#D4AF37]">
                <option value="">All Types</option>
                @foreach ($gameTypes as $type)
                    <option value="{{ $type }}" {{ request('game_type') == $type ? 'selected' : '' }}>
                        {{ $type }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="flex items-end gap-2">
            <button type="submit"
                    class="px-4 py-2 bg-[#D4AF37] text-gray-900 font-semibold rounded-lg hover:bg-[#F6D365] transition-colors">
                Filter
            </button>
            <a href="{{ route('admin.transactions.index') }}"
               class="px-4 py-2 bg-gray-700 text-white rounded-lg hover:bg-gray-600 transition-colors">
                Clear
            </a>
        </div>
    </form>
</div>

<!-- Table -->
<div class="bg-gray-800 rounded-xl border border-gray-700">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-700/50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Transaction ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Device</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Game</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Numbers</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Amount</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Draw Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Draw Time</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Created</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-700">
                @forelse ($transactions as $transaction)
                    <tr class="hover:bg-gray-700/50">
                        <td class="px-6 py-4">
                            <code class="text-xs text-[#D4AF37]">{{ $transaction->transaction_id }}</code>
                        </td>
                        <td class="px-6 py-4 text-gray-300">
                            {{ $transaction->device->device_name ?? 'Unknown' }}
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
                        <td class="px-6 py-4 text-gray-300">{{ $transaction->draw_date->format('M d, Y') }}</td>
                        <td class="px-6 py-4 text-gray-300">{{ $transaction->draw_time }}</td>
                        <td class="px-6 py-4 text-sm text-gray-400">{{ $transaction->created_at->format('M d, Y H:i') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center text-gray-500">No transactions found</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if ($transactions->hasPages())
        <div class="px-6 py-4 border-t border-gray-700">
            <div class="flex items-center justify-between">
                <p class="text-sm text-gray-400">
                    Showing {{ $transactions->firstItem() }} to {{ $transactions->lastItem() }} of {{ $transactions->total() }} results
                </p>
                <div class="flex gap-2">
                    @if ($transactions->onFirstPage())
                        <span class="px-3 py-1 bg-gray-700/50 text-gray-500 rounded cursor-not-allowed">Previous</span>
                    @else
                        <a href="{{ $transactions->previousPageUrl() }}"
                           class="px-3 py-1 bg-gray-700 text-white rounded hover:bg-gray-600">Previous</a>
                    @endif

                    @if ($transactions->hasMorePages())
                        <a href="{{ $transactions->nextPageUrl() }}"
                           class="px-3 py-1 bg-gray-700 text-white rounded hover:bg-gray-600">Next</a>
                    @else
                        <span class="px-3 py-1 bg-gray-700/50 text-gray-500 rounded cursor-not-allowed">Next</span>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
