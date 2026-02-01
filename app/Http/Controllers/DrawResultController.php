<?php

namespace App\Http\Controllers;

use App\Models\DrawResult;
use App\Rules\NumbersMatchGameType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DrawResultController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = DrawResult::with('setBy:id,name');

        if ($request->has('draw_date')) {
            $query->where('draw_date', $request->draw_date);
        }

        if ($request->has('game_type')) {
            $query->where('game_type', $request->game_type);
        }

        if ($request->has('draw_time')) {
            $query->where('draw_time', $request->draw_time);
        }

        if ($request->has('date_from')) {
            $query->where('draw_date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('draw_date', '<=', $request->date_to);
        }

        $results = $query->orderBy('draw_date', 'desc')
            ->orderBy('draw_time', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json($results);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'draw_date' => 'required|date',
            'draw_time' => 'required|in:11AM,4PM,9PM',
            'game_type' => 'required|in:SWER2,SWER3,SWER4',
            'winning_numbers' => ['required', 'array', new NumbersMatchGameType($request->game_type)],
        ]);

        // Check if result already exists
        $exists = DrawResult::where('draw_date', $request->draw_date)
            ->where('draw_time', $request->draw_time)
            ->where('game_type', $request->game_type)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Draw result already exists for this combination',
            ], 422);
        }

        $drawResult = DrawResult::create([
            'draw_date' => $request->draw_date,
            'draw_time' => $request->draw_time,
            'game_type' => $request->game_type,
            'winning_numbers' => $request->winning_numbers,
            'set_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Draw result created successfully',
            'draw_result' => $drawResult->load('setBy:id,name'),
        ], 201);
    }

    public function show(DrawResult $drawResult): JsonResponse
    {
        return response()->json($drawResult->load('setBy:id,name'));
    }

    public function update(Request $request, DrawResult $drawResult): JsonResponse
    {
        $request->validate([
            'winning_numbers' => ['required', 'array', new NumbersMatchGameType($drawResult->game_type)],
        ]);

        $drawResult->update([
            'winning_numbers' => $request->winning_numbers,
            'set_by' => $request->user()->id,
        ]);

        // Re-trigger the event to update transactions
        event(new \App\Events\DrawResultCreated($drawResult));

        return response()->json([
            'message' => 'Draw result updated successfully',
            'draw_result' => $drawResult->fresh()->load('setBy:id,name'),
        ]);
    }

    public function destroy(DrawResult $drawResult): JsonResponse
    {
        $drawResult->delete();

        return response()->json([
            'message' => 'Draw result deleted successfully',
        ]);
    }

    public function today(): JsonResponse
    {
        $results = DrawResult::where('draw_date', today())
            ->orderBy('draw_time')
            ->get();

        return response()->json([
            'date' => today()->toDateString(),
            'results' => $results,
        ]);
    }
}
