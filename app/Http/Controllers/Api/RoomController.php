<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoomController extends Controller
{
    public function index()
    {
        $rooms = Room::all();
        return response()->json($rooms);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'room_number' => 'required|unique:rooms',
            'type' => ['required', Rule::in(['standard', 'deluxe', 'suite'])],
            'capacity' => 'required|integer|min:1',
            'price_per_night' => 'required|numeric|min:0',
            'status' => ['required', Rule::in(['available', 'occupied', 'maintenance'])],
            'description' => 'nullable|string',
            'amenities' => 'nullable|array'
        ]);

        $room = Room::create($validated);
        return response()->json($room, 201);
    }

    public function show(Room $room)
    {
        return response()->json($room);
    }

    public function update(Request $request, Room $room)
    {
        $validated = $request->validate([
            'room_number' => ['required', Rule::unique('rooms')->ignore($room->id)],
            'type' => ['required', Rule::in(['standard', 'deluxe', 'suite'])],
            'capacity' => 'required|integer|min:1',
            'price_per_night' => 'required|numeric|min:0',
            'status' => ['required', Rule::in(['available', 'occupied', 'maintenance'])],
            'description' => 'nullable|string',
            'amenities' => 'nullable|array'
        ]);

        $room->update($validated);
        return response()->json($room);
    }

    public function destroy(Room $room)
    {
        $room->delete();
        return response()->json(null, 204);
    }
}