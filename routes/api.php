<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\BusApiController;
use App\Http\Controllers\ScannerController;
use App\Http\Controllers\AiAssistantController;

// 1. Tuyến đường & Điểm đi / Điểm đến
Route::get('/locations', [BusApiController::class, 'getLocations']);
Route::get('/routes', [BusApiController::class, 'getRoutes']);

// 2. Chuyến xe & Sơ đồ ghế
Route::get('/trips', [BusApiController::class, 'getTrips']);
Route::get('/trips/{id}', [BusApiController::class, 'getTripDetail']);

// 3. Đặt vé & Tra cứu vé
Route::post('/bookings/create', [BusApiController::class, 'createBooking']);
Route::get('/tickets/{code}', [BusApiController::class, 'getTicket']);

// 4. Soát vé QR cho lơ xe
Route::post('/scanner/verify', [ScannerController::class, 'verify']);
Route::post('/scanner/checkin', [ScannerController::class, 'checkin']);

// 5. Danh sách tài xế
Route::get('/drivers', [BusApiController::class, 'getDrivers']);

// 6. Trợ lý AI
Route::post('/ai/chat', [AiAssistantController::class, 'chat']);
