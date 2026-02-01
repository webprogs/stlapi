<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function summary(Request $request): JsonResponse
    {
        $dateFrom = $request->get('date_from', today()->startOfMonth());
        $dateTo = $request->get('date_to', today());

        $totalBets = Transaction::whereBetween('draw_date', [$dateFrom, $dateTo])->sum('amount');
        $totalWinnings = Transaction::whereBetween('draw_date', [$dateFrom, $dateTo])
            ->whereIn('status', ['won', 'claimed'])
            ->sum('win_amount');
        $netEarnings = $totalBets * config('stl.net_earnings_rate') - $totalWinnings;

        $transactionCounts = Transaction::whereBetween('draw_date', [$dateFrom, $dateTo])
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        $activeDevices = Device::where('is_active', true)
            ->whereNotNull('last_sync_at')
            ->where('last_sync_at', '>=', now()->subDay())
            ->count();

        $totalDevices = Device::count();

        return response()->json([
            'period' => [
                'from' => $dateFrom,
                'to' => $dateTo,
            ],
            'financial' => [
                'total_bets' => round($totalBets, 2),
                'total_winnings' => round($totalWinnings, 2),
                'net_earnings' => round($netEarnings, 2),
                'gross_revenue' => round($totalBets * config('stl.net_earnings_rate'), 2),
            ],
            'transactions' => [
                'total' => array_sum($transactionCounts->toArray()),
                'pending' => $transactionCounts['pending'] ?? 0,
                'won' => $transactionCounts['won'] ?? 0,
                'lost' => $transactionCounts['lost'] ?? 0,
                'claimed' => $transactionCounts['claimed'] ?? 0,
            ],
            'devices' => [
                'total' => $totalDevices,
                'active_last_24h' => $activeDevices,
            ],
        ]);
    }

    public function byGame(Request $request): JsonResponse
    {
        $dateFrom = $request->get('date_from', today()->startOfMonth());
        $dateTo = $request->get('date_to', today());

        $stats = Transaction::whereBetween('draw_date', [$dateFrom, $dateTo])
            ->select(
                'game_type',
                DB::raw('count(*) as total_bets'),
                DB::raw('sum(amount) as total_amount'),
                DB::raw('sum(case when status in ("won", "claimed") then win_amount else 0 end) as total_winnings')
            )
            ->groupBy('game_type')
            ->get()
            ->map(function ($item) {
                return [
                    'game_type' => $item->game_type,
                    'total_bets' => $item->total_bets,
                    'total_amount' => round($item->total_amount, 2),
                    'total_winnings' => round($item->total_winnings, 2),
                    'net_earnings' => round($item->total_amount * config('stl.net_earnings_rate') - $item->total_winnings, 2),
                ];
            });

        return response()->json([
            'period' => ['from' => $dateFrom, 'to' => $dateTo],
            'data' => $stats,
        ]);
    }

    public function byDrawTime(Request $request): JsonResponse
    {
        $dateFrom = $request->get('date_from', today()->startOfMonth());
        $dateTo = $request->get('date_to', today());

        $stats = Transaction::whereBetween('draw_date', [$dateFrom, $dateTo])
            ->select(
                'draw_time',
                DB::raw('count(*) as total_bets'),
                DB::raw('sum(amount) as total_amount'),
                DB::raw('sum(case when status in ("won", "claimed") then win_amount else 0 end) as total_winnings')
            )
            ->groupBy('draw_time')
            ->orderByRaw("FIELD(draw_time, '11AM', '4PM', '9PM')")
            ->get()
            ->map(function ($item) {
                return [
                    'draw_time' => $item->draw_time,
                    'total_bets' => $item->total_bets,
                    'total_amount' => round($item->total_amount, 2),
                    'total_winnings' => round($item->total_winnings, 2),
                    'net_earnings' => round($item->total_amount * config('stl.net_earnings_rate') - $item->total_winnings, 2),
                ];
            });

        return response()->json([
            'period' => ['from' => $dateFrom, 'to' => $dateTo],
            'data' => $stats,
        ]);
    }

    public function byDevice(Request $request): JsonResponse
    {
        $dateFrom = $request->get('date_from', today()->startOfMonth());
        $dateTo = $request->get('date_to', today());

        $stats = Transaction::whereBetween('draw_date', [$dateFrom, $dateTo])
            ->join('devices', 'transactions.device_id', '=', 'devices.id')
            ->select(
                'devices.id',
                'devices.device_name',
                'devices.uuid',
                DB::raw('count(*) as total_bets'),
                DB::raw('sum(transactions.amount) as total_amount'),
                DB::raw('sum(case when transactions.status in ("won", "claimed") then transactions.win_amount else 0 end) as total_winnings')
            )
            ->groupBy('devices.id', 'devices.device_name', 'devices.uuid')
            ->orderByDesc('total_amount')
            ->limit($request->get('limit', 10))
            ->get()
            ->map(function ($item) {
                return [
                    'device_id' => $item->id,
                    'device_name' => $item->device_name,
                    'uuid' => $item->uuid,
                    'total_bets' => $item->total_bets,
                    'total_amount' => round($item->total_amount, 2),
                    'total_winnings' => round($item->total_winnings, 2),
                    'net_earnings' => round($item->total_amount * config('stl.net_earnings_rate') - $item->total_winnings, 2),
                ];
            });

        return response()->json([
            'period' => ['from' => $dateFrom, 'to' => $dateTo],
            'data' => $stats,
        ]);
    }

    public function daily(Request $request): JsonResponse
    {
        $dateFrom = $request->get('date_from', today()->subDays(30));
        $dateTo = $request->get('date_to', today());

        $stats = Transaction::whereBetween('draw_date', [$dateFrom, $dateTo])
            ->select(
                'draw_date',
                DB::raw('count(*) as total_bets'),
                DB::raw('sum(amount) as total_amount'),
                DB::raw('sum(case when status in ("won", "claimed") then win_amount else 0 end) as total_winnings')
            )
            ->groupBy('draw_date')
            ->orderBy('draw_date')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->draw_date->toDateString(),
                    'total_bets' => $item->total_bets,
                    'total_amount' => round($item->total_amount, 2),
                    'total_winnings' => round($item->total_winnings, 2),
                    'net_earnings' => round($item->total_amount * config('stl.net_earnings_rate') - $item->total_winnings, 2),
                ];
            });

        return response()->json([
            'period' => ['from' => $dateFrom, 'to' => $dateTo],
            'data' => $stats,
        ]);
    }

    public function device(Request $request, Device $device): JsonResponse
    {
        $period = $request->get('period', 'month');

        // Calculate date range based on period
        $dateTo = today();
        $dateFrom = match ($period) {
            'day' => today(),
            'week' => today()->startOfWeek(),
            'month' => today()->startOfMonth(),
            'year' => today()->startOfYear(),
            'all' => null,
            default => today()->startOfMonth(),
        };

        $query = Transaction::where('device_id', $device->id);

        if ($dateFrom) {
            $query->whereBetween('draw_date', [$dateFrom, $dateTo]);
        }

        // Summary stats
        $totalBets = (clone $query)->sum('amount');
        $totalWinnings = (clone $query)
            ->whereIn('status', ['won', 'claimed'])
            ->sum('win_amount');
        $netEarnings = $totalBets * config('stl.net_earnings_rate') - $totalWinnings;

        $transactionCounts = (clone $query)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        // By game type
        $byGame = (clone $query)
            ->select(
                'game_type',
                DB::raw('count(*) as total_bets'),
                DB::raw('sum(amount) as total_amount'),
                DB::raw('sum(case when status in ("won", "claimed") then win_amount else 0 end) as total_winnings'),
                DB::raw('sum(case when status in ("won", "claimed") then 1 else 0 end) as winning_bets')
            )
            ->groupBy('game_type')
            ->get()
            ->map(fn($item) => [
                'game_type' => $item->game_type,
                'total_bets' => $item->total_bets,
                'winning_bets' => (int) $item->winning_bets,
                'total_amount' => round($item->total_amount, 2),
                'total_winnings' => round($item->total_winnings, 2),
            ]);

        // By draw time
        $byDrawTime = (clone $query)
            ->select(
                'draw_time',
                DB::raw('count(*) as total_bets'),
                DB::raw('sum(amount) as total_amount'),
                DB::raw('sum(case when status in ("won", "claimed") then 1 else 0 end) as winning_bets')
            )
            ->groupBy('draw_time')
            ->orderByRaw("FIELD(draw_time, '11AM', '4PM', '9PM')")
            ->get()
            ->map(fn($item) => [
                'draw_time' => $item->draw_time,
                'total_bets' => $item->total_bets,
                'winning_bets' => (int) $item->winning_bets,
                'total_amount' => round($item->total_amount, 2),
            ]);

        // Daily breakdown (last 7 days for day/week, last 30 for month, last 12 months for year)
        $dailyQuery = Transaction::where('device_id', $device->id);
        if ($dateFrom) {
            $dailyQuery->whereBetween('draw_date', [$dateFrom, $dateTo]);
        }

        $daily = $dailyQuery
            ->select(
                'draw_date',
                DB::raw('count(*) as total_bets'),
                DB::raw('sum(amount) as total_amount'),
                DB::raw('sum(case when status in ("won", "claimed") then win_amount else 0 end) as total_winnings'),
                DB::raw('sum(case when status in ("won", "claimed") then 1 else 0 end) as winning_bets')
            )
            ->groupBy('draw_date')
            ->orderBy('draw_date', 'desc')
            ->limit(30)
            ->get()
            ->map(fn($item) => [
                'date' => $item->draw_date->toDateString(),
                'total_bets' => $item->total_bets,
                'winning_bets' => (int) $item->winning_bets,
                'total_amount' => round($item->total_amount, 2),
                'total_winnings' => round($item->total_winnings, 2),
                'net_earnings' => round($item->total_amount * config('stl.net_earnings_rate') - $item->total_winnings, 2),
            ]);

        return response()->json([
            'device' => [
                'id' => $device->id,
                'name' => $device->device_name,
                'uuid' => $device->uuid,
            ],
            'period' => [
                'type' => $period,
                'from' => $dateFrom?->toDateString(),
                'to' => $dateTo->toDateString(),
            ],
            'summary' => [
                'total_bets' => round($totalBets, 2),
                'total_winnings' => round($totalWinnings, 2),
                'net_earnings' => round($netEarnings, 2),
                'gross_revenue' => round($totalBets * config('stl.net_earnings_rate'), 2),
            ],
            'transactions' => [
                'total' => array_sum($transactionCounts->toArray()),
                'pending' => $transactionCounts['pending'] ?? 0,
                'won' => $transactionCounts['won'] ?? 0,
                'lost' => $transactionCounts['lost'] ?? 0,
                'claimed' => $transactionCounts['claimed'] ?? 0,
                'winning_total' => ($transactionCounts['won'] ?? 0) + ($transactionCounts['claimed'] ?? 0),
            ],
            'by_game' => $byGame,
            'by_draw_time' => $byDrawTime,
            'daily' => $daily,
        ]);
    }

    public function topNumbers(Request $request): JsonResponse
    {
        $dateFrom = $request->get('date_from', today()->startOfMonth());
        $dateTo = $request->get('date_to', today());
        $gameType = $request->get('game_type');

        $query = Transaction::whereBetween('draw_date', [$dateFrom, $dateTo]);

        if ($gameType) {
            $query->where('game_type', $gameType);
        }

        $transactions = $query->get(['numbers', 'amount']);

        $numberCounts = [];
        foreach ($transactions as $tx) {
            $key = implode('-', $tx->numbers);
            if (!isset($numberCounts[$key])) {
                $numberCounts[$key] = ['count' => 0, 'amount' => 0];
            }
            $numberCounts[$key]['count']++;
            $numberCounts[$key]['amount'] += $tx->amount;
        }

        arsort($numberCounts);
        $topNumbers = array_slice($numberCounts, 0, $request->get('limit', 20), true);

        $result = [];
        foreach ($topNumbers as $numbers => $data) {
            $result[] = [
                'numbers' => $numbers,
                'bet_count' => $data['count'],
                'total_amount' => round($data['amount'], 2),
            ];
        }

        return response()->json([
            'period' => ['from' => $dateFrom, 'to' => $dateTo],
            'game_type' => $gameType ?? 'all',
            'data' => $result,
        ]);
    }
}
