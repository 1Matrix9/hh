<?php

use App\Http\Controllers\CourseController;
use App\Http\Controllers\CourseSectionController;
use App\Http\Controllers\VideoController;
use Illuminate\Http\Request;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\BlogController;
use Illuminate\Support\Facades\Route;

// Public routes (no authentication required)
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login'])
    ->middleware('throttle:5,1');
Route::get('/blogs', [BlogController::class, 'index']);
Route::get('/blogs/{id}', [BlogController::class, 'show']);
// Password Reset Routes
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);

// Email verification
Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
    ->middleware(['signed'])
    ->name('verification.verify');

// Protected routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/email/resend', [AuthController::class, 'resendVerification'])
        ->middleware('throttle:6,1');
    
    // User routes
    Route::get('/user', [UserController::class, 'show']);
    Route::put('/user/profile', [UserController::class, 'updateProfile']);
    Route::put('/user/change-password', [UserController::class, 'changePassword']);
    Route::get('/user/wallet', [UserController::class, 'getWallet']);
    Route::get('/user/leaderboard', [UserController::class, 'getLeaderboard']);
    // Admin-only user routes
    Route::middleware('admin')->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::delete('/users/{id}', [UserController::class, 'destroy']);
    });

    // Admin-only Wallet routes
    Route::middleware('admin')->group(function () {
        Route::post('/user/{id}/adjust-wallet', [UserController::class, 'adjustWallet']);
        Route::post('/user/{id}/deposit-wallet', [UserController::class, 'depositWallet']);
        Route::post('/user/{id}/adjust-points', [UserController::class, 'adjustPoints']);
    });

    // Admin-only blog routes
    Route::middleware('admin')->group(function () {
        Route::post('/blogs', [BlogController::class, 'store']);
        Route::put('/blogs/{id}', [BlogController::class, 'update']);
        Route::delete('/blogs/{id}', [BlogController::class, 'destroy']);
    });

    // Courses Routes
    Route::get('/courses', [CourseController::class, 'index']);
    Route::get('/courses/{id}', [CourseController::class, 'show']);
    Route::post('/courses/{id}/purchase', [CourseController::class, 'purchase']);
    Route::post('/courses/{id}/progress', [CourseController::class, 'updateProgress']);
    // Admin-only course routes
    Route::middleware('admin')->group(function () {
        Route::post('/courses', [CourseController::class, 'store']);
        Route::put('/courses/{id}', [CourseController::class, 'update']);
        Route::delete('/courses/{id}', [CourseController::class, 'destroy']);
    });

    // Course Section Routes
    Route::get('/courses/{course_id}/course-sections', [CourseSectionController::class, 'index']);
    Route::get('/courses/{course_id}/course-sections/{id}', [CourseSectionController::class, 'show']);
    // Admin-only course section routes
    Route::middleware('admin')->group(function () {
        Route::post('/courses/{course_id}/course-sections', [CourseSectionController::class, 'store']);
        Route::put('/courses/{course_id}/course-sections/{id}', [CourseSectionController::class, 'update']);
        Route::delete('/courses/{course_id}/course-sections/{id}', [CourseSectionController::class, 'destroy']);
    });

    // Video Routes
    Route::get('/courses/{course_id}/course-sections/{section_id}/videos', [VideoController::class, 'index']);
    Route::get('/courses/{course_id}/course-sections/{section_id}/videos/{id}', [VideoController::class, 'show']);
    // Admin-only video routes
    Route::middleware('admin')->group(function () {
        Route::post('/courses/{course_id}/course-sections/{section_id}/videos', [VideoController::class, 'store']);
        Route::put('/courses/{course_id}/course-sections/{section_id}/videos/{id}', [VideoController::class, 'update']);
        Route::delete('/courses/{course_id}/course-sections/{section_id}/videos/{id}', [VideoController::class, 'destroy']);
    });
});
