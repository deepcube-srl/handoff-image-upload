<?php

use Illuminate\Support\Facades\Route;
use Se09deluca\HandoffImageUpload\Controllers\HandoffImageUploadController;

Route::middleware(['web'])->group(function () {
    Route::get('/handoff-image-upload/camera/{uuid}', [HandoffImageUploadController::class, 'showCameraPage'])
        ->name('handoff-image-upload.camera');
});
