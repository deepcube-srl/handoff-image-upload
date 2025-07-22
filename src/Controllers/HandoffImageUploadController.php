<?php

namespace Se09deluca\HandoffImageUpload\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class HandoffImageUploadController extends Controller
{
    /**
     * Generate a unique UUID for the image upload session
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function generateUuid()
    {
        $uuid = Str::uuid()->toString();

        // Store the UUID in the session or cache if needed
        session()->put('handoff_image_upload_uuid', $uuid);

        return response()->json([
            'uuid' => $uuid,
        ]);
    }

    /**
     * Show the camera page for mobile devices
     *
     * @param string $uuid
     * @return \Illuminate\View\View
     */
    public function showCameraPage($uuid)
    {
        return view('handoff-image-upload::camera', [
            'uuid' => $uuid,
        ]);
    }

    /**
     * Handle the image upload from mobile devices
     *
     * @param \Illuminate\Http\Request $request
     * @param string $uuid
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadImage(Request $request, $uuid)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|max:10240', // Max 10MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $image = $request->file('image');
        $path = $image->store('handoff-images', 'public');

        // Store the image path in the cache associated with the UUID
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
     * @param string $uuid
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
