<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Guest;

class GuestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $guests = Guest::all();
        return response()->json($guests);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:guests',
            'phone_number' => 'required|string|max:20',
            'address' => 'nullable|string',
            'city' => 'nullable|string',
            'country' => 'nullable|string',
            'identification_type' => 'nullable|string',
            'identification_number' => 'nullable|string',
            'date_of_birth' => 'nullable|date',
            'special_requests' => 'nullable|string'
        ]);

        $guest = Guest::create($validated);
        return response()->json($guest, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Guest $guest)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:guests,email,' . $guest->id,
            'phone_number' => 'required|string|max:20',
            'address' => 'nullable|string',
            'city' => 'nullable|string',
            'country' => 'nullable|string',
            'identification_type' => 'nullable|string',
            'identification_number' => 'nullable|string',
            'date_of_birth' => 'nullable|date',
            'special_requests' => 'nullable|string'
        ]);

        $guest->update($validated);
        return response()->json($guest);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Guest $guest)
    {
        $guest->delete();
        return response()->json(null, 204);
    }
}
