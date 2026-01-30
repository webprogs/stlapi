<?php

namespace App\Http\Controllers;

use App\Models\DrawResult;
use App\Models\Transaction;
use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DrawResultController extends Controller
{
    /**
     * Game types and their configurations
     */
    private const GAME_TYPES = ['SWER4', 'SWER3', 'SWER2'];
    private const DRAW_TIMES = ['2:00 PM', '5:00 PM', '9:00 PM'];
    private const PRIZES = [
        'SWER4' => 5000,
        'SWER3' => 500,
        'SWER2' => 50,
    ];

    /**
     * Get draw results for a specific date
     */
    public function getByDate(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
        ]);

        $date = $request->date;

        // Get all draw results for this date (admin-created ones have device_id = null)
        $results = DrawResult::whereNull('device_id')
            ->whereDate('draw_date', $date)
            ->get()
            ->keyBy(function ($item) {
                return $item->draw_time . '_' . $item->game_type;
            });

        // Build response with all possible slots
        $response = [];
        foreach (self::DRAW_TIMES as $drawTime) {
            $timeResults = [];
            foreach (self::GAME_TYPES as $gameType) {
                $key = $drawTime . '_' . $gameType;
                $result = $results->get($key);
                $timeResults[$gameType] = $result ? [
                    'id' => $result->id,
                    'winning_numbers' => $result->winning_numbers,
                    'created_at' => $result->created_at,
                ] : null;
            }
            $response[$drawTime] = $timeResults;
        }

        return response()->json([
            'success' => true,
            'date' => $date,
            'results' => $response,
        ]);
    }

    /**
     * Save or update draw result
     */
    public function store(Request $request)
    {
        $request->validate([
            'draw_date' => 'required|date|before_or_equal:today',
            'draw_time' => 'required|in:2:00 PM,5:00 PM,9:00 PM',
            'game_type' => 'required|in:SWER4,SWER3,SWER2',
            'winning_numbers' => 'required|array',
        ]);

        // Validate number count based on game type
        $expectedCount = match($request->game_type) {
            'SWER4' => 4,
            'SWER3' => 3,
            'SWER2' => 2,
        };

        if (count($request->winning_numbers) !== $expectedCount) {
            return response()->json([
                'success' => false,
                'message' => "Expected {$expectedCount} numbers for {$request->game_type}",
            ], 422);
        }

        // Validate each number is between 0-9
        foreach ($request->winning_numbers as $num) {
            if (!is_numeric($num) || $num < 0 || $num > 9) {
                return response()->json([
                    'success' => false,
                    'message' => 'Each number must be between 0 and 9',
                ], 422);
            }
        }

        // Find existing or create new (admin results have null device_id)
        $result = DrawResult::updateOrCreate(
            [
                'device_id' => null,
                'draw_date' => $request->draw_date,
                'draw_time' => $request->draw_time,
                'game_type' => $request->game_type,
            ],
            [
                'winning_numbers' => array_map('intval', $request->winning_numbers),
                'local_id' => 0, // Admin-created results
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Draw result saved successfully',
            'result' => $result,
        ]);
    }

    /**
     * Delete a draw result
     */
    public function destroy($id)
    {
        $result = DrawResult::whereNull('device_id')->findOrFail($id);
        $result->delete();

        return response()->json([
            'success' => true,
            'message' => 'Draw result deleted successfully',
        ]);
    }

    /**
     * Get winners for a specific date
     */
    public function getWinners(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'draw_time' => 'nullable|in:2:00 PM,5:00 PM,9:00 PM',
        ]);

        $date = $request->date;
        $drawTime = $request->draw_time;

        // Get draw results for this date (admin-created)
        $drawResultsQuery = DrawResult::whereNull('device_id')
            ->whereDate('draw_date', $date);

        if ($drawTime) {
            $drawResultsQuery->where('draw_time', $drawTime);
        }

        $drawResults = $drawResultsQuery->get();

        if ($drawResults->isEmpty()) {
            return response()->json([
                'success' => true,
                'date' => $date,
                'winners' => [],
                'summary' => [
                    'total_winners' => 0,
                    'total_prize' => 0,
                ],
            ]);
        }

        $winners = [];
        $totalWinners = 0;
        $totalPrize = 0;

        foreach ($drawResults as $drawResult) {
            // Find matching transactions
            $winningTransactions = Transaction::with('device')
                ->whereDate('draw_date', $date)
                ->where('draw_time', $drawResult->draw_time)
                ->where('game_type', $drawResult->game_type)
                ->get()
                ->filter(function ($transaction) use ($drawResult) {
                    // Ensure numbers is an array
                    $betNumbers = $transaction->numbers;
                    if (is_string($betNumbers)) {
                        $betNumbers = json_decode($betNumbers, true) ?? [];
                    }
                    return $this->checkWinner($betNumbers, $drawResult->winning_numbers);
                });

            foreach ($winningTransactions as $transaction) {
                $prize = self::PRIZES[$drawResult->game_type] ?? 0;
                $prizeWon = $prize * $transaction->amount;

                // Ensure numbers is an array for response
                $betNumbers = $transaction->numbers;
                if (is_string($betNumbers)) {
                    $betNumbers = json_decode($betNumbers, true) ?? [];
                }

                $winners[] = [
                    'transaction_id' => $transaction->transaction_id,
                    'device_id' => $transaction->device_id,
                    'device_name' => $transaction->device->device_name ?? 'Unknown Device',
                    'game_type' => $drawResult->game_type,
                    'draw_time' => $drawResult->draw_time,
                    'bet_numbers' => $betNumbers,
                    'winning_numbers' => $drawResult->winning_numbers,
                    'bet_amount' => $transaction->amount,
                    'prize_multiplier' => $prize,
                    'prize_won' => $prizeWon,
                    'transaction_date' => $transaction->device_created_at ?? $transaction->created_at,
                ];

                $totalWinners++;
                $totalPrize += $prizeWon;
            }
        }

        // Group by device
        $winnersByDevice = collect($winners)->groupBy('device_id')->map(function ($deviceWinners, $deviceId) {
            $device = Device::find($deviceId);
            return [
                'device_id' => $deviceId,
                'device_name' => $device->device_name ?? 'Unknown Device',
                'total_winners' => $deviceWinners->count(),
                'total_prize' => $deviceWinners->sum('prize_won'),
                'winners' => $deviceWinners->values(),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'date' => $date,
            'draw_time' => $drawTime,
            'winners_by_device' => $winnersByDevice,
            'all_winners' => $winners,
            'summary' => [
                'total_winners' => $totalWinners,
                'total_prize' => $totalPrize,
                'devices_with_winners' => $winnersByDevice->count(),
            ],
        ]);
    }

    /**
     * Get calendar data with results and winner counts
     */
    public function getCalendarData(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2100',
        ]);

        $month = $request->month;
        $year = $request->year;

        // Get all draw results for the month (admin-created)
        $results = DrawResult::whereNull('device_id')
            ->whereYear('draw_date', $year)
            ->whereMonth('draw_date', $month)
            ->select('draw_date', 'draw_time', 'game_type')
            ->get()
            ->groupBy(function ($item) {
                return $item->draw_date->format('Y-m-d');
            });

        // Get transactions for the month to calculate potential winners
        $transactions = Transaction::whereYear('draw_date', $year)
            ->whereMonth('draw_date', $month)
            ->select('draw_date', 'draw_time', 'game_type', 'numbers', 'amount')
            ->get()
            ->groupBy(function ($item) {
                return $item->draw_date->format('Y-m-d');
            });

        $calendarData = [];

        foreach ($results as $date => $dayResults) {
            $resultCount = $dayResults->count();
            $hasAllResults = $resultCount === 9; // 3 draw times * 3 game types

            // Count winners for this date
            $winnerCount = 0;
            $dayTransactions = $transactions->get($date, collect());

            foreach ($dayResults as $result) {
                $drawResult = DrawResult::whereNull('device_id')
                    ->whereDate('draw_date', $date)
                    ->where('draw_time', $result->draw_time)
                    ->where('game_type', $result->game_type)
                    ->first();

                if ($drawResult) {
                    $matchingTransactions = $dayTransactions
                        ->where('draw_time', $result->draw_time)
                        ->where('game_type', $result->game_type)
                        ->filter(function ($t) use ($drawResult) {
                            // Ensure numbers is an array
                            $betNumbers = $t->numbers;
                            if (is_string($betNumbers)) {
                                $betNumbers = json_decode($betNumbers, true) ?? [];
                            }
                            return $this->checkWinner($betNumbers, $drawResult->winning_numbers);
                        });

                    $winnerCount += $matchingTransactions->count();
                }
            }

            $calendarData[$date] = [
                'has_results' => true,
                'result_count' => $resultCount,
                'is_complete' => $hasAllResults,
                'winner_count' => $winnerCount,
            ];
        }

        return response()->json([
            'success' => true,
            'month' => $month,
            'year' => $year,
            'calendar_data' => $calendarData,
        ]);
    }

    /**
     * Check if bet numbers match winning numbers
     */
    private function checkWinner(array $betNumbers, array $winningNumbers): bool
    {
        // Normalize to integers
        $bet = array_map('intval', $betNumbers);
        $winning = array_map('intval', $winningNumbers);

        // Must have same count
        if (count($bet) !== count($winning)) {
            return false;
        }

        // Check exact match (order matters)
        return $bet === $winning;
    }

    /**
     * Get list of dates with draw results in a date range
     */
    public function getDatesWithResults(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $dates = DrawResult::whereNull('device_id')
            ->whereBetween('draw_date', [$request->start_date, $request->end_date])
            ->selectRaw('DATE(draw_date) as date, COUNT(*) as count')
            ->groupBy('date')
            ->pluck('count', 'date');

        return response()->json([
            'success' => true,
            'dates' => $dates,
        ]);
    }
}
