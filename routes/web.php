<?php

use Deepcube\HandoffImageUpload\Controllers\HandoffImageUploadController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web'])->group(function () {
    Route::get('/handoff-image-upload/camera/{uuid}', [HandoffImageUploadController::class, 'showCameraPage'])
        ->where('uuid', '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}')
        ->name('handoff-image-upload.camera');
});
