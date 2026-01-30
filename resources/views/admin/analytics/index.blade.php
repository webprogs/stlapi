@extends('admin.layouts.app')

@section('title', 'Analytics')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-white">Analytics</h1>
    <div class="flex gap-2">
        @foreach (['day' => 'Today', 'week' => 'Week', 'month' => 'Month', 'year' => 'Year'] as $value => $label)
            <a href="{{ route('admin.analytics.index', ['period' => $value]) }}"
               class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ $period === $value ? 'bg-[#D4AF37] text-gray-900' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>
</div>

<!-- Summary Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
        <p class="text-sm text-gray-400 mb-2">Total Earnings</p>
        <p class="text-3xl font-bold text-[#D4AF37]">₱{{ number_format($totalEarnings, 2) }}</p>
    </div>
    <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
        <p class="text-sm text-gray-400 mb-2">Total Transactions</p>
        <p class="text-3xl font-bold text-white">{{ number_format($totalTransactions) }}</p>
    </div>
    <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
        <p class="text-sm text-gray-400 mb-2">Active Devices</p>
        <p class="text-3xl font-bold text-[#1A7B52]">{{ $activeDevices }}</p>
    </div>
</div>

<!-- Period Data Chart (placeholder - requires JS charting library) -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
        <h2 class="text-lg font-semibold text-white mb-4">Earnings Over Time</h2>
        @if ($periodData->count() > 0)
            <div class="space-y-3">
                @foreach ($periodData as $data)
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-400">{{ $data->date }}</span>
                        <div class="flex items-center gap-4">
                            <div class="w-32 h-2 bg-gray-700 rounded-full overflow-hidden">
                                @php
                                    $maxEarnings = $periodData->max('earnings') ?: 1;
                                    $percentage = ($data->earnings / $maxEarnings) * 100;
                                @endphp
                                <div class="h-full bg-[#D4AF37] rounded-full" style="width: {{ $percentage }}%"></div>
                            </div>
                            <span class="text-sm font-medium text-[#D4AF37] w-24 text-right">₱{{ number_format($data->earnings, 2) }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-gray-500 text-center py-8">No data for this period</p>
        @endif
    </div>

    <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
        <h2 class="text-lg font-semibold text-white mb-4">Transactions Over Time</h2>
        @if ($periodData->count() > 0)
            <div class="space-y-3">
                @foreach ($periodData as $data)
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-400">{{ $data->date }}</span>
                        <div class="flex items-center gap-4">
                            <div class="w-32 h-2 bg-gray-700 rounded-full overflow-hidden">
                                @php
                                    $maxTx = $periodData->max('transactions') ?: 1;
                                    $percentage = ($data->transactions / $maxTx) * 100;
                                @endphp
                                <div class="h-full bg-[#0D5C3D] rounded-full" style="width: {{ $percentage }}%"></div>
                            </div>
                            <span class="text-sm font-medium text-[#1A7B52] w-16 text-right">{{ $data->transactions }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-gray-500 text-center py-8">No data for this period</p>
        @endif
    </div>
</div>

<!-- Device Performance -->
<div class="bg-gray-800 rounded-xl border border-gray-700">
    <div class="px-6 py-4 border-b border-gray-700">
        <h2 class="text-lg font-semibold text-white">Device Performance</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-700/50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Device</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Transactions</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Earnings</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">% of Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-700">
                @php $totalDeviceEarnings = $deviceStats->sum('earnings'); @endphp
                @forelse ($deviceStats as $device)
                    <tr class="hover:bg-gray-700/50">
                        <td class="px-6 py-4 font-medium text-white">{{ $device['name'] }}</td>
                        <td class="px-6 py-4 text-gray-300">{{ number_format($device['transactions']) }}</td>
                        <td class="px-6 py-4 text-[#D4AF37] font-medium">₱{{ number_format($device['earnings'], 2) }}</td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <div class="w-20 h-2 bg-gray-700 rounded-full overflow-hidden">
                                    @php $pct = $totalDeviceEarnings > 0 ? ($device['earnings'] / $totalDeviceEarnings) * 100 : 0; @endphp
                                    <div class="h-full bg-[#D4AF37] rounded-full" style="width: {{ $pct }}%"></div>
                                </div>
                                <span class="text-sm text-gray-400">{{ number_format($pct, 1) }}%</span>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center text-gray-500">No devices registered</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
