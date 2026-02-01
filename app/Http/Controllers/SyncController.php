<?php

namespace App\Http\Controllers;

use App\Models\DeviceUser;
use App\Models\DrawResult;
use App\Models\SyncLog;
use App\Models\Transaction;
use App\Rules\NumbersMatchGameType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SyncController extends Controller
{
    public function sync(Request $request): JsonResponse
    {
        try {
            $device = $request->get('device');
            $table = $request->input('table');

            // Common validation rules
            $commonRules = [
                'table' => 'required|string|in:transactions,draw_results',
                'action' => 'required|string|in:insert,update,delete',
                'recordId' => 'required',
                'payload' => 'required|array',
            ];

            // Table-specific validation rules
            if ($table === 'draw_results') {
                $rules = array_merge($commonRules, [
                    'payload.draw_date' => 'required|date',
                    'payload.draw_time' => 'required|in:11AM,4PM,9PM',
                    'payload.game_type' => 'required|in:SWER2,SWER3,SWER4',
                    'payload.winning_numbers' => ['required', 'array', new NumbersMatchGameType($request->input('payload.game_type'))],
                    'payload.is_official' => 'required|boolean',
                    'payload.set_by' => 'required|exists:users,id',
                    'payload.modified_at' => 'nullable|date',
                ]);
            } else {
                $rules = array_merge($commonRules, [
                    'payload.transaction_id' => 'required|string|unique:transactions,transaction_id',
                    'payload.user_id' => 'nullable',
                    'payload.game_type' => 'required|in:SWER2,SWER3,SWER4',
                    'payload.numbers' => ['required', 'array', new NumbersMatchGameType($request->input('payload.game_type'))],
                    'payload.amount' => 'required|numeric|min:' . config('stl.bet_limits.min') . '|max:' . config('stl.bet_limits.max'),
                    'payload.draw_time' => 'required|in:11AM,4PM,9PM',
                    'payload.draw_date' => 'required|date',
                ]);
            }

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $payload = $request->input('payload');

            if ($table === 'draw_results') {
                // Check for duplicate draw result
                $existing = DrawResult::where('draw_date', $payload['draw_date'])
                    ->where('draw_time', $payload['draw_time'])
                    ->where('game_type', $payload['game_type'])
                    ->first();

                if ($existing) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Draw result already exists for this draw_date, draw_time, and game_type',
                        'draw_result' => $existing,
                    ], 409);
                }

                $drawResult = DrawResult::create([
                    'draw_date' => $payload['draw_date'],
                    'draw_time' => $payload['draw_time'],
                    'game_type' => $payload['game_type'],
                    'winning_numbers' => $payload['winning_numbers'],
                    'set_by' => $payload['set_by'],
                    'is_official' => $payload['is_official'],
                    'modified_at' => $payload['modified_at'] ?? null,
                ]);

                SyncLog::create([
                    'device_id' => $device->id,
                    'sync_type' => 'push',
                    'records_synced' => 1,
                    'status' => 'success',
                    'ip_address' => $request->ip(),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Draw result synced successfully',
                    'draw_result' => $drawResult,
                ], 201);
            }

            // Handle transactions (existing logic)
            $transaction = Transaction::create([
                'transaction_id' => $payload['transaction_id'],
                'device_id' => $device->id,
                'local_user_id' => $payload['user_id'] ?? null,
                'game_type' => $payload['game_type'],
                'numbers' => $payload['numbers'],
                'amount' => $payload['amount'],
                'draw_time' => $payload['draw_time'],
                'draw_date' => $payload['draw_date'],
                'local_created_at' => $payload['created_at'] ?? now(),
            ]);

            SyncLog::create([
                'device_id' => $device->id,
                'sync_type' => 'push',
                'records_synced' => 1,
                'status' => 'success',
                'ip_address' => $request->ip(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Transaction synced successfully',
                'transaction' => $transaction,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Sync failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function batch(Request $request): JsonResponse
    {
        $device = $request->get('device');

        $request->validate([
            'transactions' => 'required|array|min:1',
            'transactions.*.transaction_id' => 'required|string',
            'transactions.*.local_user_id' => 'nullable|string',
            'transactions.*.game_type' => 'required|in:SWER2,SWER3,SWER4',
            'transactions.*.numbers' => 'required|array',
            'transactions.*.amount' => 'required|numeric|min:' . config('stl.bet_limits.min') . '|max:' . config('stl.bet_limits.max'),
            'transactions.*.draw_time' => 'required|in:11AM,4PM,9PM',
            'transactions.*.draw_date' => 'required|date',
            'transactions.*.local_created_at' => 'required|date',
            'users' => 'sometimes|array',
            'users.*.local_user_id' => 'required|string',
            'users.*.name' => 'required|string',
            'users.*.pin' => 'nullable|string',
            'users.*.is_active' => 'sometimes|boolean',
        ]);

        $results = [
            'transactions' => ['synced' => 0, 'skipped' => 0, 'errors' => []],
            'users' => ['synced' => 0, 'skipped' => 0, 'errors' => []],
        ];

        DB::beginTransaction();

        try {
            // Sync users first
            if ($request->has('users')) {
                foreach ($request->users as $userData) {
                    $existingUser = DeviceUser::where('device_id', $device->id)
                        ->where('local_user_id', $userData['local_user_id'])
                        ->first();

                    if ($existingUser) {
                        $existingUser->update([
                            'name' => $userData['name'],
                            'pin' => $userData['pin'] ?? $existingUser->pin,
                            'is_active' => $userData['is_active'] ?? $existingUser->is_active,
                        ]);
                        $results['users']['skipped']++;
                    } else {
                        DeviceUser::create([
                            'device_id' => $device->id,
                            'local_user_id' => $userData['local_user_id'],
                            'name' => $userData['name'],
                            'pin' => $userData['pin'] ?? null,
                            'is_active' => $userData['is_active'] ?? true,
                        ]);
                        $results['users']['synced']++;
                    }
                }
            }

            // Sync transactions
            foreach ($request->transactions as $index => $txData) {
                // Skip if already exists
                if (Transaction::where('transaction_id', $txData['transaction_id'])->exists()) {
                    $results['transactions']['skipped']++;
                    continue;
                }

                // Validate numbers match game type
                $validator = Validator::make($txData, [
                    'numbers' => [new NumbersMatchGameType($txData['game_type'])],
                ]);

                if ($validator->fails()) {
                    $results['transactions']['errors'][] = [
                        'index' => $index,
                        'transaction_id' => $txData['transaction_id'],
                        'error' => $validator->errors()->first(),
                    ];
                    continue;
                }

                Transaction::create([
                    'transaction_id' => $txData['transaction_id'],
                    'device_id' => $device->id,
                    'local_user_id' => $txData['local_user_id'] ?? null,
                    'game_type' => $txData['game_type'],
                    'numbers' => $txData['numbers'],
                    'amount' => $txData['amount'],
                    'draw_time' => $txData['draw_time'],
                    'draw_date' => $txData['draw_date'],
                    'local_created_at' => $txData['local_created_at'],
                ]);
                $results['transactions']['synced']++;
            }

            DB::commit();

            $syncStatus = empty($results['transactions']['errors']) ? 'success' : 'partial';

            SyncLog::create([
                'device_id' => $device->id,
                'sync_type' => 'batch',
                'records_synced' => $results['transactions']['synced'] + $results['users']['synced'],
                'status' => $syncStatus,
                'error_message' => !empty($results['transactions']['errors'])
                    ? json_encode($results['transactions']['errors'])
                    : null,
                'ip_address' => $request->ip(),
            ]);

            return response()->json([
                'message' => 'Batch sync completed',
                'results' => $results,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            SyncLog::create([
                'device_id' => $device->id,
                'sync_type' => 'batch',
                'records_synced' => 0,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'ip_address' => $request->ip(),
            ]);

            return response()->json([
                'message' => 'Batch sync failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function pull(Request $request): JsonResponse
    {
        $device = $request->get('device');

        $request->validate([
            'since' => 'nullable|date',
            'draw_date' => 'nullable|date',
        ]);

        $query = DrawResult::query();

        if ($request->has('since')) {
            $query->where('created_at', '>=', $request->since);
        }

        if ($request->has('draw_date')) {
            $query->where('draw_date', $request->draw_date);
        }

        $drawResults = $query->orderBy('draw_date', 'desc')
            ->orderBy('draw_time', 'desc')
            ->get();

        // Get updated transaction statuses for this device
        $transactions = Transaction::where('device_id', $device->id)
            ->whereIn('status', ['won', 'lost', 'claimed'])
            ->when($request->has('since'), function ($q) use ($request) {
                $q->where('updated_at', '>=', $request->since);
            })
            ->get(['transaction_id', 'status', 'win_amount', 'claimed_at']);

        SyncLog::create([
            'device_id' => $device->id,
            'sync_type' => 'pull',
            'records_synced' => $drawResults->count() + $transactions->count(),
            'status' => 'success',
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'draw_results' => $drawResults,
            'transaction_updates' => $transactions,
            'server_time' => now()->toIso8601String(),
        ]);
    }
}
