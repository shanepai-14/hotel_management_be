<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Billing;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Carbon\Carbon;



class BookingController extends Controller
{
    public function index()
    {
        $bookings = Booking::with(['guest', 'room'])->get();
        return response()->json($bookings);
    }

    public function show(Booking $booking)
    {
        return response()->json([
            'data' => $booking->load(['guest', 'room', 'billing'])
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'guest_id' => 'required|exists:guests,id',
            'check_in' => 'required|date',
            'check_out' => 'required|date|after:check_in',
            'number_of_guests' => 'required|integer|min:1',
            'notes' => 'nullable|string',
            'billing' => 'required|array',
            'billing.room_charges' => 'required|numeric',
            'billing.tax_amount' => 'required|numeric',
            'billing.total_amount' => 'required|numeric',
            'billing.payment_method' => 'required|in:cash,card,transfer',
            'billing.payment_status' => 'required|in:paid,pending'
        ]);
    
        DB::beginTransaction();
        try {
            // Create booking
            $booking = Booking::create([
                'room_id' => $validated['room_id'],
                'guest_id' => $validated['guest_id'],
                'check_in' => $validated['check_in'],
                'check_out' => $validated['check_out'],
                'number_of_guests' => $validated['number_of_guests'],
                'total_price' => $validated['billing']['total_amount'],
                'status' => 'checked_in',
                'notes' => $validated['notes']
            ]);
    
            // Create billing
            $billing = Billing::create([
                'booking_id' => $booking->id,
                'invoice_number' => 'INV-' . date('Y') . str_pad($booking->id, 6, '0', STR_PAD_LEFT),
                'room_charges' => $validated['billing']['room_charges'],
                'tax_amount' => $validated['billing']['tax_amount'],
                'total_amount' => $validated['billing']['total_amount'],
                'payment_status' => $validated['billing']['payment_status'],
                'payment_method' => $validated['billing']['payment_method']
            ]);
    
            // Update room status
            $room = Room::findOrFail($validated['room_id']);
            $room->update(['status' => 'occupied']);
    
            DB::commit();
    
            return response()->json([
                'success' => true,
                'booking' => $booking->load(['guest', 'room']),
                'billing' => $billing
            ], 201);
    
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    // public function update(Request $request, Booking $booking)
    // {
    //     $validated = $request->validate([
    //         'status' => ['required', Rule::in(['confirmed', 'checked_in', 'checked_out', 'cancelled'])],
    //         'room_id' => 'sometimes|exists:rooms,id',
    //         'guest_id' => 'sometimes|exists:guests,id',
    //         'check_in' => 'sometimes|date',
    //         'check_out' => 'sometimes|date|after:check_in',
    //         'number_of_guests' => 'sometimes|integer|min:1',
    //         'notes' => 'nullable|string'
    //     ]);

    //     DB::beginTransaction();
    //     try {
    //         $oldStatus = $booking->status;
    //         $newStatus = $validated['status'];

    //         // Update booking
    //         $booking->update($validated);

    //         // Handle room status changes based on booking status
    //         if ($oldStatus !== $newStatus) {
    //             $room = Room::findOrFail($booking->room_id);

    //             switch ($newStatus) {
    //                 case 'checked_out':
    //                     $room->update(['status' => 'available']);
    //                     break;
    //                 case 'cancelled':
    //                     if (in_array($oldStatus, ['confirmed', 'checked_in'])) {
    //                         $room->update(['status' => 'available']);
    //                     }
    //                     break;
    //                 case 'confirmed':
    //                 case 'checked_in':
    //                     $room->update(['status' => 'occupied']);
    //                     break;
    //             }
    //         }

    //         // If dates are being updated, check for conflicts
    //         if (isset($validated['check_in']) || isset($validated['check_out'])) {
    //             $conflicts = Booking::where('room_id', $booking->room_id)
    //                 ->where('id', '!=', $booking->id)
    //                 ->where('status', '!=', 'cancelled')
    //                 ->where(function ($query) use ($booking) {
    //                     $query->whereBetween('check_in', [$booking->check_in, $booking->check_out])
    //                         ->orWhereBetween('check_out', [$booking->check_in, $booking->check_out]);
    //                 })->exists();

    //             if ($conflicts) {
    //                 throw new \Exception('Room is not available for the selected dates');
    //             }
    //         }

    //         DB::commit();

    //         return response()->json([
    //             'data' => $booking->load(['guest', 'room', 'billing'])
    //         ]);

    //     } catch (\Exception $e) {
    //         DB::rollback();
    //         return response()->json([
    //             'message' => $e->getMessage()
    //         ], 400);
    //     }
    // }
    
    public function update(Request $request, Booking $booking)
{
    $validated = $request->validate([
        'status' => ['required', Rule::in(['confirmed', 'checked_in', 'checked_out', 'cancelled'])],
        'room_id' => 'sometimes|exists:rooms,id',
        'guest_id' => 'sometimes|exists:guests,id',
        'check_in' => 'sometimes|date',
        'check_out' => 'sometimes|date|after:check_in',
        'number_of_guests' => 'sometimes|integer|min:1',
        'notes' => 'nullable|string'
    ]);

    DB::beginTransaction();
    try {
        $oldStatus = $booking->status;
        $newStatus = $validated['status'];

        // Update booking
        $booking->update($validated);

        // Handle room status changes based on booking status
        if ($oldStatus !== $newStatus) {
            $room = Room::findOrFail($booking->room_id);

            switch ($newStatus) {
                case 'checked_out':
                    $room->update(['status' => 'available']);
                    
                    // Create billing if it doesn't exist
                    if (!$booking->billing) {
                        // Calculate number of days
                        $checkIn = Carbon::parse($booking->check_in);
                        $checkOut = Carbon::parse($booking->check_out);
                        $days = $checkOut->diffInDays($checkIn);
                        
                        // Calculate charges
                        $roomCharges = $room->price_per_night * $days;
                        $taxAmount = $roomCharges * 0.1; // 10% tax
                        $totalAmount = $roomCharges + $taxAmount;

                        // Create billing record
                        $booking->billing()->create([
                            'invoice_number' => 'INV-' . date('Y') . str_pad($booking->id, 6, '0', STR_PAD_LEFT),
                            'room_charges' => $roomCharges,
                            'tax_amount' => $taxAmount,
                            'total_amount' => $totalAmount,
                            'payment_status' => 'paid',
                            'payment_method' => 'cash'
                        ]);
                    }
                    break;
                case 'cancelled':
                    if (in_array($oldStatus, ['confirmed', 'checked_in'])) {
                        $room->update(['status' => 'available']);
                    }
                    break;
                case 'confirmed':
                case 'checked_in':
                    $room->update(['status' => 'occupied']);
                    break;
            }
        }

        DB::commit();

        // Load the relationships including billing
        $booking->load(['guest', 'room', 'billing']);

        return response()->json([
            'data' => $booking
        ]);

    } catch (\Exception $e) {
        DB::rollback();
        return response()->json([
            'message' => $e->getMessage()
        ], 400);
    }
}
    public function destroy(Booking $booking)
    {
        DB::beginTransaction();
        try {
            // Can only delete if not checked out
            if ($booking->status === 'checked_out') {
                throw new \Exception('Cannot delete checked-out bookings');
            }

            // If the booking was confirmed or checked in, free up the room
            if (in_array($booking->status, ['confirmed', 'checked_in'])) {
                $room = Room::findOrFail($booking->room_id);
                $room->update(['status' => 'available']);
            }

            // Delete associated billing first (if exists)
            if ($booking->billing) {
                $booking->billing->delete();
            }

            // Delete the booking
            $booking->delete();

            DB::commit();

            return response()->json(null, 204);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    private function checkRoomAvailability($roomId, $checkIn, $checkOut)
    {
        return !Booking::where('room_id', $roomId)
            ->where('status', '!=', 'cancelled')
            ->where(function ($query) use ($checkIn, $checkOut) {
                $query->whereBetween('check_in', [$checkIn, $checkOut])
                    ->orWhereBetween('check_out', [$checkIn, $checkOut])
                    ->orWhere(function ($q) use ($checkIn, $checkOut) {
                        $q->where('check_in', '<=', $checkIn)
                            ->where('check_out', '>=', $checkOut);
                    });
            })->exists();
    }

    // Add other booking controller methods...
}