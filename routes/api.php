<?php

use App\Http\Controllers\Api\GuideController;

Route::get('/guide/{channel_nr}/{date}', [GuideController::class, 'guide'])->name('guide');
