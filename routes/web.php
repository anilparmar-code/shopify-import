<?php

use App\Http\Controllers\ErrorLogController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\UploadController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    Route::get('uploads', [UploadController::class, 'index'])->name('uploads.index');
    Route::get('uploads/create', [UploadController::class, 'create'])->name('uploads.create');
    Route::post('uploads', [UploadController::class, 'store'])->name('uploads.store');
    Route::get('uploads/{upload}/import-records', [UploadController::class, 'importRecords'])->name('uploads.import-records');

    Route::get('error-logs', [ErrorLogController::class, 'index'])->name('error-logs.index');
    Route::get('products', [ProductController::class, 'index'])->name('products.index');
});

require __DIR__.'/settings.php';
