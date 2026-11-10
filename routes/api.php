<?php

use App\Http\Controllers\Api\ActionController;
use App\Http\Controllers\Api\AnnouncementController;
use App\Http\Controllers\Api\AssignmentController;
use App\Http\Controllers\Api\AttachmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClassroomController;
use App\Http\Controllers\Api\ClassroomUserController;
use App\Http\Controllers\Api\GlobalSettingController;
use App\Http\Controllers\Api\MaterialController;
use App\Http\Controllers\Api\SectionController;
use Illuminate\Support\Facades\Route;


Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verify'])->middleware(['signed'])->name("verification.verify");

Route::middleware(['auth:sanctum', 'verified', 'throttle:profile-limit'])->group(function () {

  Route::get('/user', [AuthController::class, 'profile']);
  Route::post('/logout', [AuthController::class, 'logout']);
  Route::post('/update-user', [AuthController::class, 'updateProfile']);

  Route::post('/classrooms-join', [ClassroomUserController::class, 'join']);
  Route::post('/classrooms-leave', [ClassroomUserController::class, 'leave']);
  Route::apiResource('classrooms', ClassroomController::class)->only(['index', 'show']);
  Route::apiResource('sections', SectionController::class)->only(['show']);
  Route::apiResource('announcements', AnnouncementController::class)->only(['show']);
  Route::apiResource('attachments', AttachmentController::class)->only(['show']);
  Route::apiResource('materials', MaterialController::class)->only(['show']);
  Route::apiResource('assignments', AssignmentController::class)->only(['show']);
  Route::get('/actions/file-download/{id}', [ActionController::class, 'fileDownload']);

  Route::middleware('role:admin')->group(function () {
    Route::apiResource('global-settings', GlobalSettingController::class);
    Route::apiResource('announcements', AnnouncementController::class)->only(['index']);
    Route::apiResource('attachments', AttachmentController::class)->only(['index',]);
    Route::apiResource('materials', MaterialController::class)->only(['index']);
    Route::apiResource('assignments', AssignmentController::class)->only(['index']);
  });

  Route::middleware('role:asisten')->group(function () {
    Route::apiResource('global-settings', GlobalSettingController::class);
    Route::apiResource('sections', SectionController::class)->only(['store', 'update', 'destroy']);
    Route::apiResource('announcements', AnnouncementController::class)->only(['store', 'update', 'destroy']);
    Route::apiResource('materials', MaterialController::class)->only(['store', 'update', 'destroy']);
    Route::apiResource('assignments', AssignmentController::class)->only(['store', 'update', 'destroy']);
    Route::post('/actions/raise-order', [ActionController::class, 'raiseOrder']);
    Route::post('/actions/lower-order', [ActionController::class, 'lowerOrder']);
  });

  Route::middleware('role:admin,asisten')->group(function () {
    Route::post('/classrooms-add', [ClassroomUserController::class, 'add']);
    Route::apiResource('classrooms', ClassroomController::class)->only(['store', 'update', 'destroy']);
    Route::apiResource('attachments', AttachmentController::class)->only(['destroy']);
  });
});

Route::get('/unauthenticated', function () {
  return response()->json([
    'success' => false,
    'message' => 'Unauthenticated or Invalid Token.',
  ], 401);
})->name('api.unauthenticated');
