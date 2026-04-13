<?php

use App\Http\Controllers\Api\GuideController;

Route::get('/guide/{channel_nr}/{date}', [GuideController::class, 'guide'])->name('guide');
Route::get('/on-air/{channel_nr}', [GuideController::class, 'onAir'])->name('on-air');
Route::get('/upcoming/{channel_nr}', [GuideController::class, 'upcoming'])->name('upcoming');
Route::post('/guide', [GuideController::class, 'store'])->middleware('basic.auth')->name('store');
