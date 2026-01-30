<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
     * Display the draw results page
     */
    public function index(Request $request)
    {
        $date = $request->get('date', now()->format('Y-m-d'));
        $selectedDate = \Carbon\Carbon::parse($date);

        // Prevent future dates
        if ($selectedDate->isFuture()) {
            $selectedDate = now();
            $date = $selectedDate->format('Y-m-d');
        }

        // Get results for selected date (admin-created)
        $results = DrawResult::whereNull('device_id')
            ->whereDate('draw_date', $date)
            ->get()
            ->keyBy(function ($item) {
                return $item->draw_time . '_' . $item->game_type;
            });

        // Get calendar data for current month
        $monthStart = $selectedDate->copy()->startOfMonth();
        $monthEnd = $selectedDate->copy()->endOfMonth();

        $calendarResults = DrawResult::whereNull('device_id')
            ->whereBetween('draw_date', [$monthStart, $monthEnd])
            ->selectRaw('DATE(draw_date) as date, COUNT(*) as count')
            ->groupBy('date')
            ->pluck('count', 'date')
            ->toArray();

        // Get winners for selected date
        $winners = $this->getWinnersForDate($date);

        return view('admin.draw-results.index', [
            'selectedDate' => $selectedDate,
            'results' => $results,
            'calendarResults' => $calendarResults,
            'winners' => $winners,
            'gameTypes' => self::GAME_TYPES,
            'drawTimes' => self::DRAW_TIMES,
            'prizes' => self::PRIZES,
        ]);
    }

    /**
     * Store or update a draw result
     */
    public function store(Request $request)
    {
        $request->validate([
            'draw_date' => 'required|date|before_or_equal:today',
            'draw_time' => 'required|in:2:00 PM,5:00 PM,9:00 PM',
            'game_type' => 'required|in:SWER4,SWER3,SWER2',
            'winning_numbers' => 'required|array',
            'winning_numbers.*' => 'required|integer|min:0|max:9',
        ]);

        // Validate number count based on game type
        $expectedCount = match($request->game_type) {
            'SWER4' => 4,
            'SWER3' => 3,
            'SWER2' => 2,
        };

        if (count($request->winning_numbers) !== $expectedCount) {
            return back()->withErrors(['winning_numbers' => "Expected {$expectedCount} numbers for {$request->game_type}"]);
        }

        // Find existing or create new (admin results have null device_id)
        // Pad single digit numbers with leading zeros (e.g., 1 -> "01")
        $paddedNumbers = array_map(function($num) {
            return str_pad((string)$num, 2, '0', STR_PAD_LEFT);
        }, $request->winning_numbers);

        DrawResult::updateOrCreate(
            [
                'device_id' => null,
                'draw_date' => $request->draw_date,
                'draw_time' => $request->draw_time,
                'game_type' => $request->game_type,
            ],
            [
                'winning_numbers' => $paddedNumbers,
                'local_id' => 0,
            ]
        );

        return redirect()
            ->route('admin.draw-results.index', ['date' => $request->draw_date])
            ->with('success', 'Draw result saved successfully!');
    }

    /**
     * Delete a draw result
     */
    public function destroy($id)
    {
        $result = DrawResult::whereNull('device_id')->findOrFail($id);
        $date = $result->draw_date->format('Y-m-d');
        $result->delete();

        return redirect()
            ->route('admin.draw-results.index', ['date' => $date])
            ->with('success', 'Draw result deleted successfully!');
    }

    /**
     * Get winners for a specific date
     */
    private function getWinnersForDate($date)
    {
        // Get admin-created draw results for this date
        $drawResults = DrawResult::whereNull('device_id')
            ->whereDate('draw_date', $date)
            ->get();

        if ($drawResults->isEmpty()) {
            return [
                'by_device' => collect(),
                'total_winners' => 0,
                'total_prize' => 0,
            ];
        }

        $allWinners = collect();

        foreach ($drawResults as $drawResult) {
            // Find matching transactions
            $transactions = Transaction::with('device')
                ->whereDate('draw_date', $date)
                ->where('draw_time', $drawResult->draw_time)
                ->where('game_type', $drawResult->game_type)
                ->get();

            foreach ($transactions as $transaction) {
                // Ensure numbers is an array (handle both JSON string and array)
                $betNumbers = $transaction->numbers;
                if (is_string($betNumbers)) {
                    $betNumbers = json_decode($betNumbers, true) ?? [];
                }

                if ($this->checkWinner($betNumbers, $drawResult->winning_numbers)) {
                    $prize = self::PRIZES[$drawResult->game_type] ?? 0;
                    $prizeWon = $prize * $transaction->amount;

                    $allWinners->push([
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
                    ]);
                }
            }
        }

        // Group by device
        $byDevice = $allWinners->groupBy('device_id')->map(function ($winners, $deviceId) {
            $device = Device::find($deviceId);
            return [
                'device_id' => $deviceId,
                'device_name' => $device->device_name ?? 'Unknown Device',
                'winners' => $winners,
                'total_winners' => $winners->count(),
                'total_prize' => $winners->sum('prize_won'),
            ];
        });

        return [
            'by_device' => $byDevice,
            'total_winners' => $allWinners->count(),
            'total_prize' => $allWinners->sum('prize_won'),
        ];
    }

    /**
     * Check if bet numbers match winning numbers
     * Handles both padded strings ("01") and integers (1)
     */
    private function checkWinner(array $betNumbers, array $winningNumbers): bool
    {
        // Normalize both arrays: convert to padded strings and re-index
        $bet = array_values(array_map(function($num) {
            return str_pad((string)intval($num), 2, '0', STR_PAD_LEFT);
        }, $betNumbers));

        $winning = array_values(array_map(function($num) {
            return str_pad((string)intval($num), 2, '0', STR_PAD_LEFT);
        }, $winningNumbers));

        if (count($bet) !== count($winning)) {
            return false;
        }

        // Compare each number at each position
        foreach ($bet as $index => $num) {
            if ($num !== $winning[$index]) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get number count for game type
     */
    public static function getNumberCount($gameType): int
    {
        return match($gameType) {
            'SWER4' => 4,
            'SWER3' => 3,
            'SWER2' => 2,
            default => 0,
        };
    }
}
