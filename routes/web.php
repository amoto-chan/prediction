<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\RecordController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicController::class, 'index'])->name('home');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login')->name('login.attempt');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/analytics', [DashboardController::class, 'analytics'])->name('dashboard.analytics');
    Route::get('/admin/dashboard', [DashboardController::class, 'index'])->middleware('role:admin')->name('admin.dashboard');
    Route::get('/instructor/dashboard', [DashboardController::class, 'index'])->middleware('role:instructor')->name('instructor.dashboard');
    Route::get('/student/dashboard', [DashboardController::class, 'index'])->middleware('role:student')->name('student.dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
    Route::post('/messages', [MessageController::class, 'store'])->name('messages.store');
    Route::post('/messages/bulk', [MessageController::class, 'storeBulk'])->name('messages.bulk');
    Route::patch('/messages/{message}/read', [MessageController::class, 'read'])->name('messages.read');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::post('/chat', [ChatController::class, 'answer'])
        ->middleware(['role:student', 'throttle:chat'])
        ->name('chat.answer');

    Route::middleware('role:admin')->group(function () {
        Route::get('/admin/users', [AdminController::class, 'users'])->name('admin.users');
        Route::post('/admin/users', [AdminController::class, 'storeUser'])->name('admin.users.store');
        Route::delete('/admin/users/{user}', [AdminController::class, 'destroyUser'])->name('admin.users.destroy');

        Route::get('/admin/students', [AdminController::class, 'students'])->name('admin.students');
        Route::get('/admin/students/{student}', [AdminController::class, 'showStudent'])->name('admin.students.show');
        Route::patch('/admin/students/{student}', [AdminController::class, 'updateStudent'])->name('admin.students.update');
        Route::post('/admin/students/{student}/subjects', [AdminController::class, 'assignSubject'])->name('admin.students.subjects');
        Route::delete('/admin/students/{student}', [AdminController::class, 'destroyStudent'])->name('admin.students.destroy');

        Route::get('/admin/instructors', [AdminController::class, 'instructors'])->name('admin.instructors');
        Route::patch('/admin/instructors/{instructor}', [AdminController::class, 'updateInstructor'])->name('admin.instructors.update');
        Route::delete('/admin/instructors/{instructor}', [AdminController::class, 'destroyInstructor'])->name('admin.instructors.destroy');

        Route::get('/admin/logs', [AdminController::class, 'logs'])->name('admin.logs');
    });

    Route::middleware('role:admin,instructor')->group(function () {
        Route::post('/records', [RecordController::class, 'store'])->name('records.store');
        Route::patch('/records/{record}', [RecordController::class, 'update'])->name('records.update');
        Route::delete('/records/{record}', [RecordController::class, 'destroy'])->name('records.destroy');
        Route::post('/records/import', [RecordController::class, 'import'])->middleware('throttle:import')->name('records.import');
        Route::get('/records/template', [RecordController::class, 'template'])->name('records.template');
    });
});