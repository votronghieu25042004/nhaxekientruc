<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Trip;
use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class StaffController extends Controller
{
    public function manifest(Request $request)
    {
        $tripId = $request->input('trip_id');
        $todayTrips = Trip::with(['route', 'bus', 'driver'])->whereDate('departure_time', Carbon::today())->get();

        $selectedTrip = null;
        $bookings = [];
        $seatsFloor1 = [];
        $seatsFloor2 = [];

        if ($tripId) {
            $selectedTrip = Trip::with(['route', 'bus', 'driver', 'bookings.bookingSeats', 'seats'])->findOrFail($tripId);
        } elseif ($todayTrips->count() > 0) {
            $selectedTrip = Trip::with(['route', 'bus', 'driver', 'bookings.bookingSeats', 'seats'])->find($todayTrips->first()->id);
        }

        if ($selectedTrip) {
            $bookings = $selectedTrip->bookings->map(function ($b) {
                return [
                    'id' => $b->id,
                    'booking_code' => $b->booking_code,
                    'customer_name' => $b->customer_name,
                    'customer_phone' => $b->customer_phone,
                    'seats' => $b->bookingSeats->pluck('seat_number')->toArray(),
                    'total_amount' => (float) $b->total_amount,
                    'deposit_amount' => (float) $b->deposit_amount,
                    'remaining_amount' => (float) $b->remaining_amount,
                    'checkin_status' => $b->checkin_status,
                    'checked_in_at' => $b->checked_in_at ? $b->checked_in_at->format('H:i') : null,
                    'notes' => $b->notes,
                ];
            });

            $seatsFloor1 = $selectedTrip->seats->where('floor', 1)->values();
            $seatsFloor2 = $selectedTrip->seats->where('floor', 2)->values();
        }

        return Inertia::render('Staff/Dashboard', [
            'todayTrips' => $todayTrips,
            'selectedTrip' => $selectedTrip ? [
                'id' => $selectedTrip->id,
                'route_name' => $selectedTrip->route->departure_location . ' ➔ ' . $selectedTrip->route->arrival_location,
                'departure_time' => $selectedTrip->departure_time->format('H:i - d/m/Y'),
                'bus_plate' => $selectedTrip->bus->license_plate,
                'bus_type' => $selectedTrip->bus->bus_type,
                'driver_name' => $selectedTrip->driver ? $selectedTrip->driver->name : 'Đang phân công',
                'driver_phone' => $selectedTrip->driver ? $selectedTrip->driver->phone : null,
            ] : null,
            'bookings' => $bookings,
            'seatsFloor1' => $seatsFloor1,
            'seatsFloor2' => $seatsFloor2,
            'user' => Auth::user(),
        ]);
    }

    public function manualCheckin(Request $request, $bookingId)
    {
        $booking = Booking::findOrFail($bookingId);

        if ($booking->checkin_status === 'boarded') {
            return back()->with('error', 'Vé này đã được check-in trước đó!');
        }

        $booking->update([
            'payment_status' => 'fully_paid',
            'checkin_status' => 'boarded',
            'checked_in_at' => Carbon::now(),
            'checked_in_by' => Auth::user() ? Auth::user()->name : 'Lơ xe trực tiếp',
        ]);

        return back()->with('success', "Đã xác nhận thu {$booking->remaining_amount}đ và check-in cho khách {$booking->customer_name} thành công!");
    }
}
