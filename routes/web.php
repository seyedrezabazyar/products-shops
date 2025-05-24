<?php

use App\Http\Controllers\ApiController;
use App\Http\Controllers\ConfigController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// روت‌های عمومی (بدون نیاز به احراز هویت)
Route::get('/', function () {
    return redirect()->route('login');
});

// روت‌های Breeze برای احراز هویت
require __DIR__ . '/auth.php';

// روت‌های محافظت شده با احراز هویت
Route::middleware('auth')->group(function () {
    // روت‌های پروفایل Breeze
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // روت‌های برنامه شما
    Route::get('/dashboard', function () {
        return redirect()->route('configs.index');
    })->name('dashboard');

    Route::post('/configs/{filename}/run', [ConfigController::class, 'runScraper'])->name('configs.run');
    Route::get('/configs/{filename}/logs', [ConfigController::class, 'showLogs'])->name('configs.logs');
    Route::delete('/configs/logs/delete-all', [ConfigController::class, 'deleteAllLogs'])->name('configs.logs.deleteAll');
    Route::get('/configs/history', [ConfigController::class, 'history'])->name('configs.history');
    Route::get('/configs/log-content/{logfile}', [ConfigController::class, 'getLogContent'])->name('configs.log-content');
    Route::post('/configs/{filename}/stop', [ConfigController::class, 'stopScraper'])->name('configs.stop');
    Route::delete('/configs/logs/{logfile}/delete', [ConfigController::class, 'deleteLog'])->name('configs.logs.delete');
    Route::get('/configs', [ConfigController::class, 'index'])->name('configs.index');
    Route::get('/configs/create', [ConfigController::class, 'create'])->name('configs.create');
    Route::post('/configs', [ConfigController::class, 'store'])->name('configs.store');
    Route::get('/configs/{filename}/edit', [ConfigController::class, 'edit'])->name('configs.edit');
    Route::put('/configs/{filename}', [ConfigController::class, 'update'])->name('configs.update');
    Route::delete('/configs/{filename}', [ConfigController::class, 'destroy'])->name('configs.destroy');
    Route::get('/api', [ApiController::class, 'index'])->name('api.index');
    Route::get('/api/{store}', [ProductController::class, 'index'])->name('store.products');
});
