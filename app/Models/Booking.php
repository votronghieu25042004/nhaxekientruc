<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model {
    protected $guarded = [];
    protected $casts = [
        'checked_in_at'       => 'datetime',
        'reminder_sent_at'    => 'datetime',
        'payment_confirmed_at'=> 'datetime',
        'ai_call_at'          => 'datetime',
    ];

    protected $appends = ['passenger_name', 'passenger_phone', 'ticket_code', 'total_price', 'total_seats', 'deposit_amount', 'remaining_amount', 'is_deposit'];

    public function getPassengerNameAttribute() {
        return $this->customer_name ?? $this->attributes['customer_name'] ?? 'Hành khách';
    }
    public function getPassengerPhoneAttribute() {
        return $this->customer_phone ?? $this->attributes['customer_phone'] ?? '0905.123.456';
    }
    public function getTicketCodeAttribute() {
        return $this->booking_code ?? $this->attributes['booking_code'] ?? 'KT-000000';
    }
    public function getTotalPriceAttribute() {
        return $this->total_amount ?? $this->attributes['total_amount'] ?? 140000;
    }
    public function getDepositAmountAttribute() {
        return (float)($this->paid_amount ?? 0);
    }
    public function getRemainingAmountAttribute() {
        return max(0, (float)($this->total_amount ?? 0) - (float)($this->paid_amount ?? 0));
    }
    public function getIsDepositAttribute() {
        return in_array($this->payment_status ?? '', ['DEPOSIT_PAID', 'PARTIALLY_PAID']) 
            || (($this->payment_method ?? '') === 'DEPOSIT_30')
            || str_contains($this->notes ?? '', 'Cọc trước 30%');
    }
    public function getTotalSeatsAttribute() {
        if (isset($this->attributes['total_seats']) && (int)$this->attributes['total_seats'] > 0) {
            return (int)$this->attributes['total_seats'];
        }
        if ($this->relationLoaded('bookingSeats') && $this->bookingSeats->isNotEmpty()) {
            return $this->bookingSeats->count();
        }
        if ($this->relationLoaded('seats') && $this->seats->isNotEmpty()) {
            return $this->seats->count();
        }
        return (int)($this->attributes['total_seats_count'] ?? 1);
    }

    public function user() { return $this->belongsTo(User::class); }
    public function trip() { return $this->belongsTo(Trip::class); }
    public function pickupStop() { return $this->belongsTo(RouteStop::class, 'pickup_stop_id'); }
    public function dropoffStop() { return $this->belongsTo(RouteStop::class, 'dropoff_stop_id'); }
    public function seats() { return $this->hasMany(BookingSeat::class); }
    public function bookingSeats() { return $this->hasMany(BookingSeat::class); }
    public function review() { return $this->hasOne(Review::class); }

    /**
     * Parse raw string from QR scan, URL, booking code, or phone number.
     * Returns: [$raw, $cleaned, $cleanPhone]
     */
    public static function parseSearchInput(?string $rawInput): array
    {
        if (!$rawInput) {
            return ['', '', ''];
        }

        $str = trim($rawInput);

        // 1. If it's a URL, extract path component (e.g. /ve/KT-XYZ, /booking/KT-XYZ, /ticket/KT-XYZ)
        if (preg_match('#(?:booking/detail/|booking/ticket/|booking/|ve/|ticket/)([A-Za-z0-9_-]+)#i', $str, $matches)) {
            $extracted = $matches[1];
        } elseif (filter_var($str, FILTER_VALIDATE_URL)) {
            $pathParts = explode('/', trim(parse_url($str, PHP_URL_PATH) ?? '', '/'));
            $extracted = end($pathParts) ?: $str;
        } else {
            $extracted = $str;
        }

        // Clean leading/trailing hashes, spaces, quotes
        $cleaned = trim($extracted, "# \t\n\r\0\x0B'\"");

        // Normalize phone number (strip dots, dashes, spaces)
        $cleanPhone = preg_replace('/[^\d+]/', '', $cleaned);
        if (str_starts_with($cleanPhone, '+84')) {
            $cleanPhone = '0' . substr($cleanPhone, 3);
        } elseif (str_starts_with($cleanPhone, '84') && strlen($cleanPhone) >= 11) {
            $cleanPhone = '0' . substr($cleanPhone, 2);
        }

        return [$str, $cleaned, $cleanPhone];
    }

    /**
     * Find booking by QR token, booking code, phone number, or ID with comprehensive matching and trip prioritization.
     */
    public static function findByQrOrCode(?string $rawInput, ?int $tripId = null, array $with = [])
    {
        [$raw, $cleaned, $cleanPhone] = self::parseSearchInput($rawInput);
        if (!$raw && !$cleaned) {
            return null;
        }

        $upperClean = strtoupper($cleaned);

        $query = self::query();
        if (!empty($with)) {
            $query->with($with);
        }

        $query->where(function ($q) use ($raw, $cleaned, $cleanPhone, $upperClean) {
            // 1. Raw or Cleaned QR Token
            $q->where('qr_token', $raw)
              ->orWhere('qr_token', $cleaned)
              ->orWhere('qr_token', 'QR-' . $cleaned)
              ->orWhere('qr_token', 'QR_' . $cleaned);

            // 2. Case-insensitive Booking Code (Handles PostgreSQL and MySQL)
            $q->orWhereRaw('UPPER(booking_code) = ?', [$upperClean])
              ->orWhereRaw('UPPER(booking_code) = ?', ['KT-' . $upperClean])
              ->orWhereRaw('UPPER(booking_code) = ?', ['BH-' . $upperClean])
              ->orWhereRaw('UPPER(booking_code) = ?', ['FUTA-' . $upperClean]);

            // 3. Customer Phone Number (9-12 digits)
            if (strlen($cleanPhone) >= 9 && strlen($cleanPhone) <= 12) {
                $q->orWhere('customer_phone', $cleanPhone)
                  ->orWhere('customer_phone', '0' . ltrim($cleanPhone, '0'));
            }

            // 4. Primary Key ID (if purely numeric and small enough)
            if (is_numeric($cleaned) && (int)$cleaned > 0 && strlen($cleaned) <= 8) {
                $q->orWhere('id', (int)$cleaned);
            }
        });

        // Prioritize tickets for the specified trip when multiple match (e.g. phone search)
        if ($tripId) {
            $query->orderByRaw('CASE WHEN trip_id = ? THEN 0 ELSE 1 END', [$tripId]);
        }

        return $query->latest('id')->first();
    }
}