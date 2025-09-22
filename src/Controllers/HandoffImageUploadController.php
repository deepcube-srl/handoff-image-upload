<?php

namespace Deepcube\HandoffImageUpload\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class HandoffImageUploadController extends Controller
{
    /**
     * Generate a unique UUID for the image upload session
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function generateUuid(Request $request)
    {
        $uuid = Str::uuid()->toString();
        $accessType = $request->input('access_type', 'qr'); // 'qr' or 'direct'

        // Store the UUID and access type in the session and cache
        session()->put('handoff_image_upload_uuid', $uuid);

        // Store access type information in cache for later retrieval
        cache()->put('handoff_access_type_' . $uuid, $accessType, 3600); // Cache for 1 hour

        return response()->json([
            'uuid' => $uuid,
            'access_type' => $accessType,
        ]);
    }

    /**
     * Show the camera page for mobile devices
     *
     * @param  string  $uuid
     * @return \Illuminate\View\View
     */
    public function showCameraPage($uuid)
    {
        // Retrieve access type from cache
        $accessType = cache()->get('handoff_access_type_' . $uuid, 'qr');

        return view('handoff-image-upload::camera', [
            'uuid' => $uuid,
            'accessType' => $accessType,
        ]);
    }

    /**
     * Handle the image upload from mobile devices
     *
     * @param  string  $uuid
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadImage(Request $request, $uuid)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|file|mimes:jpeg,png,jpg,gif|max:10240', // Max 10MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $image = $request->file('image');

        if (! $image || ! $image->isValid()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing image file',
                'error' => 'No valid image file was uploaded',
            ], 400);
        }

        try {
            $path = $image->store('handoff-images/tmp', 'public');

            if (! $path) {
                throw new \Exception('Failed to store image file');
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save image',
                'error' => $e->getMessage(),
            ], 500);
        }

        // Store the image path and URL in the cache associated with the UUID
        // This allows the main page to check if an image has been uploaded
        cache()->put('handoff_image_upload_' . $uuid, [
            'path' => $path,
            'url' => Storage::url($path),
        ], 3600); // Cache for 1 hour

        return response()->json([
            'success' => true,
            'path' => $path,
            'url' => Storage::url($path),
        ]);
    }

    /**
     * Check if an image has been uploaded for a given UUID
     *
     * @param  string  $uuid
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkUpload($uuid)
    {
        $imageData = cache()->get('handoff_image_upload_' . $uuid);

        if ($imageData) {
            return response()->json([
                'success' => true,
                'uploaded' => true,
                'path' => $imageData['path'],
                'url' => $imageData['url'],
            ]);
        }

        return response()->json([
            'success' => true,
            'uploaded' => false,
        ]);
    }
}
