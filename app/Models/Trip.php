<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Trip extends Model {
    protected $guarded = [];
    protected $casts = ['departure_time' => 'datetime', 'arrival_time' => 'datetime'];
    protected $appends = ['price'];

    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function getPriceAttribute() {
        return $this->base_price ?? $this->attributes['base_price'] ?? 140000;
    }

    public function route() { return $this->belongsTo(Route::class); }
    public function bus() { return $this->belongsTo(Bus::class); }
    public function driver() { return $this->belongsTo(Driver::class); }
    public function secondDriver() { return $this->belongsTo(Driver::class, 'second_driver_id'); }
    public function conductor() { return $this->belongsTo(User::class, 'conductor_id'); }
    public function secondConductor() { return $this->belongsTo(User::class, 'second_conductor_id'); }
    public function bookings() { return $this->hasMany(Booking::class); }
    public function bookingSeats() { return $this->hasMany(BookingSeat::class); }
    public function expenses() { return $this->hasMany(TripExpense::class); }
    public function closing() { return $this->hasOne(TripClosing::class); }
    public function incidents() { return $this->hasMany(Incident::class); }
    public function lostFounds() { return $this->hasMany(LostFound::class); }
    public function reviews() { return $this->hasMany(Review::class); }
    /**
     * Đồng bộ trạng thái chuyến xe theo thời gian thực (Real-time Clock Sync)
     */
    public static function syncRealTimeStatuses()
    {
        $now = \Carbon\Carbon::now('Asia/Ho_Chi_Minh');

        // 1. Chuyến đã qua giờ đến => COMPLETED
        self::where('arrival_time', '<=', $now)
            ->whereNotIn('status', ['COMPLETED', 'CANCELLED', 'CLOSED'])
            ->update(['status' => 'COMPLETED']);

        // 2. Chuyến đang trong giờ chạy (departure <= now < arrival) => IN_TRANSIT
        self::where('departure_time', '<=', $now)
            ->where('arrival_time', '>', $now)
            ->whereNotIn('status', ['IN_TRANSIT', 'REST_STOP', 'CANCELLED', 'CLOSED'])
            ->update(['status' => 'IN_TRANSIT']);

        // 3. Chuyến sắp chạy trong vòng 45 phút => BOARDING
        self::where('departure_time', '>', $now)
            ->where('departure_time', '<=', $now->copy()->addMinutes(45))
            ->whereNotIn('status', ['BOARDING', 'REST_STOP', 'CANCELLED', 'CLOSED'])
            ->update(['status' => 'BOARDING']);

        // 4. Chuyến trong ngày nhưng còn hơn 45 phút mới chạy => OPEN_FOR_SALE
        self::where('departure_time', '>', $now->copy()->addMinutes(45))
            ->whereDate('departure_time', $now->toDateString())
            ->whereIn('status', ['IN_TRANSIT', 'BOARDING'])
            ->update(['status' => 'OPEN_FOR_SALE']);

        // 5. Tự động check-in cho chuyến xe ĐÃ HOÀN THÀNH (100% lên xe, ngoại trừ vé đã hủy)
        \App\Models\Booking::whereHas('trip', function($q) {
            $q->whereIn('status', ['COMPLETED', 'CLOSED']);
        })
        ->where('payment_status', '!=', 'REFUNDED')
        ->where('checkin_status', '!=', 'CHECKED_IN')
        ->update([
            'checkin_status' => 'CHECKED_IN',
            'checkin_method' => 'QR_AUTO',
            'payment_status' => 'PAID',
        ]);

        // 6. Tự động check-in cho chuyến xe ĐANG CHẠY (IN_TRANSIT):
        // 85-90% hành khách tự động lên xe, chỉ để lại 1-2 trường hợp thực tế (đón ở trạm tiếp theo hoặc cọc 30%)
        $inTransitTrips = self::where('status', 'IN_TRANSIT')->get();
        foreach ($inTransitTrips as $itTrip) {
            $itBookings = $itTrip->bookings()->where('payment_status', '!=', 'REFUNDED')->get();
            $total = $itBookings->count();
            if ($total > 0) {
                $boardCount = max(1, $total - 2);
                foreach ($itBookings->take($boardCount) as $bk) {
                    if ($bk->checkin_status !== 'CHECKED_IN') {
                        $bk->checkin_status = 'CHECKED_IN';
                        $bk->checkin_method = 'QR_AUTO';
                        $bk->checked_in_at = $itTrip->departure_time;
                        $bk->save();
                    }
                }
            }
        }

        // 7. Tự động cộng điểm thưởng cho các chuyến xe đã hoàn thành & đã thanh toán
        self::awardLoyaltyPointsForCompletedTrips();
    }

    /**
     * Tự động tích lũy điểm thưởng thành viên khi chuyến xe hoàn thành an toàn & vé đã thanh toán
     */
    public static function awardLoyaltyPointsForCompletedTrips()
    {
        $completedTripIds = self::whereIn('status', ['COMPLETED', 'CLOSED'])->pluck('id');
        if ($completedTripIds->isEmpty()) {
            return;
        }

        $pendingBookings = Booking::whereIn('trip_id', $completedTripIds)
            ->where('payment_status', 'PAID')
            ->where(function ($q) {
                $q->whereNull('points_awarded')->orWhere('points_awarded', 0);
            })
            ->get();

        foreach ($pendingBookings as $b) {
            $earned = $b->points_earned ?: (int)($b->paid_amount / 1000);
            if ($earned <= 0) {
                $earned = (int)($b->total_amount / 1000);
            }
            $b->points_earned = $earned;
            $b->points_awarded = 1;
            $b->save();

            // Tìm tài khoản User theo user_id hoặc số điện thoại
            $user = null;
            if ($b->user_id) {
                $user = User::find($b->user_id);
            }
            if (!$user && $b->customer_phone) {
                $user = User::where('phone', $b->customer_phone)->first();
            }

            if ($user) {
                // Tổng chi tiêu thực tế của khách hàng
                $totalSpent = (float)$user->bookings()->where('payment_status', '!=', 'REFUNDED')->sum('total_amount');
                $maxPoints = (int)($totalSpent / 1000);

                // Điểm tích lũy chỉ có thể bằng hoặc ít hơn tổng chi tiêu (points <= total_spent / 1000)
                $user->reward_points = min(($user->reward_points ?? 0) + $earned, $maxPoints);
                if ($user->reward_points >= 5000) {
                    $user->loyalty_tier = 'DIAMOND';
                } elseif ($user->reward_points >= 2000) {
                    $user->loyalty_tier = 'GOLD';
                } elseif ($user->reward_points >= 500) {
                    $user->loyalty_tier = 'SILVER';
                }
                $user->save();
            }
        }
    }

    /**
     * Lấy danh sách ghi chú đặc biệt từ khách hàng và điều hành
     * Chỉ trả về khi có ghi chú thực tế (không lấy note mặc định)
     */
    public function getCustomerNotesList()
    {
        $notesList = [];

        // 1. Ghi chú từ hành khách
        $bookings = $this->relationLoaded('bookings') 
            ? $this->bookings 
            : $this->bookings()->with('bookingSeats')->get();

        $activeBookings = $bookings->whereNotIn('payment_status', ['CANCELLED', 'REFUNDED'])
            ->whereNotIn('checkin_status', ['CANCELLED']);

        foreach ($activeBookings as $b) {
            $rawNote = trim($b->notes ?? '');
            if (
                empty($rawNote) ||
                $rawNote === 'Đặt vé trực tuyến qua nhaxekientruc' ||
                str_starts_with($rawNote, 'Tự động tạo') ||
                strtolower($rawNote) === 'không' ||
                strtolower($rawNote) === 'none'
            ) {
                continue;
            }

            // Lấy danh sách số ghế
            $seats = [];
            if ($b->relationLoaded('bookingSeats')) {
                $seats = $b->bookingSeats->pluck('seat_number')->toArray();
            } else {
                $seats = $b->bookingSeats()->pluck('seat_number')->toArray();
            }
            $seatLabel = !empty($seats) ? implode(', ', $seats) : ($b->seat_number ?: 'Chưa chọn ghế');

            $notesList[] = [
                'type' => 'passenger',
                'customer_name' => $b->customer_name ?: 'Khách hàng',
                'customer_phone' => $b->customer_phone ?: '',
                'booking_code' => $b->booking_code ?: '',
                'seat_label' => $seatLabel,
                'seats' => $seats,
                'note' => $rawNote,
            ];
        }

        // 2. Ghi chú điều hành / chuyến đi nếu có
        if (!empty($this->passenger_notes) && trim($this->passenger_notes) !== '') {
            $notesList[] = [
                'type' => 'trip',
                'customer_name' => 'Ban Điều Hành',
                'customer_phone' => '',
                'booking_code' => '',
                'seat_label' => 'Toàn chuyến',
                'seats' => [],
                'note' => trim($this->passenger_notes),
            ];
        }

        return $notesList;
    }

    /**
     * Tính toán số liệu thống kê chuẩn xác đồng bộ:
     * - Số ghế đã đặt (booked_seats_count / occupied_count)
     * - Số khách / đơn đặt (customer_count / booking_count)
     * - Số ghế đã lên xe (boarded_seats_count / boarded_count)
     * - Số khách đã lên xe (boarded_passengers_count)
     */
    public function calculateAccurateStats()
    {
        $bookings = $this->relationLoaded('bookings') 
            ? $this->bookings 
            : $this->bookings()->with('bookingSeats')->get();

        $activeBookings = $bookings->whereNotIn('payment_status', ['CANCELLED', 'REFUNDED'])
            ->whereNotIn('checkin_status', ['CANCELLED']);

        // 1. Số ghế đã đặt
        $bookedSeats = $activeBookings->flatMap(function($b) {
            return $b->relationLoaded('bookingSeats') ? $b->bookingSeats : $b->bookingSeats()->get();
        });
        $bookedSeatsCount = $bookedSeats->count();
        if ($bookedSeatsCount === 0 && $activeBookings->count() > 0) {
            $bookedSeatsCount = (int)($activeBookings->sum('total_seats') ?: $activeBookings->count());
        }

        // 2. Số khách / đơn đặt
        $customerCount = $activeBookings->count();

        // 3. Số ghế và khách đã lên xe (CHECKED_IN)
        $boardedBookings = $activeBookings->where('checkin_status', 'CHECKED_IN');
        $boardedSeatsCount = $boardedBookings->flatMap(function($b) {
            return $b->relationLoaded('bookingSeats') ? $b->bookingSeats : $b->bookingSeats()->get();
        })->count();
        if ($boardedSeatsCount === 0 && $boardedBookings->count() > 0) {
            $boardedSeatsCount = (int)($boardedBookings->sum('total_seats') ?: $boardedBookings->count());
        }
        $boardedPaxCount = $boardedBookings->count();

        // 4. Chờ đón và Bỏ chuyến theo ghế
        $waitingSeatsCount = max(0, $bookedSeatsCount - $boardedSeatsCount);
        $noShowSeatsCount = $bookings->whereIn('checkin_status', ['NO_SHOW', 'CANCELLED'])->flatMap(function($b) {
            return $b->relationLoaded('bookingSeats') ? $b->bookingSeats : $b->bookingSeats()->get();
        })->count();

        // 5. Tổng số ghế của xe (Chuẩn 30 ghế: 18 Tầng 1 & 12 Tầng 2)
        $totalSeats = $this->bus ? ($this->bus->total_seats ?? 30) : 30;
        $availableSeats = max(0, $totalSeats - $bookedSeatsCount);

        // 6. Tầng 1 & Tầng 2
        $floor1Count = $bookedSeats->filter(fn($s) => str_starts_with($s->seat_number, 'A'))->count();
        $floor2Count = $bookedSeats->filter(fn($s) => str_starts_with($s->seat_number, 'B'))->count();

        $notesList = $this->getCustomerNotesList();
        $specialNotesText = !empty($notesList) 
            ? implode('; ', array_map(fn($item) => "[{$item['seat_label']}] {$item['customer_name']}: {$item['note']}", $notesList))
            : null;

        return [
            'booked_seats_count' => $bookedSeatsCount,
            'occupied_count' => $bookedSeatsCount,
            'customer_count' => $customerCount,
            'total_passengers' => $bookedSeatsCount,
            'booking_count' => $customerCount,
            'boarded_seats_count' => $boardedSeatsCount,
            'boarded_count' => $boardedSeatsCount,
            'boarded_passengers_count' => $boardedPaxCount,
            'waiting_seats_count' => $waitingSeatsCount,
            'waiting_count' => $waitingSeatsCount,
            'no_show_seats_count' => $noShowSeatsCount,
            'no_show_count' => $noShowSeatsCount,
            'total_seats' => $totalSeats,
            'available_seats' => $availableSeats,
            'floor1_count' => $floor1Count,
            'floor2_count' => $floor2Count,
            'occupied_percent' => $totalSeats > 0 ? round(($bookedSeatsCount / $totalSeats) * 100) : 0,
            'is_balanced' => abs($floor1Count - $floor2Count) <= 3,
            'customer_notes_list' => $notesList,
            'special_notes' => $specialNotesText,
        ];
    }
}
