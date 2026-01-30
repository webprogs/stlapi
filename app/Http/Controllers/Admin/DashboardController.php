<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\Transaction;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $totalEarnings = Transaction::sum('amount');
        $todayEarnings = Transaction::whereDate('created_at', today())->sum('amount');
        $totalTransactions = Transaction::count();
        $todayTransactions = Transaction::whereDate('created_at', today())->count();
        $activeDevices = Device::where('is_active', true)->count();
        $onlineDevices = Device::where('last_seen_at', '>=', now()->subMinutes(15))->count();

        $recentTransactions = Transaction::with('device')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $devices = Device::withCount('transactions')
            ->withSum('transactions', 'amount')
            ->orderBy('last_seen_at', 'desc')
            ->get();

        return view('admin.dashboard', compact(
            'totalEarnings',
            'todayEarnings',
            'totalTransactions',
            'todayTransactions',
            'activeDevices',
            'onlineDevices',
            'recentTransactions',
            'devices'
        ));
    }
}
