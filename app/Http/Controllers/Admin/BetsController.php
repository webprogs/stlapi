<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\DrawResult;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BetsController extends Controller
{
    public function index(Request $request)
    {
        $selectedDate = $request->get('date', now()->format('Y-m-d'));
        $selectedGameType = $request->get('game_type');
        $selectedDrawTime = $request->get('draw_time');

        $query = Transaction::with('device')
            ->whereDate('draw_date', $selectedDate)
            ->orderBy('device_id')
            ->orderBy('draw_time')
            ->orderBy('created_at', 'desc');

        if ($selectedGameType) {
            $query->where('game_type', $selectedGameType);
        }

        if ($selectedDrawTime) {
            $query->where('draw_time', $selectedDrawTime);
        }

        $bets = $query->get();

        // Get draw results for the selected date to check for winners
        $drawResults = DrawResult::whereDate('draw_date', $selectedDate)->get();
        $drawResultsMap = [];
        foreach ($drawResults as $result) {
            $key = $result->draw_time . '_' . $result->game_type;
            $drawResultsMap[$key] = $result->winning_numbers;
        }

        // Check each bet for winning status
        $bets = $bets->map(function ($bet) use ($drawResultsMap) {
            $key = $bet->draw_time . '_' . $bet->game_type;
            $winningNumbers = $drawResultsMap[$key] ?? null;

            $bet->is_winner = false;
            $bet->winning_numbers = $winningNumbers;

            if ($winningNumbers) {
                $betNumbers = is_array($bet->numbers) ? $bet->numbers : json_decode($bet->numbers, true);
                $bet->is_winner = $this->checkWinner($betNumbers, $winningNumbers);
            }

            return $bet;
        });

        // Count winners
        $totalWinners = $bets->where('is_winner', true)->count();
        $totalPrize = $bets->where('is_winner', true)->sum(function ($bet) {
            return $this->getPrize($bet->game_type);
        });

        // Group by device
        $betsByDevice = $bets->groupBy('device_id')->map(function ($deviceBets, $deviceId) {
            $device = $deviceBets->first()->device;
            $winners = $deviceBets->where('is_winner', true);
            return [
                'device' => $device,
                'device_name' => $device?->device_name ?? 'Unknown Device',
                'total_bets' => $deviceBets->count(),
                'total_amount' => $deviceBets->sum('amount'),
                'winners_count' => $winners->count(),
                'winners_prize' => $winners->sum(fn ($b) => $this->getPrize($b->game_type)),
                'bets_by_draw_time' => $deviceBets->groupBy('draw_time'),
            ];
        });

        // Summary
        $summary = [
            'total_bets' => $bets->count(),
            'total_amount' => $bets->sum('amount'),
            'devices_count' => $betsByDevice->count(),
            'total_winners' => $totalWinners,
            'total_prize' => $totalPrize,
        ];

        // Calendar data for current month
        $calendarData = $this->getCalendarData($selectedDate);

        // Get draw results for display
        $drawResultsForDisplay = $drawResults->keyBy(fn ($r) => $r->draw_time . '_' . $r->game_type);

        $devices = Device::orderBy('device_name')->get();
        $gameTypes = ['SWER4', 'SWER3', 'SWER2'];
        $drawTimes = ['2:00 PM', '5:00 PM', '9:00 PM'];

        return view('admin.bets.index', compact(
            'betsByDevice',
            'summary',
            'selectedDate',
            'selectedGameType',
            'selectedDrawTime',
            'calendarData',
            'devices',
            'gameTypes',
            'drawTimes',
            'drawResultsForDisplay'
        ));
    }

    /**
     * Check if bet numbers match winning numbers
     * Handles both padded strings ("01") and integers (1)
     */
    private function checkWinner(array $betNumbers, array $winningNumbers): bool
    {
        if (count($betNumbers) !== count($winningNumbers)) {
            return false;
        }

        // Normalize both arrays: convert to padded strings for comparison
        $bet = array_map(function($num) {
            return str_pad((string)intval($num), 2, '0', STR_PAD_LEFT);
        }, $betNumbers);

        $winning = array_map(function($num) {
            return str_pad((string)intval($num), 2, '0', STR_PAD_LEFT);
        }, $winningNumbers);

        // Compare each number (exact match, same position)
        foreach ($bet as $index => $num) {
            if ($num !== $winning[$index]) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get prize amount for game type
     */
    private function getPrize(string $gameType): int
    {
        return match ($gameType) {
            'SWER4' => 5000,
            'SWER3' => 500,
            'SWER2' => 50,
            default => 0,
        };
    }

    public function statistics(Request $request)
    {
        $period = $request->get('period', 'month');
        $selectedGameType = $request->get('game_type');

        $startDate = match ($period) {
            'day' => now()->startOfDay(),
            'week' => now()->subDays(7),
            'month' => now()->subDays(30),
            'year' => now()->subYear(),
            'all' => null,
            default => now()->subDays(30),
        };

        $query = Transaction::with('device');

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($selectedGameType) {
            $query->where('game_type', $selectedGameType);
        }

        $transactions = $query->get();

        // Group by numbers to find most popular combinations
        $numberStats = $transactions->groupBy(function ($t) {
            $numbers = is_array($t->numbers) ? $t->numbers : json_decode($t->numbers, true);
            return implode('-', $numbers ?? []);
        })->map(function ($group, $numbersStr) {
            $first = $group->first();
            $numbers = is_array($first->numbers) ? $first->numbers : json_decode($first->numbers, true);
            return [
                'numbers' => $numbers,
                'numbers_str' => $numbersStr,
                'count' => $group->count(),
                'total_amount' => $group->sum('amount'),
                'game_type' => $first->game_type,
                'devices_count' => $group->pluck('device_id')->unique()->count(),
            ];
        })->sortByDesc('count')->take(20)->values();

        // Digit statistics by position
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
                ];
            }
            $digitStats[$gameType] = $positionStats;
        }

        // Device statistics
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
                'device_name' => $device?->device_name ?? 'Unknown Device',
                'total_bets' => $group->count(),
                'top_combinations' => $topNumbers,
            ];
        })->values();

        $summary = [
            'total_bets' => $transactions->count(),
            'unique_combinations' => $numberStats->count(),
            'total_amount' => $transactions->sum('amount'),
        ];

        $gameTypesList = ['SWER4', 'SWER3', 'SWER2'];

        return view('admin.bets.statistics', compact(
            'numberStats',
            'digitStats',
            'deviceStats',
            'summary',
            'period',
            'selectedGameType',
            'gameTypesList'
        ));
    }

    private function getCalendarData($selectedDate)
    {
        $date = \Carbon\Carbon::parse($selectedDate);
        $startOfMonth = $date->copy()->startOfMonth()->format('Y-m-d');
        $endOfMonth = $date->copy()->endOfMonth()->format('Y-m-d');

        return Transaction::selectRaw('draw_date, COUNT(*) as bet_count, SUM(amount) as total_amount')
            ->whereBetween('draw_date', [$startOfMonth, $endOfMonth])
            ->groupBy('draw_date')
            ->get()
            ->keyBy(fn ($row) => $row->draw_date->format('Y-m-d'))
            ->map(fn ($row) => [
                'bet_count' => $row->bet_count,
                'total_amount' => $row->total_amount,
            ]);
    }
}
