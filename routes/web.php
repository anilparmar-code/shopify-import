<?php

use App\Http\Controllers\UploadController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    Route::get('uploads', [UploadController::class, 'index'])->name('uploads.index');
    Route::get('uploads/create', [UploadController::class, 'create'])->name('uploads.create');
    Route::post('uploads', [UploadController::class, 'store'])->name('uploads.store');
});

require __DIR__.'/settings.php';
