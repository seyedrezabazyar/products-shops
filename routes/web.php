<?php

use App\Http\Controllers\ApiController;
use App\Http\Controllers\ConfigController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::post('/configs/{filename}/run', [App\Http\Controllers\ConfigController::class, 'runScraper'])->name('configs.run');
Route::get('/configs/{filename}/logs', [App\Http\Controllers\ConfigController::class, 'showLogs'])->name('configs.logs');
Route::delete('/configs/logs/delete-all', [ConfigController::class, 'deleteAllLogs'])->name('configs.logs.deleteAll');
Route::get('/configs/log-content/{logfile}', [App\Http\Controllers\ConfigController::class, 'getLogContent'])->name('configs.log-content');
Route::post('/configs/{filename}/stop', [App\Http\Controllers\ConfigController::class, 'stopScraper'])->name('configs.stop');
Route::delete('/configs/logs/{logfile}/delete', [App\Http\Controllers\ConfigController::class, 'deleteLog'])->name('configs.logs.delete');
Route::get('/', [ConfigController::class, 'index'])->name('configs.index');
Route::get('/configs/create', [ConfigController::class, 'create'])->name('configs.create');
Route::post('/configs', [ConfigController::class, 'store'])->name('configs.store');
Route::get('/configs/{filename}/edit', [ConfigController::class, 'edit'])->name('configs.edit');
Route::put('/configs/{filename}', [ConfigController::class, 'update'])->name('configs.update');
Route::delete('/configs/{filename}', [ConfigController::class, 'destroy'])->name('configs.destroy');
Route::get('/api', [ApiController::class, 'index'])->name('api.index');
Route::get('/api/{store}', [ProductController::class, 'index'])->name('store.products');
