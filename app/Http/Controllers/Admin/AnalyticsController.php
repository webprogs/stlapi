<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->get('period', 'week');

        // Summary stats
        $totalEarnings = Transaction::sum('amount');
        $totalTransactions = Transaction::count();
        $activeDevices = Device::where('is_active', true)->count();

        // Period data
        $periodData = $this->getPeriodData($period);

        // Device stats
        $deviceStats = Device::withCount('transactions')
            ->withSum('transactions', 'amount')
            ->where('is_active', true)
            ->get()
            ->map(function ($device) {
                return [
                    'name' => $device->device_name ?? 'Device ' . substr($device->device_id, 0, 8),
                    'transactions' => $device->transactions_count,
                    'earnings' => $device->transactions_sum_amount ?? 0,
                ];
            });

        return view('admin.analytics.index', compact(
            'totalEarnings',
            'totalTransactions',
            'activeDevices',
            'periodData',
            'deviceStats',
            'period'
        ));
    }

    private function getPeriodData($period)
    {
        $startDate = match ($period) {
            'day' => now()->startOfDay(),
            'week' => now()->subDays(7),
            'month' => now()->subDays(30),
            'year' => now()->subYear(),
            default => now()->subDays(7),
        };

        $dateFormat = match ($period) {
            'day' => '%Y-%m-%d %H:00',
            default => '%Y-%m-%d',
        };

        return Transaction::selectRaw("DATE_FORMAT(created_at, '$dateFormat') as date, SUM(amount) as earnings, COUNT(*) as transactions")
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }
}
