<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Booking;
use App\Models\Review;
use App\Models\Driver;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    public function show($bookingCode)
    {
        $booking = Booking::with(['trip.route', 'trip.bus', 'trip.driver', 'review'])
            ->where('booking_code', $bookingCode)
            ->firstOrFail();

        return Inertia::render('Reviews/Create', [
            'booking' => [
                'id' => $booking->id,
                'booking_code' => $booking->booking_code,
                'customer_name' => $booking->customer_name,
                'departure_time' => $booking->trip->departure_time->format('H:i - d/m/Y'),
                'from_location' => $booking->trip->route->departure_location ?? 'Đà Nẵng',
                'to_location' => $booking->trip->route->arrival_location ?? 'Hội An',
                'bus_type' => $booking->trip->bus->bus_type ?? 'Giường Nằm Cao Cấp',
                'license_plate' => $booking->trip->bus->plate_number ?? $booking->trip->bus->license_plate ?? '43B-012.34',
                'driver' => $booking->trip->driver ? [
                    'id' => $booking->trip->driver->id,
                    'name' => $booking->trip->driver->name,
                    'avatar' => $booking->trip->driver->avatar,
                    'experience_years' => $booking->trip->driver->experience_years ?? 8,
                    'avg_rating' => (float) ($booking->trip->driver->avg_rating ?? 5.0),
                ] : null,
                'review' => $booking->review,
            ]
        ]);
    }

    public function store(Request $request, $bookingCode)
    {
        $booking = Booking::with('trip.driver')->where('booking_code', $bookingCode)->firstOrFail();

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'safety_rating' => 'required|integer|min:1|max:5',
            'service_rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
            'is_anonymous' => 'nullable|boolean',
        ]);

        $isAnonymous = $request->boolean('is_anonymous');
        $reviewerName = $isAnonymous ? 'Hành khách ẩn danh' : ($booking->customer_name ?? 'Hành khách FUTA');

        $review = Review::updateOrCreate(
            ['booking_id' => $booking->id],
            [
                'user_id' => Auth::id() ?? $booking->user_id,
                'driver_id' => $booking->trip->driver_id,
                'rating' => $validated['rating'],
                'safety_rating' => $validated['safety_rating'],
                'service_rating' => $validated['service_rating'],
                'comment' => $validated['comment'],
                'is_anonymous' => $isAnonymous ? 1 : 0,
                'reviewer_name' => $reviewerName,
            ]
        );

        if ($booking->trip->driver) {
            $booking->trip->driver->updateAverageRating();
        }

        return redirect()->route('booking.ticket', ['code' => $booking->booking_code])
            ->with('success', 'Cảm ơn bạn đã gửi đánh giá cho Tài xế và Chuyến xe FUTA Bus Lines!');
    }
}
