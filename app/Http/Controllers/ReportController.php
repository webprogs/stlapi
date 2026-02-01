<?php

namespace App\Http\Controllers;

use App\Models\DrawResult;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function daily(Request $request): JsonResponse
    {
        $date = $request->get('date', today()->toDateString());

        // Get all transactions for the day
        $transactions = Transaction::where('draw_date', $date)
            ->with('device:id,device_name')
            ->get();

        // Group by game type and draw time
        $byGameAndTime = $transactions->groupBy(['game_type', 'draw_time'])
            ->map(function ($games) {
                return $games->map(function ($txs) {
                    return [
                        'total_bets' => $txs->count(),
                        'total_amount' => round($txs->sum('amount'), 2),
                        'total_winnings' => round($txs->whereIn('status', ['won', 'claimed'])->sum('win_amount'), 2),
                        'won_count' => $txs->whereIn('status', ['won', 'claimed'])->count(),
                        'claimed_count' => $txs->where('status', 'claimed')->count(),
                    ];
                });
            });

        // Get draw results for the day
        $drawResults = DrawResult::where('draw_date', $date)
            ->get()
            ->groupBy('game_type')
            ->map(function ($results) {
                return $results->keyBy('draw_time')->map(function ($result) {
                    return $result->winning_numbers;
                });
            });

        // Device summary
        $byDevice = $transactions->groupBy('device_id')
            ->map(function ($txs) {
                $device = $txs->first()->device;
                return [
                    'device_name' => $device->device_name ?? 'Unknown',
                    'total_bets' => $txs->count(),
                    'total_amount' => round($txs->sum('amount'), 2),
                    'total_winnings' => round($txs->whereIn('status', ['won', 'claimed'])->sum('win_amount'), 2),
                ];
            })
            ->values();

        // Summary totals
        $summary = [
            'total_bets' => $transactions->count(),
            'total_amount' => round($transactions->sum('amount'), 2),
            'total_winnings' => round($transactions->whereIn('status', ['won', 'claimed'])->sum('win_amount'), 2),
            'gross_revenue' => round($transactions->sum('amount') * config('stl.net_earnings_rate'), 2),
            'net_earnings' => round(
                $transactions->sum('amount') * config('stl.net_earnings_rate') -
                $transactions->whereIn('status', ['won', 'claimed'])->sum('win_amount'),
                2
            ),
            'pending_count' => $transactions->where('status', 'pending')->count(),
            'won_count' => $transactions->whereIn('status', ['won', 'claimed'])->count(),
            'lost_count' => $transactions->where('status', 'lost')->count(),
            'claimed_count' => $transactions->where('status', 'claimed')->count(),
        ];

        return response()->json([
            'date' => $date,
            'summary' => $summary,
            'by_game_and_time' => $byGameAndTime,
            'draw_results' => $drawResults,
            'by_device' => $byDevice,
        ]);
    }
}
