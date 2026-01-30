<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\DeviceUser;
use App\Models\DrawResult;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncController extends Controller
{
    public function push(Request $request): JsonResponse
    {
        $request->validate([
            'device_id' => 'required|string',
            'device_name' => 'nullable|string|max:100',
            'timestamp' => 'required|string',
            'changes' => 'required|array',
        ]);

        $device = $request->device;

        // Update device name if provided
        if ($request->device_name && $request->device_name !== $device->device_name) {
            $device->update(['device_name' => $request->device_name]);
        }

        $processed = [
            'transactions' => [],
            'draw_results' => [],
            'users' => [],
        ];

        try {
            DB::beginTransaction();

            // Process transactions
            if (!empty($request->changes['transactions'])) {
                foreach ($request->changes['transactions'] as $item) {
                    $result = $this->processTransaction($device, $item);
                    $processed['transactions'][] = $result;
                }
            }

            // Process draw results
            if (!empty($request->changes['draw_results'])) {
                foreach ($request->changes['draw_results'] as $item) {
                    $result = $this->processDrawResult($device, $item);
                    $processed['draw_results'][] = $result;
                }
            }

            // Process users
            if (!empty($request->changes['users'])) {
                foreach ($request->changes['users'] as $item) {
                    $result = $this->processUser($device, $item);
                    $processed['users'][] = $result;
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'processed' => $processed,
                'server_time' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Sync push failed', [
                'device_id' => $device->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Sync failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function processTransaction(Device $device, array $item): array
    {
        $localId = $item['local_id'];
        $operation = $item['operation'];
        $data = $item['data'];

        $existing = Transaction::where('device_id', $device->id)
            ->where('local_id', $localId)
            ->first();

        if ($operation === 'INSERT' && !$existing) {
            $transaction = Transaction::create([
                'device_id' => $device->id,
                'local_id' => $localId,
                'transaction_id' => $data['transaction_id'],
                'user_id' => $data['user_id'],
                'amount' => $data['amount'],
                'numbers' => $data['numbers'],
                'game_type' => $data['game_type'],
                'draw_date' => $data['draw_date'],
                'draw_time' => $data['draw_time'],
                'payment_method' => $data['payment_method'] ?? null,
                'verified' => $data['verified'] ?? false,
                'device_created_at' => $data['created_at'] ?? null,
            ]);

            return [
                'local_id' => $localId,
                'server_id' => $transaction->id,
                'status' => 'created',
            ];
        }

        if ($existing) {
            // Update existing record
            $existing->update([
                'verified' => $data['verified'] ?? $existing->verified,
            ]);

            return [
                'local_id' => $localId,
                'server_id' => $existing->id,
                'status' => 'updated',
            ];
        }

        return [
            'local_id' => $localId,
            'server_id' => null,
            'status' => 'skipped',
        ];
    }

    private function processDrawResult(Device $device, array $item): array
    {
        $localId = $item['local_id'];
        $operation = $item['operation'];
        $data = $item['data'];

        $existing = DrawResult::where('device_id', $device->id)
            ->where('local_id', $localId)
            ->first();

        if ($operation === 'INSERT' && !$existing) {
            $result = DrawResult::create([
                'device_id' => $device->id,
                'local_id' => $localId,
                'draw_date' => $data['draw_date'],
                'draw_time' => $data['draw_time'],
                'game_type' => $data['game_type'],
                'winning_numbers' => $data['winning_numbers'],
                'device_created_at' => $data['created_at'] ?? null,
            ]);

            return [
                'local_id' => $localId,
                'server_id' => $result->id,
                'status' => 'created',
            ];
        }

        if ($existing) {
            return [
                'local_id' => $localId,
                'server_id' => $existing->id,
                'status' => 'exists',
            ];
        }

        return [
            'local_id' => $localId,
            'server_id' => null,
            'status' => 'skipped',
        ];
    }

    private function processUser(Device $device, array $item): array
    {
        $localId = $item['local_id'];
        $operation = $item['operation'];
        $data = $item['data'];

        $existing = DeviceUser::where('device_id', $device->id)
            ->where('local_id', $localId)
            ->first();

        if ($operation === 'INSERT' && !$existing) {
            $user = DeviceUser::create([
                'device_id' => $device->id,
                'local_id' => $localId,
                'username' => $data['username'],
                'role' => $data['role'],
                'name' => $data['name'] ?? null,
                'device_created_at' => $data['created_at'] ?? null,
            ]);

            return [
                'local_id' => $localId,
                'server_id' => $user->id,
                'status' => 'created',
            ];
        }

        if ($existing) {
            return [
                'local_id' => $localId,
                'server_id' => $existing->id,
                'status' => 'exists',
            ];
        }

        return [
            'local_id' => $localId,
            'server_id' => null,
            'status' => 'skipped',
        ];
    }

    public function pull(Request $request): JsonResponse
    {
        $device = $request->device;
        $since = $request->query('since');

        // Get official (admin-created) draw results
        // These take priority over any local device results
        $drawResultsQuery = DrawResult::whereNull('device_id');

        if ($since) {
            $drawResultsQuery->where('updated_at', '>', $since);
        }

        $drawResults = $drawResultsQuery
            ->orderBy('draw_date', 'desc')
            ->orderBy('draw_time', 'asc')
            ->limit(100) // Limit to prevent large payloads
            ->get()
            ->map(function ($result) {
                return [
                    'server_id' => $result->id,
                    'draw_date' => $result->draw_date->format('Y-m-d'),
                    'draw_time' => $result->draw_time,
                    'game_type' => $result->game_type,
                    'winning_numbers' => $result->winning_numbers,
                    'is_official' => true, // Flag to indicate this is from server
                    'created_at' => $result->created_at->toIso8601String(),
                    'updated_at' => $result->updated_at->toIso8601String(),
                ];
            });

        return response()->json([
            'success' => true,
            'changes' => [
                'draw_results' => $drawResults,
            ],
            'server_time' => now()->toIso8601String(),
        ]);
    }

    public function status(Request $request): JsonResponse
    {
        $device = $request->device;

        return response()->json([
            'success' => true,
            'device_registered' => true,
            'device_name' => $device->device_name,
            'device_id' => $device->device_id,
            'is_active' => $device->is_active,
            'last_sync' => $device->last_seen_at,
            'server_time' => now()->toIso8601String(),
        ]);
    }

    public function full(Request $request): JsonResponse
    {
        // Push first, then pull
        $pushResponse = $this->push($request);
        $pullResponse = $this->pull($request);

        return response()->json([
            'success' => true,
            'push' => json_decode($pushResponse->getContent(), true),
            'pull' => json_decode($pullResponse->getContent(), true),
        ]);
    }
}
