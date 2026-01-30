@extends('admin.layouts.app')

@section('title', 'Popular Numbers')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-white">Popular Numbers</h1>
        <p class="text-gray-400 mt-1">Most bet number combinations statistics</p>
    </div>
    <a href="{{ route('admin.bets.index') }}"
       class="px-4 py-2 bg-gray-700 text-white rounded-lg hover:bg-gray-600 transition-colors flex items-center gap-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
        </svg>
        Back to Bets
    </a>
</div>

<!-- Period & Filters -->
<div class="bg-gray-800 rounded-xl border border-gray-700 p-4 mb-6">
    <form action="{{ route('admin.bets.statistics') }}" method="GET" class="flex flex-wrap items-center gap-4">
        <div class="flex items-center gap-2">
            <span class="text-gray-400">Period:</span>
            @foreach (['day' => 'Today', 'week' => 'This Week', 'month' => 'This Month', 'year' => 'This Year', 'all' => 'All Time'] as $value => $label)
                <button type="submit" name="period" value="{{ $value }}"
                        class="px-4 py-2 rounded-lg font-medium transition-colors {{ $period == $value ? 'bg-[#D4AF37] text-gray-900' : 'bg-gray-700 text-white hover:bg-gray-600' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>
        <div class="flex items-center gap-2 ml-auto">
            <label class="text-gray-400">Game Type:</label>
            <select name="game_type" onchange="this.form.submit()"
                    class="px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-[#D4AF37]">
                <option value="">All Types</option>
                @foreach ($gameTypesList as $type)
                    <option value="{{ $type }}" {{ $selectedGameType == $type ? 'selected' : '' }}>{{ $type }}</option>
                @endforeach
            </select>
            <input type="hidden" name="period" value="{{ $period }}">
        </div>
    </form>
</div>

<!-- Summary Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <div class="bg-gray-800 rounded-xl border border-gray-700 p-6">
        <div class="text-sm text-gray-400 mb-1">Total Bets</div>
        <div class="text-3xl font-bold text-white">{{ number_format($summary['total_bets']) }}</div>
    </div>
    <div class="bg-gray-800 rounded-xl border border-gray-700 p-6">
        <div class="text-sm text-gray-400 mb-1">Unique Combinations</div>
        <div class="text-3xl font-bold text-[#D4AF37]">{{ number_format($summary['unique_combinations']) }}</div>
    </div>
    <div class="bg-gray-800 rounded-xl border border-gray-700 p-6">
        <div class="text-sm text-gray-400 mb-1">Total Amount</div>
        <div class="text-3xl font-bold text-white">₱{{ number_format($summary['total_amount'], 2) }}</div>
    </div>
</div>

<!-- Top Combinations Table -->
<div class="bg-gray-800 rounded-xl border border-gray-700 mb-6">
    <div class="px-6 py-4 border-b border-gray-700">
        <h2 class="text-lg font-semibold text-white">Top Number Combinations</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-700/50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Rank</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Numbers</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Game Type</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-400 uppercase tracking-wider">Times Bet</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-400 uppercase tracking-wider">Total Amount</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-400 uppercase tracking-wider">Devices</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-700">
                @forelse ($numberStats as $index => $combo)
                    <tr class="hover:bg-gray-700/50">
                        <td class="px-6 py-4">
                            <span class="w-7 h-7 flex items-center justify-center rounded-full text-xs font-bold
                                {{ $index == 0 ? 'bg-[#D4AF37] text-gray-900' : ($index == 1 ? 'bg-gray-400 text-gray-900' : ($index == 2 ? 'bg-amber-700 text-white' : 'bg-gray-700 text-gray-400')) }}">
                                {{ $index + 1 }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex gap-1">
                                @foreach ($combo['numbers'] ?? [] as $num)
                                    <span class="w-8 h-8 flex items-center justify-center bg-[#D4AF37] text-gray-900 text-sm font-bold rounded">
                                        {{ str_pad($num, 2, '0', STR_PAD_LEFT) }}
                                    </span>
                                @endforeach
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 bg-[#0D5C3D]/30 text-[#1A7B52] rounded text-xs font-medium">
                                {{ $combo['game_type'] }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right text-white font-medium">{{ number_format($combo['count']) }}</td>
                        <td class="px-6 py-4 text-right text-[#D4AF37]">₱{{ number_format($combo['total_amount'], 2) }}</td>
                        <td class="px-6 py-4 text-right text-gray-400">{{ $combo['devices_count'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-500">No data available</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Digit Statistics by Position -->
<div class="bg-gray-800 rounded-xl border border-gray-700 mb-6">
    <div class="px-6 py-4 border-b border-gray-700">
        <h2 class="text-lg font-semibold text-white">Most Common Digits by Position</h2>
    </div>
    <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-6">
        @forelse ($digitStats as $gameType => $positions)
            <div class="border border-gray-700 rounded-lg p-4">
                <h3 class="text-[#D4AF37] font-semibold mb-4">{{ $gameType }}</h3>
                <div class="space-y-3">
                    @foreach ($positions as $pos)
                        <div class="flex items-start gap-3">
                            <span class="text-sm text-gray-400 w-20 flex-shrink-0">Position {{ $pos['position'] }}:</span>
                            <div class="flex gap-1 flex-wrap">
                                @php $count = 0; @endphp
                                @foreach ($pos['frequencies'] as $digit => $freq)
                                    @if ($count < 5)
                                        <span class="px-2 py-1 rounded text-xs font-medium {{ $count == 0 ? 'bg-[#D4AF37] text-gray-900' : 'bg-gray-700 text-white' }}">
                                            {{ $digit }} ({{ $freq }})
                                        </span>
                                    @endif
                                    @php $count++; @endphp
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="col-span-3 text-center py-8 text-gray-500">No digit statistics available</div>
        @endforelse
    </div>
</div>

<!-- Device Statistics -->
<div class="bg-gray-800 rounded-xl border border-gray-700">
    <div class="px-6 py-4 border-b border-gray-700">
        <h2 class="text-lg font-semibold text-white">Top Combinations by Device</h2>
    </div>
    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
        @forelse ($deviceStats as $device)
            <div class="border border-gray-700 rounded-lg p-4">
                <div class="flex items-center gap-3 mb-4">
                    <svg class="w-5 h-5 text-[#D4AF37]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                    </svg>
                    <span class="font-semibold text-white">{{ $device['device_name'] }}</span>
                    <span class="text-sm text-gray-400 ml-auto">{{ number_format($device['total_bets']) }} bets</span>
                </div>
                <div class="space-y-2">
                    @foreach ($device['top_combinations'] as $combo)
                        <div class="flex items-center justify-between bg-gray-700/50 rounded p-2">
                            <div class="flex gap-1">
                                @foreach ($combo['numbers'] ?? [] as $num)
                                    <span class="w-6 h-6 flex items-center justify-center bg-[#D4AF37] text-gray-900 text-xs font-bold rounded">
                                        {{ str_pad($num, 2, '0', STR_PAD_LEFT) }}
                                    </span>
                                @endforeach
                            </div>
                            <span class="text-sm text-gray-400">{{ $combo['count'] }}x</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="col-span-2 text-center py-8 text-gray-500">No device data available</div>
        @endforelse
    </div>
</div>
@endsection
