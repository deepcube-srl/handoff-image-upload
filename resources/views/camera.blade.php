<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            max-width: 500px;
            margin: 0 auto;
            height: 100vh;
            overflow-x: hidden;
            background-color: #ffffff;
            color: #000000;
        }

        .container {
            display: flex;
            flex-direction: column;
            align-items: center;
            height: calc(100vh - 40px);
            position: relative;
            padding-bottom: 100px;
            box-sizing: border-box;
        }

        #video-container {
            width: 100%;
            max-width: 400px;
            margin-bottom: 20px;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        #video {
            width: 100%;
            max-height: 70vh;
            border-radius: 8px;
            border: 1px solid #d1d5db;
            object-fit: cover;
        }

        #preview-container {
            width: 100%;
            max-width: 400px;
            margin-bottom: 20px;
            display: none;
            flex: 1;
            align-items: center;
            justify-content: center;
        }

        #preview {
            width: 100%;
            max-height: 70vh;
            border-radius: 8px;
            border: 1px solid #d1d5db;
            object-fit: cover;
        }

        .button {
            flex: 1;
            background-color: #059669;
            border: none;
            color: white;
            padding: 10px 20px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            margin: 4px;
            cursor: pointer;
            border-radius: 4px;
            transition: background-color 0.2s;
        }

        .button:hover {
            background-color: #047857;
        }

        .button-secondary {
            background-color: #dc2626;
        }

        .button-secondary:hover {
            background-color: #b91c1c;
        }

        .button-container {
            display: flex;
            flex-direction: column;
            width: 90%;
            max-width: 400px;
            position: fixed;
            bottom: 20px;
            background: #f3f4f6;
            border: 1px solid #d1d5db;
            padding: 10px;
            border-radius: 8px;
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }

        .button-row {
            display: flex;
            justify-content: space-between;
        }

        .status {
            margin-bottom: 10px;
            padding: 10px;
            border-radius: 4px;
            text-align: center;
        }

        .success {
            background-color: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .error {
            background-color: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        #switch-camera-btn {
            background-color: #0891b2;
        }

        #switch-camera-btn:hover {
            background-color: #0e7490;
        }

        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            body {
                background-color: #111827;
                color: #f9fafb;
            }

            #video, #preview {
                border-color: #4b5563;
            }

            .button-container {
                background: #374151;
                border-color: #4b5563;
                box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.3);
            }

            .success {
                background-color: #064e3b;
                color: #a7f3d0;
                border-color: #065f46;
            }

            .error {
                background-color: #7f1d1d;
                color: #fca5a5;
                border-color: #991b1b;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <h1>{{ config('app.name') }}</h1>
    <p>{{ __('handoff-image-upload::handoff-image-upload.take_photo_instruction') }}</p>

    <div id="video-container">
        <video id="video" autoplay playsinline></video>
    </div>

    <div id="preview-container">
        <img id="preview" src="" alt="Preview">
    </div>

    <div class="button-container">
        <div id="status" class="status" style="display: none;"></div>
        <div class="button-row">
            <button id="switch-camera-btn" class="button">{{ __('handoff-image-upload::handoff-image-upload.switch_camera') }}</button>
            <button id="capture-btn" class="button">{{ __('handoff-image-upload::handoff-image-upload.take_photo') }}</button>
        </div>
    </div>

    <div id="confirm-container" style="display: none;" class="button-container">
        <div id="status-confirm" class="status" style="display: none;"></div>
        <div class="button-row">
            <button id="retake-btn" class="button button-secondary">{{ __('handoff-image-upload::handoff-image-upload.retry') }}</button>
            <button id="confirm-btn" class="button">{{ __('handoff-image-upload::handoff-image-upload.confirm') }}</button>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const uuid = '{{ $uuid }}';
        const accessType = '{{ $accessType }}'; // 'qr' or 'direct'
        const videoElement = document.getElementById('video');
        const videoContainer = document.getElementById('video-container');
        const previewElement = document.getElementById('preview');
        const previewContainer = document.getElementById('preview-container');
        const captureBtn = document.getElementById('capture-btn');
        const switchCameraBtn = document.getElementById('switch-camera-btn');
        const confirmBtn = document.getElementById('confirm-btn');
        const retakeBtn = document.getElementById('retake-btn');
        const confirmContainer = document.getElementById('confirm-container');
        const buttonContainer = document.querySelector('.button-container');
        const statusElement = document.getElementById('status');
        const statusConfirmElement = document.getElementById('status-confirm');

        let stream = null;
        let facingMode = 'environment'; // Start with back camera
        let imageCapture = null;
        let capturedBlob = null;

        // Check if UUID has already been used
        async function checkUuidUsage() {
            try {
                const response = await fetch(`/api/handoff-image-upload/check/${uuid}`);
                const result = await response.json();

                if (result.success && result.uploaded) {
                    // UUID has already been used, disable camera functionality
                    showStatus('{!! addslashes(__('handoff-image-upload::handoff-image-upload.uuid_already_used')) !!}', 'error');

                    // Hide all camera controls
                    videoContainer.style.display = 'none';
                    previewContainer.style.display = 'none';
                    buttonContainer.querySelector('.button-row').style.display = 'none';
                    confirmContainer.querySelector('.button-row').style.display = 'none';

                    return false; // UUID already used
                }
                return true; // UUID available for use
            } catch (error) {
                console.error('Error checking UUID usage:', error);
                showStatus('{!! addslashes(__('handoff-image-upload::handoff-image-upload.uuid_check_error')) !!}', 'error');
                return false;
            }
        }

        // Check camera permissions and request if needed
        async function checkCameraPermission() {
            // First, try to directly request camera access
            // This is the most reliable way to trigger permission prompts
            try {
                // Show initial message
                showStatus('{!! addslashes(__('handoff-image-upload::handoff-image-upload.requesting_camera_access')) !!}', '');

                // Attempt to get camera access - this will trigger the permission prompt if needed
                const testStream = await navigator.mediaDevices.getUserMedia({
                    video: {facingMode: facingMode},
                    audio: false
                });

                // If we get here, permission was granted
                // Stop the test stream immediately
                testStream.getTracks().forEach(track => track.stop());

                // Now start the actual camera
                startCamera();

            } catch (error) {
                console.error('Error requesting camera permission:', error);

                if (error.name === 'NotAllowedError' || error.name === 'PermissionDeniedError') {
                    // Permission was denied, show button to try again
                    showStatus('{!! addslashes(__('handoff-image-upload::handoff-image-upload.camera_access_denied')) !!}', 'error');

                    // Create a retry button
                    const retryBtn = document.createElement('button');
                    retryBtn.textContent = '{!! addslashes(__('handoff-image-upload::handoff-image-upload.request_camera_access')) !!}';
                    retryBtn.className = 'button';
                    retryBtn.style.marginTop = '10px';

                    retryBtn.addEventListener('click', () => {
                        // Remove the button when clicked
                        if (retryBtn.parentNode) {
                            retryBtn.parentNode.removeChild(retryBtn);
                        }
                        // Try again
                        checkCameraPermission();
                    });

                    // Add the button to the status element
                    statusElement.appendChild(retryBtn);

                } else if (error.name === 'NotFoundError' || error.name === 'DevicesNotFoundError') {
                    showStatus('{!! addslashes(__('handoff-image-upload::handoff-image-upload.no_camera_found')) !!}', 'error');
                } else if (error.name === 'NotReadableError' || error.name === 'TrackStartError') {
                    showStatus('{!! addslashes(__('handoff-image-upload::handoff-image-upload.camera_in_use')) !!}', 'error');
                } else if (error.name === 'OverconstrainedError' || error.name === 'ConstraintNotSatisfiedError') {
                    showStatus('{!! addslashes(__('handoff-image-upload::handoff-image-upload.camera_not_supported')) !!}', 'error');
                } else {
                    // For other errors, fall back to the Permissions API approach if available
                    await fallbackPermissionCheck();
                }
            }
        }

        // Fallback permission check using Permissions API
        async function fallbackPermissionCheck() {
            if (navigator.permissions && navigator.permissions.query) {
                try {
                    const permissionStatus = await navigator.permissions.query({name: 'camera'});

                    if (permissionStatus.state === 'granted') {
                        startCamera();
                    } else if (permissionStatus.state === 'prompt') {
                        showStatus('{!! addslashes(__('handoff-image-upload::handoff-image-upload.camera_permission_required')) !!}', '');

                        const permissionBtn = document.createElement('button');
                        permissionBtn.textContent = '{!! addslashes(__('handoff-image-upload::handoff-image-upload.allow_camera_access')) !!}';
                        permissionBtn.className = 'button';
                        permissionBtn.style.marginTop = '10px';

                        permissionBtn.addEventListener('click', () => {
                            if (permissionBtn.parentNode) {
                                permissionBtn.parentNode.removeChild(permissionBtn);
                            }
                            checkCameraPermission();
                        });

                        statusElement.appendChild(permissionBtn);
                    } else {
                        showStatus('{!! addslashes(__('handoff-image-upload::handoff-image-upload.camera_access_denied_settings')) !!}', 'error');
                    }

                    // Listen for permission changes
                    permissionStatus.addEventListener('change', () => {
                        if (permissionStatus.state === 'granted') {
                            startCamera();
                        }
                    });
                } catch (error) {
                    console.error('Error with fallback permission check:', error);
                    showStatus('{!! addslashes(__('handoff-image-upload::handoff-image-upload.permission_check_error')) !!}', 'error');
                }
            } else {
                // No Permissions API support, show manual instruction
                showStatus('{!! addslashes(__('handoff-image-upload::handoff-image-upload.browser_no_permission_support')) !!}', '');

                const manualBtn = document.createElement('button');
                manualBtn.textContent = '{!! addslashes(__('handoff-image-upload::handoff-image-upload.try_camera_access')) !!}';
                manualBtn.className = 'button';
                manualBtn.style.marginTop = '10px';

                manualBtn.addEventListener('click', () => {
                    if (manualBtn.parentNode) {
                        manualBtn.parentNode.removeChild(manualBtn);
                    }
                    startCamera();
                });

                statusElement.appendChild(manualBtn);
            }
        }

        // Start the camera
        async function startCamera() {
            try {
                if (stream) {
                    stream.getTracks().forEach(track => track.stop());
                }

                const constraints = {
                    video: {
                        facingMode: facingMode,
                        width: {ideal: 1920},
                        height: {ideal: 1080},
                        aspectRatio: {ideal: 16 / 9}
                    },
                    audio: false
                };

                stream = await navigator.mediaDevices.getUserMedia(constraints);

                videoElement.srcObject = stream;

                const track = stream.getVideoTracks()[0];
                imageCapture = new ImageCapture(track);

                videoContainer.style.display = 'block';
                previewContainer.style.display = 'none';
                buttonContainer.style.display = 'flex';
                confirmContainer.style.display = 'none';
                statusElement.style.display = 'none';
            } catch (error) {
                console.error('Error accessing camera:', error);

                if (error.name === 'NotAllowedError' || error.name === 'PermissionDeniedError') {
                    showStatus('{!! addslashes(__('handoff-image-upload::handoff-image-upload.camera_access_denied_settings')) !!}', 'error');
                } else if (error.name === 'NotFoundError' || error.name === 'DevicesNotFoundError') {
                    showStatus('{!! addslashes(__('handoff-image-upload::handoff-image-upload.no_camera_found')) !!}', 'error');
                } else if (error.name === 'NotReadableError' || error.name === 'TrackStartError') {
                    showStatus('{!! addslashes(__('handoff-image-upload::handoff-image-upload.camera_in_use')) !!}', 'error');
                } else if (error.name === 'OverconstrainedError' || error.name === 'ConstraintNotSatisfiedError') {
                    showStatus('{!! addslashes(__('handoff-image-upload::handoff-image-upload.camera_not_supported')) !!}', 'error');
                } else {
                    showStatus('{!! addslashes(__('handoff-image-upload::handoff-image-upload.camera_start_error')) !!}', 'error');
                }
            }
        }

        // Switch between front and back camera
        switchCameraBtn.addEventListener('click', function () {
            facingMode = facingMode === 'environment' ? 'user' : 'environment';
            startCamera();
        });

        // Capture photo with progressive fallback
        captureBtn.addEventListener('click', async function () {
            try {
                let blob = null;

                // Progressive fallback for photo capture
                // First try: Advanced settings with fillLightMode
                try {
                    let photoSettings = {};

                    // Configurazioni specifiche per fotocamera frontale
                    if (facingMode === 'user') {
                        photoSettings = {
                            imageHeight: 1080,
                            imageWidth: 1920,
                            fillLightMode: 'flash'  // Forza l'uso del flash/illuminazione
                        };
                    } else {
                        // Configurazioni per fotocamera posteriore
                        photoSettings = {
                            imageHeight: 1080,
                            imageWidth: 1920,
                            fillLightMode: 'auto'  // Migliora l'illuminazione automaticamente
                        };
                    }

                    blob = await imageCapture.takePhoto(photoSettings);
                    console.log('Photo captured with advanced settings');

                } catch (advancedError) {
                    console.warn('Advanced photo settings not supported, trying basic resolution settings:', advancedError);

                    // Second try: Basic resolution settings without fillLightMode
                    try {
                        const basicPhotoSettings = {
                            imageHeight: 1080,
                            imageWidth: 1920
                        };

                        blob = await imageCapture.takePhoto(basicPhotoSettings);
                        console.log('Photo captured with basic resolution settings');

                    } catch (basicError) {
                        console.warn('Basic photo settings not supported, trying without settings:', basicError);

                        // Third try: No settings at all (maximum compatibility)
                        blob = await imageCapture.takePhoto();
                        console.log('Photo captured with default settings');
                    }
                }

                if (blob) {
                    capturedBlob = blob;

                    previewElement.src = URL.createObjectURL(blob);
                    console.log('Preview image created from blob');

                    videoContainer.style.display = 'none';
                    previewContainer.style.display = 'block';
                    buttonContainer.style.display = 'none';
                    confirmContainer.style.display = 'flex';
                } else {
                    console.error('Blob is null or undefined after photo capture');
                    throw new Error('Failed to capture photo with any method');
                }

            } catch (error) {
                console.error('Error capturing photo:', error);
                showStatus('{!! addslashes(__('handoff-image-upload::handoff-image-upload.photo_capture_failed')) !!}', 'error');
            }
        });

        // Retake photo
        retakeBtn.addEventListener('click', function () {
            startCamera();
        });

        // Confirm and upload photo
        confirmBtn.addEventListener('click', async function () {
            if (!capturedBlob) {
                console.error('No capturedBlob available');
                showStatus('{!! addslashes(__('handoff-image-upload::handoff-image-upload.no_image_to_upload')) !!}', 'error');
                return;
            }

            const formData = new FormData();
            formData.append('image', capturedBlob, 'image.jpg');

            // Log FormData contents
            for (let [key, value] of formData.entries()) {
                console.log('FormData entry:', key, value);
            }

            try {
                // Additional validation before starting fetch
                if (capturedBlob.size === 0) {
                    throw new Error('{!! addslashes(__('handoff-image-upload::handoff-image-upload.blob_empty_error')) !!}');
                }

                if (!capturedBlob.type || !capturedBlob.type.startsWith('image/')) {
                    console.warn('Blob type might be incorrect:', capturedBlob.type);
                }

                // Check network connectivity
                if (!navigator.onLine) {
                    throw new Error('{!! addslashes(__('handoff-image-upload::handoff-image-upload.no_internet_connection')) !!}');
                }

                showStatus('{!! addslashes(__('handoff-image-upload::handoff-image-upload.uploading')) !!}', '');

                const response = await fetch(`/api/handoff-image-upload/upload/${uuid}`, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                const status = await response.status;
                if (status === 200) {

                    const result = await response.json();

                    if (response.ok && result.success) {
                        // Hide only the button rows, keep the containers for status messages
                        const buttonRow = buttonContainer.querySelector('.button-row');
                        const confirmButtonRow = confirmContainer.querySelector('.button-row');
                        if (buttonRow) buttonRow.style.display = 'none';
                        if (confirmButtonRow) confirmButtonRow.style.display = 'none';

                        // Disable the camera by stopping all tracks
                        if (stream) {
                            stream.getTracks().forEach(track => track.stop());
                            stream = null;
                        }

                        // Clear the video source
                        videoElement.srcObject = null;

                        if (accessType === 'qr') {
                            // User came from QR Code (desktop) - show success message and conclusion
                            showStatus('{!! addslashes(__('handoff-image-upload::handoff-image-upload.upload_success_qr')) !!}', 'success');
                            // Don't auto-close, let user close manually
                        } else {
                            // User came from direct mobile access - go back to form
                            showStatus('{!! addslashes(__('handoff-image-upload::handoff-image-upload.upload_success_direct')) !!}', 'success');
                            setTimeout(() => {
                                // Try to go back to previous page
                                if (window.history.length > 1) {
                                    window.history.back();
                                } else {
                                    // Fallback: close the window
                                    window.close();
                                }
                            }, 2000);
                        }
                    } else {
                        const errorMsg = '{!! addslashes(__('handoff-image-upload::handoff-image-upload.image_upload_error_with_message')) !!}'.replace('{message}', result.message || result.error || '{!! addslashes(__('handoff-image-upload::handoff-image-upload.server_error')) !!}');
                        showStatus(`1 - ${errorMsg}.`, 'error');
                    }
                } else {
                    if (status >= 400 && status < 500) {
                        if (status === 413) {
                            showStatus('{!! addslashes(__('handoff-image-upload::handoff-image-upload.image_too_large')) !!}', 'error');
                            return;
                        }
                        showStatus('{!! addslashes(__('handoff-image-upload::handoff-image-upload.image_upload_general_error')) !!}', 'error');
                    }
                }
            } catch (error) {
                console.error('Error uploading image:', error);
                const errorMsg = '{!! addslashes(__('handoff-image-upload::handoff-image-upload.image_upload_error_with_message')) !!}'.replace('{message}', error.message || error.name || '{!! addslashes(__('handoff-image-upload::handoff-image-upload.network_connection_error')) !!}');
                showStatus(`${errorMsg}.`, 'error');
            }
        });

        function showStatus(message, type) {
            // Determine which status element to use based on which container is visible
            const activeStatusElement = confirmContainer.style.display === 'flex' ? statusConfirmElement : statusElement;
            const inactiveStatusElement = confirmContainer.style.display === 'flex' ? statusElement : statusConfirmElement;

            // Hide the inactive status element
            inactiveStatusElement.style.display = 'none';

            // Show message in the active status element
            activeStatusElement.textContent = message;
            activeStatusElement.style.display = 'block';

            activeStatusElement.className = 'status';
            if (type) {
                activeStatusElement.classList.add(type);
            }
        }

        // Check UUID usage first, then camera permissions and start the camera when the page loads
        async function initializeCamera() {
            const canUseCamera = await checkUuidUsage();
            if (canUseCamera) {
                checkCameraPermission();
            }
        }

        initializeCamera();
    });
</script>
</body>
</html>
