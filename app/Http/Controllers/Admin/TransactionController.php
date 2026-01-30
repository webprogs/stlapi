<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaction::with('device')->orderBy('created_at', 'desc');

        if ($request->filled('device_id')) {
            $query->where('device_id', $request->device_id);
        }

        if ($request->filled('date')) {
            $query->whereDate('draw_date', $request->date);
        }

        if ($request->filled('game_type')) {
            $query->where('game_type', $request->game_type);
        }

        $transactions = $query->paginate(20)->withQueryString();
        $devices = Device::orderBy('device_name')->get();
        $gameTypes = ['SWER4', 'SWER3', 'SWER2'];

        return view('admin.transactions.index', compact('transactions', 'devices', 'gameTypes'));
    }

    public function show(Transaction $transaction)
    {
        $transaction->load('device');
        return view('admin.transactions.show', compact('transaction'));
    }
}
