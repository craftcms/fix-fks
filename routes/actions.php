<?php

declare(strict_types=1);

use CraftCms\FixFks\Http\Controllers\RestoreForeignKeysController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::post('restore', RestoreForeignKeysController::class);
});
