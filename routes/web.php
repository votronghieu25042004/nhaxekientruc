<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TripController;
use App\Http\Controllers\ConductorController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\ScannerController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\AiAssistantController;

/*
|--------------------------------------------------------------------------
| Web Routes - BusHub Central Terminal Bus Booking System
|--------------------------------------------------------------------------
*/

// 1. Authentication Routes (Đăng Nhập Thông Minh & Đăng Ký Xác Thực Gmail)
Route::get('login', [AuthController::class, 'showLogin'])->name('login');
Route::post('login', [AuthController::class, 'login']);
Route::get('register', [AuthController::class, 'showRegister'])->name('register');
Route::post('register', [AuthController::class, 'register']);
Route::post('register/check-exists', [AuthController::class, 'checkRegisterExists'])->name('register.checkExists');
Route::get('register/check-exists', fn() => redirect()->route('register'));
Route::post('register/send-otp', [AuthController::class, 'sendRegisterOtp'])->name('register.sendOtp');
Route::get('register/send-otp', fn() => redirect()->route('register'));
Route::post('register/verify-otp', [AuthController::class, 'verifyRegisterOtp'])->name('register.verifyOtp');
Route::get('register/verify-otp', fn() => redirect()->route('register'));
Route::post('auth/google-login', [AuthController::class, 'googleLogin'])->name('auth.googleLogin');
Route::post('logout', [AuthController::class, 'logout'])->name('logout');

// 1.1 Fast Login & Role Switcher
Route::post('auth/fast-login', [AuthController::class, 'fastLogin'])->name('auth.fastLogin');
Route::get('auth/fast-login/{role}', [AuthController::class, 'fastLoginGet'])->name('auth.fastLoginGet');

// 1.2 OTP & Quick Authentication
Route::post('auth/send-otp', [AuthController::class, 'sendOtp'])->name('auth.sendOtp');
Route::post('auth/verify-otp', [AuthController::class, 'verifyOtp'])->name('auth.verifyOtp');

// 1.3 User Profile, Password & Linking Account (Gmail / Phone via OTP)
Route::post('user/profile', [AuthController::class, 'updateProfile'])->name('user.profile.update');
Route::post('user/change-password', [AuthController::class, 'changePassword'])->name('user.password.change');
Route::post('user/send-otp', [AuthController::class, 'sendProfileOtp'])->name('user.profile.sendOtp');
Route::post('user/verify-otp', [AuthController::class, 'verifyProfileOtp'])->name('user.profile.verifyOtp');
Route::post('user/delete-account', [AuthController::class, 'deleteAccount'])->name('user.deleteAccount');

// 2. Schedules Route
Route::get('schedules', [TripController::class, 'schedules'])->name('schedules.index');

// Diagnostic route
Route::get('debug-check', function () {
    $results = [];
    $results['php_version'] = PHP_VERSION;
    $results['app_env'] = config('app.env');
    $results['app_debug'] = config('app.debug');
    $results['db_connection'] = config('database.default');
    $results['db_host'] = config('database.connections.mysql.host');
    $results['db_port'] = config('database.connections.mysql.port');
    $results['db_database'] = config('database.connections.mysql.database');
    $results['session_driver'] = config('session.driver');
    
    try {
        \Illuminate\Support\Facades\DB::connection()->getPdo();
        $results['db_status'] = 'CONNECTED';
        $results['tables'] = \Illuminate\Support\Facades\DB::select('SHOW TABLES');
    } catch (\Throwable $e) {
        $results['db_status'] = 'FAILED: ' . $e->getMessage();
    }
    
    return response()->json($results);
});

// 2. Customer Routes
Route::get('/', [TripController::class, 'home'])->name('home');
Route::get('trips', [TripController::class, 'search'])->name('trips.index');
Route::get('trips/{id}', [TripController::class, 'show'])->name('trips.show');
Route::post('trips/{id}/book', [TripController::class, 'book'])->name('trips.book');
Route::get('booking/ticket/{code}', [TripController::class, 'ticket'])->name('booking.ticket');
Route::get('ve/{code}', [TripController::class, 'ticket'])->name('booking.ticket.alias');
Route::get('booking/{code}', [TripController::class, 'ticket'])->name('booking.ticket.short');
Route::get('booking/{code}/detail', [TripController::class, 'bookingDetail'])->name('booking.detail');
Route::get('booking/{code}/payment', [TripController::class, 'paymentScreen'])->name('booking.payment');
Route::get('api/booking/{code}/payment-status', [TripController::class, 'checkPaymentStatus'])->name('api.booking.paymentStatus');
Route::post('api/booking/{code}/confirm-payment', [TripController::class, 'confirmPayment'])->name('api.booking.confirmPayment');
Route::post('api/payment/webhook', [TripController::class, 'handlePaymentWebhook'])->name('api.payment.webhook');
Route::post('api/payment/sepay-webhook', [TripController::class, 'handlePaymentWebhook']);
Route::post('booking/{code}/send-ai-reminder', [TripController::class, 'sendAiReminderEmail'])->name('booking.sendAiReminder');
Route::post('booking/{code}/cancel-request', [TripController::class, 'requestBookingRefund'])->name('booking.cancelRequest');
Route::get('trips/{id}/live-tracking', [TripController::class, 'liveTracking'])->name('trips.liveTracking');
Route::post('trips/{id}/update-gps', [TripController::class, 'updateGps'])->name('trips.updateGps');

// Customer booking history (by phone/email)
Route::get('my-bookings', [TripController::class, 'myBookings'])->name('my.bookings');
Route::get('api/my-bookings', [TripController::class, 'myBookingsApi'])->name('api.myBookings');
Route::get('api/vouchers', [TripController::class, 'activeVouchersApi'])->name('api.vouchers.list');
Route::post('api/vouchers/apply', [TripController::class, 'applyVoucher'])->name('api.vouchers.apply');

// Customer Reviews
Route::get('booking/{code}/review', [ReviewController::class, 'show'])->name('reviews.show');
Route::post('booking/{code}/review', [ReviewController::class, 'store'])->name('reviews.store');

// 3. Conductor / Lơ Xe Operations
Route::get('staff/manifest', [ConductorController::class, 'dashboard'])->name('staff.manifest');
Route::get('conductor', [ConductorController::class, 'dashboard'])->name('conductor.dashboard');
Route::get('staff', [ConductorController::class, 'dashboard']);

Route::post('conductor/trips/{id}/status', [ConductorController::class, 'updateTripStatus'])->name('conductor.trip.status');
Route::post('conductor/trips/{id}/stop', [ConductorController::class, 'updateCurrentStop'])->name('conductor.trip.stop');
Route::post('conductor/trips/{id}/roadside-ticket', [ConductorController::class, 'createRoadsideTicket'])->name('conductor.ticket.create');
Route::post('conductor/checkin/{id}', [ConductorController::class, 'checkinManual'])->name('conductor.checkin');
Route::post('conductor/bookings/{id}/checkin', [ConductorController::class, 'checkinManual']);
Route::post('conductor/bookings/{id}/change-seat', [ConductorController::class, 'changeSeat'])->name('conductor.changeSeat');
Route::post('conductor/scan-qr', [ConductorController::class, 'scanQr'])->name('conductor.scanQr');
Route::post('conductor/bookings/{id}/no-show', [ConductorController::class, 'markNoShow'])->name('conductor.noShow');
Route::post('conductor/bookings/{id}/release-seat', [ConductorController::class, 'releaseNoShowSeat'])->name('conductor.releaseSeat');
Route::post('conductor/bookings/{id}/collect-payment', [ConductorController::class, 'collectPayment'])->name('conductor.collectPayment');
Route::post('conductor/trips/{id}/expense', [ConductorController::class, 'storeExpense'])->name('conductor.expense.store');
Route::post('conductor/trips/{id}/expenses', [ConductorController::class, 'storeExpense']);
Route::post('conductor/trips/{id}/incident', [ConductorController::class, 'reportIncident'])->name('conductor.incident.store');
Route::post('conductor/trips/{id}/lost-found', [ConductorController::class, 'storeLostFound'])->name('conductor.lostFound.store');
Route::post('conductor/trips/{id}/close', [ConductorController::class, 'closeTrip'])->name('conductor.trip.close');

// 3.5 Driver / Tài Xế Operations
Route::get('driver', [DriverController::class, 'index'])->name('driver.index');
Route::get('driver/history', [DriverController::class, 'history'])->name('driver.history');
Route::get('api/driver/reviews', [DriverController::class, 'reviewsApi'])->name('api.driver.reviews');
Route::get('api/driver/history-trips', [DriverController::class, 'historyTripsApi'])->name('api.driver.historyTrips');

    // Bác tài hỗ trợ Check-in & Bán vé tại chỗ / Đón dọc đường (giống lơ xe khi rảnh)
    Route::post('driver/scan-qr', [ConductorController::class, 'scanQr'])->name('driver.scanQr');
    Route::post('driver/bookings/{id}/checkin', [ConductorController::class, 'checkinManual'])->name('driver.checkin');
    Route::post('driver/bookings/{id}/change-seat', [ConductorController::class, 'changeSeat'])->name('driver.changeSeat');
    Route::post('driver/bookings/{id}/no-show', [ConductorController::class, 'markNoShow'])->name('driver.noShow');
    Route::post('driver/bookings/{id}/release-seat', [ConductorController::class, 'releaseNoShowSeat'])->name('driver.releaseSeat');
    Route::post('driver/bookings/{id}/collect-payment', [ConductorController::class, 'collectPayment'])->name('driver.collectPayment');
    Route::post('driver/trips/{id}/roadside-ticket', [ConductorController::class, 'createRoadsideTicket'])->name('driver.ticket.create');

Route::post('driver/trips/{id}/accept', [DriverController::class, 'acceptTrip'])->name('driver.trip.accept');
Route::post('driver/trips/{id}/reject', [DriverController::class, 'rejectTrip'])->name('driver.trip.reject');
Route::post('driver/trips/{id}/verify-vehicle', [DriverController::class, 'verifyVehicle'])->name('driver.trip.verifyVehicle');
Route::post('driver/trips/{id}/report-vehicle-issue', [DriverController::class, 'reportVehicleIssue'])->name('driver.trip.reportVehicleIssue');
Route::post('driver/trips/{id}/report-incident', [DriverController::class, 'reportIncident'])->name('driver.trip.reportIncident');
Route::post('driver/trips/{id}/report-delay', [DriverController::class, 'reportDelay'])->name('driver.trip.reportDelay');
Route::post('driver/trips/{id}/start', [DriverController::class, 'startTrip'])->name('driver.trip.start');
Route::post('driver/trips/{id}/finish', [DriverController::class, 'finishTrip'])->name('driver.trip.finish');
Route::post('driver/trips/{id}/rest-stop', [DriverController::class, 'restStop'])->name('driver.trip.restStop');
Route::post('driver/trips/{id}/resume', [DriverController::class, 'resumeTrip'])->name('driver.trip.resume');
Route::post('driver/trips/{id}/stop', [DriverController::class, 'updateCurrentStop'])->name('driver.trip.stop');
Route::post('driver/trips/{id}/close', [DriverController::class, 'closeTrip'])->name('driver.trip.close');
Route::post('driver/trips/{id}/expense', [DriverController::class, 'storeExpense'])->name('driver.trip.expense');
Route::post('driver/trips/{id}/sos', [DriverController::class, 'triggerSos'])->name('driver.trip.sos');
Route::post('driver/trips/{id}/change-shift', [DriverController::class, 'changeShift'])->name('driver.trip.changeShift');

// 4. QR Scanner Operations
Route::get('scanner', [ScannerController::class, 'index'])->name('scanner.index');
Route::post('scanner/verify', [ScannerController::class, 'verify'])->name('scanner.verify');
Route::post('scanner/checkin', [ScannerController::class, 'checkin'])->name('scanner.checkin');

// 5. Admin Portal
Route::prefix('admin')->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('fleet-trip/{tripId}/details', [AdminController::class, 'fleetTripDetails'])->name('admin.fleetTripDetails');
    Route::get('routes', [AdminController::class, 'routes'])->name('admin.routes');
    Route::post('routes', [AdminController::class, 'storeRoute'])->name('admin.routes.store');

    Route::get('trips', [AdminController::class, 'trips'])->name('admin.trips');
    Route::post('trips', [AdminController::class, 'storeTrip'])->name('admin.trips.store');
    Route::post('trips/{id}/price', [AdminController::class, 'updateTripPrice'])->name('admin.trips.price');
    Route::post('trips/{id}/assign-driver', [AdminController::class, 'assignDriver'])->name('admin.trips.assignDriver');
    Route::post('trips/{id}/status', [AdminController::class, 'updateTripStatus'])->name('admin.trips.updateStatus');
    Route::post('trips/bulk-adjust-prices', [AdminController::class, 'bulkAdjustTripPrices'])->name('admin.trips.bulkAdjustPrices');

    // Các Route Tự Động Gửi Email & Nhắc Nhở 45 Phút
    Route::post('trips/{id}/notify-incident', [AdminController::class, 'notifyIncident'])->name('admin.trips.notifyIncident');
    Route::post('trips/{id}/notify-reminder', [AdminController::class, 'notifyReminder'])->name('admin.trips.notifyReminder');
    Route::post('trips/notify-all-reminders', [AdminController::class, 'notifyAllReminders'])->name('admin.trips.notifyAllReminders');
    Route::post('bookings/{id}/notify-and-call', [AdminController::class, 'notifyAndCallBooking'])->name('admin.bookings.notifyAndCall');

    Route::post('counter-booking', [AdminController::class, 'counterBook'])->name('admin.counterBooking');

    Route::get('buses', [AdminController::class, 'buses'])->name('admin.buses');
    Route::post('buses', [AdminController::class, 'storeBus'])->name('admin.buses.store');
    Route::post('buses/{id}/toggle-status', [AdminController::class, 'toggleBusStatus'])->name('admin.buses.toggleStatus');
    Route::post('buses/{id}/liquidate', [AdminController::class, 'liquidateBus'])->name('admin.buses.liquidate');
    Route::delete('buses/{id}', [AdminController::class, 'deleteBus'])->name('admin.buses.delete');

    Route::get('drivers', [AdminController::class, 'drivers'])->name('admin.drivers');
    Route::post('drivers', [AdminController::class, 'storeDriver'])->name('admin.drivers.store');
    Route::post('drivers/{id}/upload-docs', [AdminController::class, 'uploadDriverDocs'])->name('admin.drivers.uploadDocs');
    Route::post('drivers/{id}/update-license', [AdminController::class, 'updateDriverLicense'])->name('admin.drivers.updateLicense');
    Route::delete('drivers/{id}', [AdminController::class, 'deleteDriver'])->name('admin.drivers.delete');

    Route::get('users', [AdminController::class, 'users'])->name('admin.users');
    Route::get('customers', [AdminController::class, 'customers'])->name('admin.customers');
    Route::post('users/staff', [AdminController::class, 'storeStaff'])->name('admin.users.storeStaff');

    Route::get('finance', [AdminController::class, 'finance'])->name('admin.finance');
    Route::post('closings/{id}/audit', [AdminController::class, 'auditTripClosing'])->name('admin.closings.audit');

    // POS Screen & Operations
    Route::get('pos', [AdminController::class, 'posScreen'])->name('admin.pos');
    Route::post('pos/lock-seat', [AdminController::class, 'lockSeat'])->name('admin.pos.lockSeat');
    Route::post('pos/unlock-seat', [AdminController::class, 'unlockSeat'])->name('admin.pos.unlockSeat');
    Route::post('pos/book', [AdminController::class, 'posBook'])->name('admin.pos.book');
    Route::get('pos/trip/{tripId}/seats', [AdminController::class, 'posTripSeats'])->name('admin.pos.tripSeats');
    Route::post('pos/booking/{id}/mark-paid', [AdminController::class, 'markBookingPaid'])->name('admin.pos.markPaid');
    Route::post('pos/booking/{id}/cancel', [AdminController::class, 'cancelBookingAdmin'])->name('admin.pos.cancelBooking');

    // Live Tracking Map & SOS
    Route::get('tracking', [AdminController::class, 'trackingScreen'])->name('admin.tracking');
    Route::post('tracking/trigger-sos-demo', [AdminController::class, 'triggerSosDemo'])->name('admin.tracking.sosDemo');
    Route::post('tracking/resolve-sos-demo', [AdminController::class, 'resolveSosDemo'])->name('admin.tracking.resolveDemo');

    // Pricing & Promotions (Giá & Khuyến Mãi)
    Route::get('pricing', [AdminController::class, 'pricingScreen'])->name('admin.pricing');
    Route::get('vouchers', [AdminController::class, 'vouchersScreen'])->name('admin.vouchers');
    Route::post('pricing/rules', [AdminController::class, 'storePricingRule'])->name('admin.pricing.storeRule');
    Route::post('pricing/rules/{id}/toggle', [AdminController::class, 'togglePricingRule'])->name('admin.pricing.toggleRule');
    Route::post('pricing/vouchers', [AdminController::class, 'storeVoucher'])->name('admin.pricing.storeVoucher');
    Route::post('pricing/vouchers/{id}/toggle', [AdminController::class, 'toggleVoucher'])->name('admin.pricing.toggleVoucher');
    Route::post('pricing/vouchers/{id}/update', [AdminController::class, 'updateVoucher'])->name('admin.pricing.updateVoucher');
    Route::post('pricing/vouchers/{id}/delete', [AdminController::class, 'deleteVoucher'])->name('admin.pricing.deleteVoucher');
    Route::post('pricing/refund-policy/{id}', [AdminController::class, 'updateRefundPolicy'])->name('admin.pricing.updatePolicy');

    // Refund & Cancellation Management (Quản lý Hoàn tiền & Hủy vé)
    Route::get('refunds', [AdminController::class, 'refundsScreen'])->name('admin.refunds');
    Route::post('refunds/{id}/approve', [AdminController::class, 'approveRefund'])->name('admin.refunds.approve');
    Route::post('refunds/{id}/reject', [AdminController::class, 'rejectRefund'])->name('admin.refunds.reject');

    // Fleet & Inventory (Vật tư & Chi phí đội xe)
    Route::get('fleet-inventory', [AdminController::class, 'fleetInventoryScreen'])->name('admin.fleetInventory');
    Route::post('fleet-inventory/maintenance', [AdminController::class, 'storeMaintenanceRecord'])->name('admin.fleetInventory.storeMaintenance');
    Route::post('fleet-inventory/fuel', [AdminController::class, 'storeFuelRecord'])->name('admin.fleetInventory.storeFuel');
    Route::post('fleet-inventory/fixed-expense', [AdminController::class, 'storeFixedExpense'])->name('admin.fleetInventory.storeFixed');

    // Route Stops & ETA & Daily Trips Management
    Route::post('routes/{id}/update', [AdminController::class, 'updateRoute'])->name('admin.routes.update');
    Route::delete('routes/{id}', [AdminController::class, 'deleteRoute'])->name('admin.routes.delete');
    Route::post('routes/{id}/stops', [AdminController::class, 'updateRouteStops'])->name('admin.routes.updateStops');
    Route::post('routes/{id}/daily-trip', [AdminController::class, 'addDailyTrip'])->name('admin.routes.addDailyTrip');
    Route::post('routes/daily-trip/{tripId}/update-time', [AdminController::class, 'updateDailyTripTime'])->name('admin.routes.updateDailyTripTime');
    Route::delete('routes/daily-trip/{tripId}', [AdminController::class, 'deleteDailyTrip'])->name('admin.routes.deleteDailyTrip');

    // Dispatching & Recurring Trips
    Route::post('trips/recurring', [AdminController::class, 'storeRecurringTrips'])->name('admin.trips.recurring');

    // Settlement & Road Expenses
    Route::post('finance/settle-conductor', [AdminController::class, 'settleConductorCash'])->name('admin.finance.settleConductor');
    Route::post('finance/collect-cash', [AdminController::class, 'collectConductorCash'])->name('admin.finance.collectCash');
    Route::post('expenses/{id}/approve', [AdminController::class, 'approveExpense'])->name('admin.expenses.approve');
    Route::post('expenses/{id}/reject', [AdminController::class, 'rejectExpense'])->name('admin.expenses.reject');

    // System Roles & RBAC (Quản lý tài khoản & Phân quyền)
    Route::get('roles', [AdminController::class, 'rolesPermissionsScreen'])->name('admin.roles');
    Route::post('roles/users/{id}/update', [AdminController::class, 'updateUserRole'])->name('admin.roles.updateUser');
    Route::post('roles/users/{id}/toggle-status', [AdminController::class, 'toggleUserStatus'])->name('admin.roles.toggleStatus');
    Route::post('users/{id}/toggle-status', [AdminController::class, 'toggleUserStatus'])->name('admin.users.toggleStatus');
    Route::delete('users/{id}', [AdminController::class, 'deleteUser'])->name('admin.users.delete');
    Route::post('users/{id}/delete', [AdminController::class, 'deleteUser'])->name('admin.users.deletePost');

    // Audit Logs & System Activity Trail (Nhật Ký Vận Hành)
    Route::get('logs', [AdminController::class, 'auditLogs'])->name('admin.logs');
    Route::get('audit-logs', [AdminController::class, 'auditLogs'])->name('admin.auditLogs');

    // SOS Resolution & Auto-Cronjob Trigger
    Route::post('incidents/{id}/resolve-sos', [AdminController::class, 'resolveSos'])->name('admin.incidents.resolveSos');
    Route::post('system/run-auto-cron', [AdminController::class, 'runAutoCron'])->name('admin.system.runAutoCron');
});

// AI Chatbot Assistant & Auto Notification
Route::post('ai/chat', [AiAssistantController::class, 'chat'])->name('ai.chat');
Route::post('api/ai/chat', [AiAssistantController::class, 'chat']);
Route::post('ai/auto-notify', [AiAssistantController::class, 'autoNotify'])->name('ai.autoNotify');
Route::post('api/ai/auto-notify', [AiAssistantController::class, 'autoNotify']);
Route::post('ai/custom-notify', [AiAssistantController::class, 'customNotify'])->name('ai.customNotify');
Route::post('api/ai/custom-notify', [AiAssistantController::class, 'customNotify']);