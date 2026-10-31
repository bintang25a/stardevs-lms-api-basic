<?php

use App\Http\Controllers\Api\AnnouncementController;
use App\Http\Controllers\Api\AttachmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClassroomController;
use App\Http\Controllers\Api\ClassroomUserController;
use App\Http\Controllers\Api\GlobalSettingController;
use App\Http\Controllers\Api\SectionController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verify'])->middleware(['signed']);

Route::middleware(['auth:sanctum', 'verified'])->group(function () {

  Route::get('/user', [AuthController::class, 'profile']);
  Route::post('/logout', [AuthController::class, 'logout']);

  Route::post('/classrooms/join', [ClassroomUserController::class, 'store']);
  Route::delete('/classrooms/leave', [ClassroomUserController::class, 'destroy']);
  Route::apiResource('classrooms', ClassroomController::class)->only(['index', 'show']);
  Route::apiResource('announcements', AnnouncementController::class)->only(['show']);
  Route::apiResource('attachments', AttachmentController::class)->only(['show']);

  Route::middleware('role:admin')->group(function () {
    Route::apiResource('global-settings', GlobalSettingController::class);
    Route::apiResource('announcements', AnnouncementController::class)->only(['index']);
    Route::apiResource('attachments', AttachmentController::class)->only(['index', 'destroy']);
  });

  Route::middleware('role:asisten')->group(function () {
    Route::apiResource('global-settings', GlobalSettingController::class);
    Route::apiResource('sections', SectionController::class)->only(['store', 'update', 'destroy']);
    Route::apiResource('announcements', AnnouncementController::class)->only(['store', 'update', 'destroy']);
  });

  Route::middleware('role:admin,asisten')->group(function () {
    Route::apiResource('classrooms', ClassroomController::class)->only(['store', 'update', 'destroy']);
  });
});

Route::get('/unauthenticated', function () {
  return response()->json([
    'success' => false,
    'message' => 'Unauthenticated or Invalid Token.',
  ], 401);
})->name('api.unauthenticated');
