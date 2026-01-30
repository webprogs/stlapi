@extends('admin.layouts.app')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white">Draw Results</h1>
            <p class="text-gray-400 mt-1">Set winning numbers and view winners</p>
        </div>
        @if($winners['total_winners'] > 0)
        <div class="flex items-center gap-2 px-4 py-2 bg-[#D4AF37] text-gray-900 rounded-lg">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
            </svg>
            <span class="font-semibold">{{ $winners['total_winners'] }} Winner{{ $winners['total_winners'] > 1 ? 's' : '' }}</span>
        </div>
        @endif
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Calendar --}}
        <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-white">
                    {{ $selectedDate->format('F Y') }}
                </h2>
                <div class="flex gap-2">
                    <a href="{{ route('admin.draw-results.index', ['date' => $selectedDate->copy()->subMonth()->format('Y-m-d')]) }}"
                       class="p-2 hover:bg-gray-700 rounded-lg transition-colors">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </a>
                    @if(!$selectedDate->copy()->addMonth()->startOfMonth()->isFuture())
                    <a href="{{ route('admin.draw-results.index', ['date' => $selectedDate->copy()->addMonth()->format('Y-m-d')]) }}"
                       class="p-2 hover:bg-gray-700 rounded-lg transition-colors">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                    @else
                    <span class="p-2 opacity-30">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </span>
                    @endif
                </div>
            </div>

            {{-- Day headers --}}
            <div class="grid grid-cols-7 gap-1 mb-2">
                @foreach(['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $day)
                <div class="text-center text-xs text-gray-500 py-2">{{ $day }}</div>
                @endforeach
            </div>

            {{-- Calendar grid --}}
            @php
                $startOfMonth = $selectedDate->copy()->startOfMonth();
                $endOfMonth = $selectedDate->copy()->endOfMonth();
                $startDay = $startOfMonth->dayOfWeek;
                $daysInMonth = $endOfMonth->day;
                $today = now()->format('Y-m-d');
            @endphp

            <div class="grid grid-cols-7 gap-1">
                {{-- Previous month days --}}
                @for($i = $startDay - 1; $i >= 0; $i--)
                    <div class="p-2 text-sm text-gray-600 text-center">
                        {{ $startOfMonth->copy()->subDays($i + 1)->day }}
                    </div>
                @endfor

                {{-- Current month days --}}
                @for($day = 1; $day <= $daysInMonth; $day++)
                    @php
                        $date = $selectedDate->copy()->setDay($day)->format('Y-m-d');
                        $isFuture = \Carbon\Carbon::parse($date)->isFuture();
                        $isSelected = $date === $selectedDate->format('Y-m-d');
                        $isToday = $date === $today;
                        $resultCount = $calendarResults[$date] ?? 0;
                        $isComplete = $resultCount >= 9;
                    @endphp
                    @if($isFuture)
                        <div class="relative p-2 text-sm text-gray-600 text-center rounded-lg cursor-not-allowed">
                            {{ $day }}
                        </div>
                    @else
                        <a href="{{ route('admin.draw-results.index', ['date' => $date]) }}"
                           class="relative p-2 text-sm text-center rounded-lg transition-all
                                  {{ $isSelected ? 'bg-[#D4AF37] text-gray-900 font-bold' : 'text-white hover:bg-gray-700' }}
                                  {{ $isToday && !$isSelected ? 'ring-2 ring-[#D4AF37]' : '' }}">
                            {{ $day }}
                            @if($resultCount > 0)
                            <div class="absolute bottom-0.5 left-1/2 transform -translate-x-1/2 flex gap-0.5">
                                <div class="w-1.5 h-1.5 rounded-full {{ $isComplete ? 'bg-green-500' : 'bg-yellow-500' }}"></div>
                            </div>
                            @endif
                        </a>
                    @endif
                @endfor

                {{-- Next month days --}}
                @php $remaining = 42 - ($startDay + $daysInMonth); @endphp
                @for($i = 1; $i <= $remaining; $i++)
                    <div class="p-2 text-sm text-gray-600 text-center">{{ $i }}</div>
                @endfor
            </div>

            {{-- Legend --}}
            <div class="mt-4 flex flex-wrap gap-4 text-xs text-gray-400">
                <div class="flex items-center gap-1">
                    <div class="w-2 h-2 rounded-full bg-green-500"></div>
                    <span>Complete</span>
                </div>
                <div class="flex items-center gap-1">
                    <div class="w-2 h-2 rounded-full bg-yellow-500"></div>
                    <span>Partial</span>
                </div>
            </div>
        </div>

        {{-- Results Entry --}}
        <div class="lg:col-span-2 bg-gray-800 rounded-xl p-6 border border-gray-700">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-lg font-semibold text-white">
                    Results for {{ $selectedDate->format('l, F j, Y') }}
                </h2>
                @if($selectedDate->isFuture())
                <span class="px-3 py-1 bg-red-500/20 text-red-400 rounded-lg text-sm">
                    Future Date
                </span>
                @elseif($selectedDate->isToday())
                <span class="px-3 py-1 bg-green-500/20 text-green-400 rounded-lg text-sm">
                    Today
                </span>
                @endif
            </div>

            @if($selectedDate->isFuture())
                <div class="flex flex-col items-center justify-center h-64 text-gray-500">
                    <svg class="w-12 h-12 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <p>Cannot enter results for future dates</p>
                </div>
            @else
                <div class="space-y-6">
                    @foreach($drawTimes as $drawTime)
                    <div class="border border-gray-700 rounded-lg p-4">
                        <h3 class="text-[#D4AF37] font-semibold mb-4">{{ $drawTime }}</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            @foreach($gameTypes as $gameType)
                                @php
                                    $key = $drawTime . '_' . $gameType;
                                    $result = $results->get($key);
                                    $numberCount = \App\Http\Controllers\Admin\DrawResultController::getNumberCount($gameType);
                                @endphp
                                <div class="p-4 rounded-lg border-2 transition-all
                                            {{ $result ? 'border-green-500 bg-green-500/10' : 'border-gray-600 bg-gray-700/50' }}">
                                    <div class="flex items-center justify-between mb-3">
                                        <span class="text-white font-medium">{{ $gameType }}</span>
                                        <span class="text-xs text-gray-400">Prize: ₱{{ number_format($prizes[$gameType]) }}</span>
                                    </div>

                                    @if($result)
                                        {{-- Show existing result --}}
                                        <div class="flex items-center justify-between mb-3">
                                            <div class="flex gap-2">
                                                @foreach($result->winning_numbers as $num)
                                                <div class="w-10 h-10 flex items-center justify-center bg-[#D4AF37] text-gray-900 font-bold rounded-lg text-lg">
                                                    {{ str_pad($num, 2, '0', STR_PAD_LEFT) }}
                                                </div>
                                                @endforeach
                                            </div>
                                            <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                        </div>
                                        <div class="flex gap-2">
                                            <button type="button"
                                                    onclick="openEditModal('{{ $drawTime }}', '{{ $gameType }}', {{ json_encode($result->winning_numbers) }}, {{ $result->id }})"
                                                    class="flex-1 px-3 py-2 text-sm bg-gray-600 text-white rounded-lg hover:bg-gray-500 transition-colors">
                                                Edit
                                            </button>
                                            <form action="{{ route('admin.draw-results.destroy', $result->id) }}" method="POST" class="inline"
                                                  onsubmit="return confirm('Are you sure you want to delete this result?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="px-3 py-2 text-sm bg-red-600/20 text-red-400 rounded-lg hover:bg-red-600/30 transition-colors">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    @else
                                        {{-- Empty slot - click to add --}}
                                        <button type="button"
                                                onclick="openModal('{{ $drawTime }}', '{{ $gameType }}', {{ $numberCount }})"
                                                class="w-full">
                                            <div class="flex gap-2 mb-3">
                                                @for($i = 0; $i < $numberCount; $i++)
                                                <div class="w-10 h-10 flex items-center justify-center border-2 border-dashed border-gray-500 text-gray-500 rounded-lg">
                                                    ?
                                                </div>
                                                @endfor
                                            </div>
                                            <div class="text-sm text-gray-400 hover:text-[#D4AF37] transition-colors">
                                                Click to enter numbers
                                            </div>
                                        </button>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Winners Section --}}
    @if($winners['total_winners'] > 0)
    <div class="bg-gradient-to-r from-[#D4AF37]/20 to-yellow-500/20 rounded-xl p-6 border border-[#D4AF37]/30">
        <div class="flex items-center gap-4 mb-6">
            <div class="p-3 bg-[#D4AF37] rounded-lg">
                <svg class="w-8 h-8 text-gray-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                </svg>
            </div>
            <div>
                <h3 class="text-xl font-bold text-white">
                    {{ $winners['total_winners'] }} Winner{{ $winners['total_winners'] > 1 ? 's' : '' }} Today!
                </h3>
                <p class="text-gray-400">
                    Total Prize: ₱{{ number_format($winners['total_prize'], 2) }} across {{ $winners['by_device']->count() }} device{{ $winners['by_device']->count() > 1 ? 's' : '' }}
                </p>
            </div>
        </div>

        {{-- Winners by Device --}}
        <div class="space-y-4">
            @foreach($winners['by_device'] as $deviceWinners)
            <div class="bg-gray-800/50 rounded-lg border border-gray-700 overflow-hidden">
                <div class="bg-gray-700/50 px-4 py-3 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-[#D4AF37]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                        <span class="font-semibold text-white">{{ $deviceWinners['device_name'] }}</span>
                    </div>
                    <div class="flex items-center gap-4 text-sm">
                        <span class="text-gray-400">{{ $deviceWinners['total_winners'] }} winner{{ $deviceWinners['total_winners'] > 1 ? 's' : '' }}</span>
                        <span class="text-[#D4AF37] font-semibold">₱{{ number_format($deviceWinners['total_prize'], 2) }}</span>
                    </div>
                </div>

                <div class="divide-y divide-gray-700">
                    @foreach($deviceWinners['winners'] as $winner)
                    <div class="p-4 flex items-center justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <span class="px-2 py-1 bg-[#D4AF37]/20 text-[#D4AF37] text-xs rounded">{{ $winner['draw_time'] }}</span>
                                <span class="px-2 py-1 bg-gray-700 text-white text-xs rounded">{{ $winner['game_type'] }}</span>
                                <span class="text-gray-500 text-xs font-mono">{{ $winner['transaction_id'] }}</span>
                            </div>
                            <div class="flex items-center gap-4">
                                <div class="flex items-center gap-1">
                                    <span class="text-gray-400 text-sm">Numbers:</span>
                                    <div class="flex gap-1">
                                        @foreach($winner['bet_numbers'] as $num)
                                        <span class="w-6 h-6 flex items-center justify-center bg-[#D4AF37] text-gray-900 text-xs font-bold rounded">{{ str_pad($num, 2, '0', STR_PAD_LEFT) }}</span>
                                        @endforeach
                                    </div>
                                </div>
                                <span class="text-gray-400 text-sm">Bet: ₱{{ number_format($winner['bet_amount'], 2) }}</span>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="flex items-center gap-1 text-[#D4AF37] font-bold">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                ₱{{ number_format($winner['prize_won'], 2) }}
                            </div>
                            <span class="text-xs text-gray-500">x{{ $winner['prize_multiplier'] }} multiplier</span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>

{{-- Number Input Modal --}}
<div id="numberModal" class="fixed inset-0 bg-black/70 flex items-center justify-center z-50 hidden">
    <div class="bg-gray-800 rounded-xl p-6 w-full max-w-md border border-gray-700 mx-4">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-bold text-white">Enter Winning Numbers</h3>
            <button type="button" onclick="closeModal()" class="p-2 hover:bg-gray-700 rounded-lg transition-colors">
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <form id="resultForm" action="{{ route('admin.draw-results.store') }}" method="POST">
            @csrf
            <input type="hidden" name="draw_date" value="{{ $selectedDate->format('Y-m-d') }}">
            <input type="hidden" name="draw_time" id="modalDrawTime">
            <input type="hidden" name="game_type" id="modalGameType">

            <div class="mb-6">
                <div class="flex items-center gap-2 text-gray-400 mb-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span>{{ $selectedDate->format('F j, Y') }}</span>
                </div>
                <div class="flex items-center gap-4">
                    <span id="modalDrawTimeDisplay" class="px-3 py-1 bg-[#D4AF37]/20 text-[#D4AF37] rounded-lg text-sm"></span>
                    <span id="modalGameTypeDisplay" class="px-3 py-1 bg-gray-700 text-white rounded-lg text-sm"></span>
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-sm text-gray-400 mb-3">Enter digits (0-9)</label>
                <div id="numberInputs" class="flex gap-3 justify-center">
                    {{-- Inputs will be dynamically generated --}}
                </div>
            </div>

            <div class="flex gap-3">
                <button type="button" onclick="closeModal()" class="flex-1 px-4 py-3 bg-gray-700 text-white rounded-lg hover:bg-gray-600 transition-colors">
                    Cancel
                </button>
                <button type="submit" class="flex-1 px-4 py-3 bg-[#D4AF37] text-gray-900 font-semibold rounded-lg hover:bg-[#F6D365] transition-colors">
                    Save Result
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(drawTime, gameType, numberCount) {
    document.getElementById('modalDrawTime').value = drawTime;
    document.getElementById('modalGameType').value = gameType;
    document.getElementById('modalDrawTimeDisplay').textContent = drawTime;
    document.getElementById('modalGameTypeDisplay').textContent = gameType;

    // Generate input fields
    const container = document.getElementById('numberInputs');
    container.innerHTML = '';
    for (let i = 0; i < numberCount; i++) {
        const input = document.createElement('input');
        input.type = 'text';
        input.name = 'winning_numbers[]';
        input.maxLength = 1;
        input.inputMode = 'numeric';
        input.className = 'w-14 h-14 text-center text-2xl font-bold bg-gray-700 border-2 border-gray-600 rounded-lg text-white focus:border-[#D4AF37] focus:outline-none transition-colors';
        input.dataset.index = i;
        input.addEventListener('input', function(e) {
            const value = e.target.value;
            if (!/^[0-9]?$/.test(value)) {
                e.target.value = value.replace(/[^0-9]/g, '').slice(0, 1);
            }
            if (value && i < numberCount - 1) {
                const nextInput = container.querySelector(`[data-index="${i + 1}"]`);
                if (nextInput) nextInput.focus();
            }
        });
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Backspace' && !e.target.value && i > 0) {
                const prevInput = container.querySelector(`[data-index="${i - 1}"]`);
                if (prevInput) prevInput.focus();
            }
        });
        container.appendChild(input);
    }

    document.getElementById('numberModal').classList.remove('hidden');
    container.querySelector('[data-index="0"]').focus();
}

function openEditModal(drawTime, gameType, existingNumbers, resultId) {
    const numberCount = existingNumbers.length;
    openModal(drawTime, gameType, numberCount);

    // Fill in existing numbers
    const container = document.getElementById('numberInputs');
    existingNumbers.forEach((num, i) => {
        const input = container.querySelector(`[data-index="${i}"]`);
        if (input) input.value = num;
    });
}

function closeModal() {
    document.getElementById('numberModal').classList.add('hidden');
}

// Close modal on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeModal();
});

// Close modal on backdrop click
document.getElementById('numberModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>
@endsection
