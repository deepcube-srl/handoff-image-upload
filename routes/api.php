<?php

use Illuminate\Support\Facades\Route;
use Se09deluca\HandoffImageUpload\Controllers\HandoffImageUploadController;

Route::middleware(['api'])->prefix('api')->group(function () {
    Route::post('/handoff-image-upload/generate-uuid', [HandoffImageUploadController::class, 'generateUuid'])
        ->name('handoff-image-upload.generate-uuid');

    Route::post('/handoff-image-upload/upload/{uuid}', [HandoffImageUploadController::class, 'uploadImage'])
        ->name('handoff-image-upload.upload');

    Route::get('/handoff-image-upload/check/{uuid}', [HandoffImageUploadController::class, 'checkUpload'])
        ->name('handoff-image-upload.check');
});
