<?php

use App\Http\Controllers\ConfigController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ConfigController::class, 'index'])->name('configs.index');
Route::get('/configs/create', [ConfigController::class, 'create'])->name('configs.create');
Route::post('/configs', [ConfigController::class, 'store'])->name('configs.store');
Route::get('/configs/{filename}/edit', [ConfigController::class, 'edit'])->name('configs.edit');
Route::put('/configs/{filename}', [ConfigController::class, 'update'])->name('configs.update');
Route::delete('/configs/{filename}', [ConfigController::class, 'destroy'])->name('configs.destroy');
