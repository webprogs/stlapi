<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Transaction::with('device:id,device_name,uuid');

        // Filters
        if ($request->has('device_id')) {
            $query->where('device_id', $request->device_id);
        }

        if ($request->has('game_type')) {
            $query->where('game_type', $request->game_type);
        }

        if ($request->has('draw_time')) {
            $query->where('draw_time', $request->draw_time);
        }

        if ($request->has('draw_date')) {
            $query->where('draw_date', $request->draw_date);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('date_from')) {
            $query->where('draw_date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('draw_date', '<=', $request->date_to);
        }

        if ($request->has('search')) {
            $query->where('transaction_id', 'like', '%' . $request->search . '%');
        }

        $transactions = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json($transactions);
    }

    public function show(Transaction $transaction): JsonResponse
    {
        $transaction->load('device:id,device_name,uuid');

        return response()->json($transaction);
    }

    public function claim(Transaction $transaction): JsonResponse
    {
        if ($transaction->status !== 'won') {
            return response()->json([
                'message' => 'Only winning transactions can be claimed',
                'current_status' => $transaction->status,
            ], 422);
        }

        $transaction->markAsClaimed();

        return response()->json([
            'message' => 'Transaction claimed successfully',
            'transaction' => $transaction->fresh(),
        ]);
    }
}
