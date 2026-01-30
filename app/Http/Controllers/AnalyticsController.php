<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function summary(): JsonResponse
    {
        $totalEarnings = Transaction::sum('amount');
        $todayEarnings = Transaction::whereDate('created_at', today())->sum('amount');
        $totalTransactions = Transaction::count();
        $todayTransactions = Transaction::whereDate('created_at', today())->count();
        $activeDevices = Device::where('is_active', true)->count();
        $onlineDevices = Device::where('last_seen_at', '>=', now()->subMinutes(15))->count();

        return response()->json([
            'success' => true,
            'summary' => [
                'total_earnings' => round($totalEarnings, 2),
                'today_earnings' => round($todayEarnings, 2),
                'total_transactions' => $totalTransactions,
                'today_transactions' => $todayTransactions,
                'active_devices' => $activeDevices,
                'online_devices' => $onlineDevices,
            ],
        ]);
    }

    public function byDevice(): JsonResponse
    {
        $devices = Device::with(['transactions' => function ($query) {
            $query->selectRaw('device_id, COUNT(*) as transaction_count, SUM(amount) as total_earnings')
                ->groupBy('device_id');
        }])->where('is_active', true)->get();

        $data = $devices->map(function ($device) {
            $stats = $device->transactions->first();
            return [
                'device_id' => $device->id,
                'device_name' => $device->device_name ?? 'Device ' . substr($device->device_id, 0, 8),
                'transaction_count' => $stats->transaction_count ?? 0,
                'total_earnings' => round($stats->total_earnings ?? 0, 2),
                'today_earnings' => round($device->getTodayEarnings(), 2),
                'last_seen_at' => $device->last_seen_at,
                'is_online' => $device->last_seen_at && $device->last_seen_at->gte(now()->subMinutes(15)),
            ];
        });

        return response()->json([
            'success' => true,
            'devices' => $data,
        ]);
    }

    public function byPeriod(Request $request): JsonResponse
    {
        $request->validate([
            'period' => 'nullable|in:day,week,month,year',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        $period = $request->query('period', 'week');

        $dateFormat = match ($period) {
            'day' => '%Y-%m-%d %H:00',
            'week' => '%Y-%m-%d',
            'month' => '%Y-%m-%d',
            'year' => '%Y-%m',
            default => '%Y-%m-%d',
        };

        $startDate = match ($period) {
            'day' => now()->startOfDay(),
            'week' => now()->subDays(7),
            'month' => now()->subDays(30),
            'year' => now()->subYear(),
            default => now()->subDays(7),
        };

        if ($request->start_date) {
            $startDate = $request->start_date;
        }

        $query = Transaction::selectRaw("DATE_FORMAT(created_at, '$dateFormat') as date, SUM(amount) as earnings, COUNT(*) as transactions")
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date');

        if ($request->end_date) {
            $query->where('created_at', '<=', $request->end_date);
        }

        $data = $query->get();

        return response()->json([
            'success' => true,
            'period' => $period,
            'data' => $data->map(fn ($row) => [
                'date' => $row->date,
                'earnings' => round($row->earnings, 2),
                'transactions' => $row->transactions,
            ]),
        ]);
    }

    public function transactions(Request $request): JsonResponse
    {
        $request->validate([
            'device_id' => 'nullable|integer|exists:devices,id',
            'date' => 'nullable|date',
            'game_type' => 'nullable|string',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = Transaction::with('device')
            ->orderBy('created_at', 'desc');

        if ($request->device_id) {
            $query->where('device_id', $request->device_id);
        }

        if ($request->date) {
            $query->whereDate('draw_date', $request->date);
        }

        if ($request->game_type) {
            $query->where('game_type', $request->game_type);
        }

        $perPage = $request->query('per_page', 20);
        $transactions = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'transactions' => $transactions->items(),
            'pagination' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
            ],
        ]);
    }

    /**
     * Get bets/transactions by date grouped by device
     */
    public function betsByDate(Request $request): JsonResponse
    {
        $request->validate([
            'date' => 'required|date',
            'game_type' => 'nullable|string',
            'draw_time' => 'nullable|string',
        ]);

        $date = $request->query('date');

        $query = Transaction::with('device')
            ->whereDate('draw_date', $date)
            ->orderBy('device_id')
            ->orderBy('draw_time')
            ->orderBy('created_at', 'desc');

        if ($request->game_type) {
            $query->where('game_type', $request->game_type);
        }

        if ($request->draw_time) {
            $query->where('draw_time', $request->draw_time);
        }

        $transactions = $query->get();

        // Group by device
        $groupedByDevice = $transactions->groupBy('device_id')->map(function ($deviceTransactions, $deviceId) {
            $device = $deviceTransactions->first()->device;
            $byDrawTime = $deviceTransactions->groupBy('draw_time');

            return [
                'device_id' => $deviceId,
                'device_name' => $device?->device_name ?? 'Unknown Device',
                'total_bets' => $deviceTransactions->count(),
                'total_amount' => round($deviceTransactions->sum('amount'), 2),
                'bets_by_draw_time' => $byDrawTime->map(function ($bets, $drawTime) {
                    return [
                        'draw_time' => $drawTime,
                        'count' => $bets->count(),
                        'amount' => round($bets->sum('amount'), 2),
                        'bets' => $bets->values()->map(fn ($bet) => [
                            'id' => $bet->id,
                            'transaction_id' => $bet->transaction_id,
                            'numbers' => $bet->numbers,
                            'game_type' => $bet->game_type,
                            'amount' => $bet->amount,
                            'verified' => $bet->verified,
                            'created_at' => $bet->created_at,
                        ]),
                    ];
                })->values(),
            ];
        })->values();

        // Summary stats
        $summary = [
            'date' => $date,
            'total_bets' => $transactions->count(),
            'total_amount' => round($transactions->sum('amount'), 2),
            'devices_count' => $groupedByDevice->count(),
            'by_game_type' => $transactions->groupBy('game_type')->map(fn ($g) => [
                'count' => $g->count(),
                'amount' => round($g->sum('amount'), 2),
            ]),
            'by_draw_time' => $transactions->groupBy('draw_time')->map(fn ($g) => [
                'count' => $g->count(),
                'amount' => round($g->sum('amount'), 2),
            ]),
        ];

        return response()->json([
            'success' => true,
            'summary' => $summary,
            'devices' => $groupedByDevice,
        ]);
    }

    /**
     * Get calendar data for bets (which dates have bets)
     */
    public function betsCalendar(Request $request): JsonResponse
    {
        $request->validate([
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer|min:2000',
        ]);

        $month = $request->query('month');
        $year = $request->query('year');

        $startDate = "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-01";
        $endDate = date('Y-m-t', strtotime($startDate));

        $data = Transaction::selectRaw('draw_date, COUNT(*) as bet_count, SUM(amount) as total_amount, COUNT(DISTINCT device_id) as device_count')
            ->whereBetween('draw_date', [$startDate, $endDate])
            ->groupBy('draw_date')
            ->get()
            ->keyBy('draw_date')
            ->map(fn ($row) => [
                'bet_count' => $row->bet_count,
                'total_amount' => round($row->total_amount, 2),
                'device_count' => $row->device_count,
            ]);

        return response()->json([
            'success' => true,
            'calendar_data' => $data,
        ]);
    }

    /**
     * Get most bet number combinations statistics
     */
    public function popularNumbers(Request $request): JsonResponse
    {
        $request->validate([
            'period' => 'nullable|in:day,week,month,year,all',
            'game_type' => 'nullable|string',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $period = $request->query('period', 'month');
        $limit = $request->query('limit', 20);

        $query = Transaction::query();

        // Apply period filter
        $startDate = match ($period) {
            'day' => now()->startOfDay(),
            'week' => now()->subDays(7),
            'month' => now()->subDays(30),
            'year' => now()->subYear(),
            'all' => null,
            default => now()->subDays(30),
        };

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($request->game_type) {
            $query->where('game_type', $request->game_type);
        }

        // Get all transactions and group by numbers
        $transactions = $query->get();

        $numberStats = $transactions->groupBy(function ($t) {
            // Convert numbers array to string for grouping
            $numbers = is_array($t->numbers) ? $t->numbers : json_decode($t->numbers, true);
            return implode('-', $numbers ?? []);
        })->map(function ($group, $numbersStr) {
            $first = $group->first();
            $numbers = is_array($first->numbers) ? $first->numbers : json_decode($first->numbers, true);
            return [
                'numbers' => $numbers,
                'numbers_str' => $numbersStr,
                'count' => $group->count(),
                'total_amount' => round($group->sum('amount'), 2),
                'game_type' => $first->game_type,
                'devices' => $group->pluck('device_id')->unique()->count(),
            ];
        })->sortByDesc('count')->take($limit)->values();

        // Get statistics by individual digit position
        $digitStats = [];
        $gameTypes = $transactions->pluck('game_type')->unique();

        foreach ($gameTypes as $gameType) {
            $gameTransactions = $transactions->where('game_type', $gameType);
            $digitCount = match ($gameType) {
                'SWER4' => 4,
                'SWER3' => 3,
                'SWER2' => 2,
                default => 0,
            };

            $positionStats = [];
            for ($pos = 0; $pos < $digitCount; $pos++) {
                $digitFreq = [];
                foreach ($gameTransactions as $t) {
                    $numbers = is_array($t->numbers) ? $t->numbers : json_decode($t->numbers, true);
                    if (isset($numbers[$pos])) {
                        $digit = (string)$numbers[$pos];
                        $digitFreq[$digit] = ($digitFreq[$digit] ?? 0) + 1;
                    }
                }
                arsort($digitFreq);
                $positionStats[] = [
                    'position' => $pos + 1,
                    'frequencies' => $digitFreq,
                    'most_common' => array_key_first($digitFreq),
                ];
            }

            $digitStats[$gameType] = $positionStats;
        }

        // Get stats by device
        $deviceStats = $transactions->groupBy('device_id')->map(function ($group) {
            $device = $group->first()->device;
            $topNumbers = $group->groupBy(function ($t) {
                $numbers = is_array($t->numbers) ? $t->numbers : json_decode($t->numbers, true);
                return implode('-', $numbers ?? []);
            })->sortByDesc(fn ($g) => $g->count())->take(5)->map(function ($g, $numbersStr) {
                $first = $g->first();
                $numbers = is_array($first->numbers) ? $first->numbers : json_decode($first->numbers, true);
                return [
                    'numbers' => $numbers,
                    'count' => $g->count(),
                ];
            })->values();

            return [
                'device_id' => $group->first()->device_id,
                'device_name' => $device?->device_name ?? 'Unknown Device',
                'total_bets' => $group->count(),
                'top_combinations' => $topNumbers,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'period' => $period,
            'summary' => [
                'total_bets' => $transactions->count(),
                'unique_combinations' => $numberStats->count(),
                'total_amount' => round($transactions->sum('amount'), 2),
            ],
            'top_combinations' => $numberStats,
            'digit_statistics' => $digitStats,
            'device_statistics' => $deviceStats,
        ]);
    }
}
