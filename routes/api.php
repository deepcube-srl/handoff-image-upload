<?php

use Deepcube\HandoffImageUpload\Controllers\HandoffImageUploadController;
use Illuminate\Support\Facades\Route;

Route::middleware(['api'])->prefix('api')->group(function () {
    Route::post('/handoff-image-upload/generate-uuid', [HandoffImageUploadController::class, 'generateUuid'])
        ->name('handoff-image-upload.generate-uuid');

    Route::post('/handoff-image-upload/upload/{uuid}', [HandoffImageUploadController::class, 'uploadImage'])
        ->where('uuid', '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}')
        ->name('handoff-image-upload.upload');

    Route::get('/handoff-image-upload/check/{uuid}', [HandoffImageUploadController::class, 'checkUpload'])
        ->where('uuid', '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}')
        ->name('handoff-image-upload.check');
});
