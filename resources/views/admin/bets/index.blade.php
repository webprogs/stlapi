@extends('admin.layouts.app')

@section('title', 'All Bets')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-white">All Bets</h1>
        <p class="text-gray-400 mt-1">View betting activity by date and device</p>
    </div>
    <a href="{{ route('admin.bets.statistics') }}"
       class="px-4 py-2 bg-gray-700 text-white rounded-lg hover:bg-gray-600 transition-colors flex items-center gap-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
        </svg>
        Popular Numbers
    </a>
</div>

{{-- Winners Alert --}}
@if ($summary['total_winners'] > 0)
<div class="bg-gradient-to-r from-[#D4AF37]/20 to-yellow-500/20 rounded-xl p-6 border border-[#D4AF37]/30 mb-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <div class="p-3 bg-[#D4AF37] rounded-lg">
                <svg class="w-8 h-8 text-gray-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path>
                </svg>
            </div>
            <div>
                <h3 class="text-xl font-bold text-white">
                    {{ $summary['total_winners'] }} Winner{{ $summary['total_winners'] > 1 ? 's' : '' }} Found!
                </h3>
                <p class="text-gray-400">
                    Total Prize: <span class="text-[#D4AF37] font-semibold">₱{{ number_format($summary['total_prize'], 2) }}</span>
                </p>
            </div>
        </div>
    </div>
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Calendar & Filters -->
    <div class="space-y-6">
        <!-- Date Picker -->
        <div class="bg-gray-800 rounded-xl border border-gray-700 p-4">
            <h3 class="text-lg font-semibold text-white mb-4">Select Date</h3>
            <form action="{{ route('admin.bets.index') }}" method="GET" id="dateForm">
                <input type="hidden" name="game_type" value="{{ $selectedGameType }}">
                <input type="hidden" name="draw_time" value="{{ $selectedDrawTime }}">
                <input type="date"
                       name="date"
                       value="{{ $selectedDate }}"
                       max="{{ now()->format('Y-m-d') }}"
                       onchange="document.getElementById('dateForm').submit()"
                       class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-[#D4AF37]">
            </form>

            <!-- Calendar Legend -->
            <div class="mt-4 pt-4 border-t border-gray-700">
                <p class="text-xs text-gray-400 mb-2">Dates with bets this month:</p>
                <div class="flex flex-wrap gap-2">
                    @foreach ($calendarData as $date => $data)
                        <a href="{{ route('admin.bets.index', ['date' => $date, 'game_type' => $selectedGameType, 'draw_time' => $selectedDrawTime]) }}"
                           class="px-2 py-1 text-xs rounded {{ $date == $selectedDate ? 'bg-[#D4AF37] text-gray-900' : 'bg-gray-700 text-white hover:bg-gray-600' }}">
                            {{ \Carbon\Carbon::parse($date)->format('d') }}
                            <span class="text-[10px] opacity-75">({{ $data['bet_count'] }})</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-gray-800 rounded-xl border border-gray-700 p-4">
            <h3 class="text-lg font-semibold text-white mb-4">Filters</h3>
            <form action="{{ route('admin.bets.index') }}" method="GET" class="space-y-4">
                <input type="hidden" name="date" value="{{ $selectedDate }}">

                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Game Type</label>
                    <select name="game_type"
                            class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-[#D4AF37]">
                        <option value="">All Types</option>
                        @foreach ($gameTypes as $type)
                            <option value="{{ $type }}" {{ $selectedGameType == $type ? 'selected' : '' }}>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Draw Time</label>
                    <select name="draw_time"
                            class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-[#D4AF37]">
                        <option value="">All Times</option>
                        @foreach ($drawTimes as $time)
                            <option value="{{ $time }}" {{ $selectedDrawTime == $time ? 'selected' : '' }}>{{ $time }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex gap-2">
                    <button type="submit"
                            class="flex-1 px-4 py-2 bg-[#D4AF37] text-gray-900 font-semibold rounded-lg hover:bg-[#F6D365] transition-colors">
                        Apply
                    </button>
                    <a href="{{ route('admin.bets.index', ['date' => $selectedDate]) }}"
                       class="px-4 py-2 bg-gray-700 text-white rounded-lg hover:bg-gray-600 transition-colors">
                        Clear
                    </a>
                </div>
            </form>
        </div>

        <!-- Summary -->
        <div class="bg-gray-800 rounded-xl border border-gray-700 p-4">
            <h3 class="text-lg font-semibold text-white mb-4">Summary</h3>
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-gray-400">Total Bets</span>
                    <span class="text-xl font-bold text-white">{{ number_format($summary['total_bets']) }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-gray-400">Total Amount</span>
                    <span class="text-xl font-bold text-[#D4AF37]">₱{{ number_format($summary['total_amount'], 2) }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-gray-400">Devices</span>
                    <span class="text-xl font-bold text-white">{{ $summary['devices_count'] }}</span>
                </div>
                @if ($summary['total_winners'] > 0)
                <div class="pt-3 border-t border-gray-700">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-400">Winners</span>
                        <span class="text-xl font-bold text-green-400">{{ $summary['total_winners'] }}</span>
                    </div>
                    <div class="flex items-center justify-between mt-2">
                        <span class="text-gray-400">Prize Payout</span>
                        <span class="text-xl font-bold text-green-400">₱{{ number_format($summary['total_prize'], 2) }}</span>
                    </div>
                </div>
                @endif
            </div>
        </div>

        <!-- Draw Results for this date -->
        @if ($drawResultsForDisplay->count() > 0)
        <div class="bg-gray-800 rounded-xl border border-gray-700 p-4">
            <h3 class="text-lg font-semibold text-white mb-4">Winning Numbers</h3>
            <div class="space-y-3">
                @foreach ($drawTimes as $drawTime)
                    @foreach ($gameTypes as $gameType)
                        @php
                            $key = $drawTime . '_' . $gameType;
                            $result = $drawResultsForDisplay->get($key);
                        @endphp
                        @if ($result)
                        <div class="bg-gray-700/50 rounded-lg p-3">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm text-[#D4AF37]">{{ $drawTime }}</span>
                                <span class="px-2 py-0.5 bg-[#0D5C3D]/30 text-[#1A7B52] rounded text-xs font-medium">{{ $gameType }}</span>
                            </div>
                            <div class="flex gap-1 justify-center">
                                @foreach ($result->winning_numbers as $num)
                                    <span class="w-8 h-8 flex items-center justify-center bg-[#D4AF37] text-gray-900 text-sm font-bold rounded">
                                        {{ str_pad($num, 2, '0', STR_PAD_LEFT) }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    @endforeach
                @endforeach
            </div>
        </div>
        @endif
    </div>

    <!-- Bets by Device -->
    <div class="lg:col-span-2 space-y-4">
        <div class="bg-gray-800 rounded-xl border border-gray-700 p-4">
            <h2 class="text-lg font-semibold text-white">
                Bets for {{ \Carbon\Carbon::parse($selectedDate)->format('l, F j, Y') }}
            </h2>
        </div>

        @forelse ($betsByDevice as $deviceData)
            <div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden">
                <!-- Device Header -->
                <div class="bg-gray-700/50 px-6 py-4 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <svg class="w-6 h-6 text-[#D4AF37]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                        </svg>
                        <span class="font-semibold text-white text-lg">{{ $deviceData['device_name'] }}</span>
                        @if ($deviceData['winners_count'] > 0)
                            <span class="px-2 py-1 bg-green-500/20 text-green-400 rounded-lg text-xs font-bold flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path>
                                </svg>
                                {{ $deviceData['winners_count'] }} WINNER{{ $deviceData['winners_count'] > 1 ? 'S' : '' }}
                            </span>
                        @endif
                    </div>
                    <div class="flex items-center gap-6 text-sm">
                        <span class="text-gray-400">{{ $deviceData['total_bets'] }} bet{{ $deviceData['total_bets'] != 1 ? 's' : '' }}</span>
                        <span class="text-[#D4AF37] font-semibold">₱{{ number_format($deviceData['total_amount'], 2) }}</span>
                        @if ($deviceData['winners_prize'] > 0)
                            <span class="text-green-400 font-semibold">Prize: ₱{{ number_format($deviceData['winners_prize'], 2) }}</span>
                        @endif
                    </div>
                </div>

                <!-- Bets by Draw Time -->
                <div class="p-4 space-y-4">
                    @foreach ($deviceData['bets_by_draw_time'] as $drawTime => $bets)
                        @php
                            $drawTimeWinners = $bets->where('is_winner', true)->count();
                        @endphp
                        <div class="border border-gray-700 rounded-lg overflow-hidden">
                            <div class="bg-gray-700/30 px-4 py-2 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="text-[#D4AF37] font-medium">{{ $drawTime }}</span>
                                    @if ($drawTimeWinners > 0)
                                        <span class="px-2 py-0.5 bg-green-500/20 text-green-400 rounded text-xs font-bold">
                                            {{ $drawTimeWinners }} WINNER{{ $drawTimeWinners > 1 ? 'S' : '' }}
                                        </span>
                                    @endif
                                </div>
                                <span class="text-sm text-gray-400">
                                    {{ $bets->count() }} bets | ₱{{ number_format($bets->sum('amount'), 2) }}
                                </span>
                            </div>
                            <div class="p-4 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
                                @foreach ($bets as $bet)
                                    <div class="rounded-lg p-3 {{ $bet->is_winner ? 'bg-green-500/20 border-2 border-green-500 ring-2 ring-green-500/30' : 'bg-gray-700/50' }}">
                                        <div class="flex items-center justify-between mb-2">
                                            <code class="text-xs text-gray-500">{{ $bet->transaction_id }}</code>
                                            <div class="flex items-center gap-2">
                                                @if ($bet->is_winner)
                                                    <span class="px-2 py-0.5 bg-green-500 text-white rounded text-xs font-bold flex items-center gap-1">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path>
                                                        </svg>
                                                        WINNER
                                                    </span>
                                                @endif
                                                <span class="px-2 py-0.5 bg-[#0D5C3D]/30 text-[#1A7B52] rounded text-xs font-medium">
                                                    {{ $bet->game_type }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <div class="flex gap-1">
                                                @php
                                                    $numbers = is_array($bet->numbers) ? $bet->numbers : json_decode($bet->numbers, true);
                                                @endphp
                                                @foreach ($numbers ?? [] as $num)
                                                    <span class="w-7 h-7 flex items-center justify-center {{ $bet->is_winner ? 'bg-green-500 text-white' : 'bg-[#D4AF37] text-gray-900' }} text-sm font-bold rounded">
                                                        {{ str_pad($num, 2, '0', STR_PAD_LEFT) }}
                                                    </span>
                                                @endforeach
                                            </div>
                                            <div class="text-right">
                                                <span class="text-sm text-white font-medium">₱{{ number_format($bet->amount, 2) }}</span>
                                                @if ($bet->is_winner)
                                                    @php
                                                        $prize = match($bet->game_type) {
                                                            'SWER4' => 5000,
                                                            'SWER3' => 500,
                                                            'SWER2' => 50,
                                                            default => 0,
                                                        };
                                                    @endphp
                                                    <div class="text-xs text-green-400 font-bold">Wins ₱{{ number_format($prize, 2) }}</div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="bg-gray-800 rounded-xl border border-gray-700 p-12 text-center">
                <svg class="w-12 h-12 text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
                <p class="text-gray-400">No bets found for this date</p>
            </div>
        @endforelse
    </div>
</div>
@endsection
