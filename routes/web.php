<?php

use App\Http\Controllers\ConfigController;
use Illuminate\Support\Facades\Route;
// روت برای اجرای اسکرپر
Route::post('/configs/{filename}/run', [App\Http\Controllers\ConfigController::class, 'runScraper'])->name('configs.run');

// روت برای نمایش لاگ‌ها
Route::get('/configs/{filename}/logs', [App\Http\Controllers\ConfigController::class, 'showLogs'])->name('configs.logs');

// روت برای دریافت محتوای یک لاگ خاص
Route::get('/configs/log-content/{logfile}', [App\Http\Controllers\ConfigController::class, 'getLogContent'])->name('configs.log-content');
// روت برای توقف اسکرپر
Route::post('/configs/{filename}/stop', [App\Http\Controllers\ConfigController::class, 'stopScraper'])->name('configs.stop');
// روت برای حذف فایل لاگ
Route::delete('/configs/logs/{logfile}/delete', [App\Http\Controllers\ConfigController::class, 'deleteLog'])->name('configs.logs.delete');
Route::get('/', [ConfigController::class, 'index'])->name('configs.index');
Route::get('/configs/create', [ConfigController::class, 'create'])->name('configs.create');
Route::post('/configs', [ConfigController::class, 'store'])->name('configs.store');
Route::get('/configs/{filename}/edit', [ConfigController::class, 'edit'])->name('configs.edit');
Route::put('/configs/{filename}', [ConfigController::class, 'update'])->name('configs.update');
Route::delete('/configs/{filename}', [ConfigController::class, 'destroy'])->name('configs.destroy');
