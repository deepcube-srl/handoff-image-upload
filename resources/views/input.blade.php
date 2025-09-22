<div
    x-data="{
        state: $wire.entangle('{{ $getStatePath() }}'),
        imagePath: '',
        imageUrl: '',
        originalName: '',
        isMobile: false,
        showChoiceModal: false,

        init() {
            // Detect if current device is mobile
            this.isMobile = this.detectMobileDevice();

            // Initialize image URL from existing state if present
            this.updateImageFromState();

            // Watch for state changes to handle async loading from database
            this.$watch('state', () => {
                this.updateImageFromState();
            });

            // Listen for the handoff-image-uploaded event
            document.addEventListener('handoff-image-uploaded', (event) => {
                this.imagePath = event.detail.path;
                this.imageUrl = event.detail.url;
                this.state = this.imagePath;

                // Original filename handling removed
            });
        },

        updateImageFromState() {
            if (this.state) {
                this.imagePath = this.state;
                // Generate URL from path using Laravel's storage URL
                this.imageUrl = '{{ \Illuminate\Support\Facades\Storage::disk("public")->url("") }}' + this.state;
            } else {
                this.imagePath = '';
                this.imageUrl = '';
            }
        },

        detectMobileDevice() {
            // Check for mobile device using multiple methods
            const userAgent = navigator.userAgent.toLowerCase();
            const isMobileUA = /android|webos|iphone|ipad|ipod|blackberry|iemobile|opera mini/i.test(userAgent);
            const isTouchDevice = 'ontouchstart' in window || navigator.maxTouchPoints > 0;
            const hasCamera = navigator.mediaDevices && navigator.mediaDevices.getUserMedia;

            return isMobileUA && isTouchDevice && hasCamera;
        },

        handleUploadClick() {
            if (this.isMobile) {
                this.showChoiceModal = true;
            } else {
                this.showQRCode();
            }
        },

        async chooseCurrentDevice() {
            this.showChoiceModal = false;

            try {
                // First, check camera permission
                if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                    // Request camera access to trigger permission prompt
                    const stream = await navigator.mediaDevices.getUserMedia({
                        video: { facingMode: 'environment' },
                        audio: false
                    });
                    stream.getTracks().forEach(track => track.stop()); // Stop immediately

                    // If camera access is granted, generate UUID and redirect to camera page
                    const response = await fetch('/api/handoff-image-upload/generate-uuid', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            access_type: 'direct'
                        })
                    });

                    const data = await response.json();
                    const uuid = data.uuid;

                    // DO NOT START POLLING HERE - it causes race conditions
                    // The camera page will handle its own upload process
                    // Only QR code flow should use polling

                    // Redirect to camera page
                    window.location.href = `/handoff-image-upload/camera/${uuid}`;
                } else {
                    // Fallback to file picker if camera API not available
                    this.openCameraOrFilePicker();
                }
            } catch (error) {
                console.log('Camera access denied or not available, using file picker fallback');
                // Fallback to file picker
                this.openCameraOrFilePicker();
            }
        },

        chooseExternalDevice() {
            this.showChoiceModal = false;
            this.showQRCode();
        },

        async openCameraOrFilePicker() {
            // Create a file input for fallback
            const fileInput = document.createElement('input');
            fileInput.type = 'file';
            fileInput.accept = 'image/*';
            fileInput.capture = 'environment'; // Prefer camera

            fileInput.onchange = async (event) => {
                const file = event.target.files[0];
                if (file) {
                    await this.uploadImageFile(file);
                }
            };

            // Try to access camera directly first
            try {
                if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                    // Check if we can access the camera
                    const stream = await navigator.mediaDevices.getUserMedia({
                        video: { facingMode: 'environment' },
                        audio: false
                    });
                    stream.getTracks().forEach(track => track.stop()); // Stop immediately

                    // If camera access is successful, trigger file input with camera preference
                    fileInput.click();
                } else {
                    // Fallback to file picker only
                    fileInput.removeAttribute('capture');
                    fileInput.click();
                }
            } catch (error) {
                console.log('Camera access denied or not available, using file picker');
                fileInput.removeAttribute('capture');
                fileInput.click();
            }
        },

        async uploadImageFile(file) {
            try {
                // Generate UUID first
                const response = await fetch('/api/handoff-image-upload/generate-uuid', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        access_type: 'direct'
                    })
                });

                const data = await response.json();
                const uuid = data.uuid;

                // Upload the file
                const formData = new FormData();
                formData.append('image', file);

                const uploadResponse = await fetch(`/api/handoff-image-upload/upload/${uuid}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: formData
                });

                const uploadData = await uploadResponse.json();

                if (uploadData.success) {
                    // Emit the event with the image data
                    const event = new CustomEvent('handoff-image-uploaded', {
                        detail: {
                            path: uploadData.path,
                            url: uploadData.url,
                            originalName: uploadData.original_name
                        }
                    });
                    document.dispatchEvent(event);
                } else {
                    console.error('Upload failed:', uploadData.errors);
                    alert('{!! addslashes(__('handoff-image-upload::handoff-image-upload.upload_error')) !!}');
                }
            } catch (error) {
                console.error('Error uploading image:', error);
                alert('{!! addslashes(__('handoff-image-upload::handoff-image-upload.upload_error')) !!}');
            }
        },

        async showQRCode() {
            try {
                // Show modal
                const modal = document.getElementById('handoff-image-upload-modal');
                const qrcodeContainer = document.getElementById('handoff-image-upload-qrcode');
                const statusElement = document.getElementById('handoff-image-upload-status');

                modal.classList.remove('hidden');

                // Generate UUID
                const response = await fetch('/api/handoff-image-upload/generate-uuid', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        access_type: 'qr'
                    })
                });

                const data = await response.json();
                const uuid = data.uuid;

                // Generate QR code URL
                const url = `{{ config('app.url') }}/handoff-image-upload/camera/${uuid}`;

                // Generate QR code
                QRCode.toDataURL(url, { width: 200 }, function(error, dataURL) {
                    if (error) {
                        console.error('Error generating QR code:', error);
                        statusElement.textContent = '{!! addslashes(__('handoff-image-upload::handoff-image-upload.qr_generation_error')) !!}';
                        statusElement.classList.remove('hidden');
                    } else {
                        // Create an image element and append it to the container
                        const img = document.createElement('img');
                        img.src = dataURL;
                        img.className = 'mx-auto';
                        qrcodeContainer.innerHTML = ''; // Clear any existing content
                        qrcodeContainer.appendChild(img);
                    }
                });

                // Start checking for uploaded image
                this.startCheckingForUpload(uuid);
            } catch (error) {
                console.error('Error:', error);
                const statusElement = document.getElementById('handoff-image-upload-status');
                statusElement.textContent = '{!! addslashes(__('handoff-image-upload::handoff-image-upload.generic_error')) !!}';
                statusElement.classList.remove('hidden');
            }
        },

        startCheckingForUpload(uuid) {
            // Clear any existing interval and reset the stored UUID
            if (window.uploadCheckInterval) {
                clearInterval(window.uploadCheckInterval);
                window.uploadCheckInterval = null;
            }

            // Store the current UUID being checked to prevent race conditions
            window.currentUploadUuid = uuid;

            console.log('Starting upload check for UUID:', uuid);

            // Check every 2 seconds if an image has been uploaded
            window.uploadCheckInterval = setInterval(async () => {
                try {
                    // Double-check that we're still checking for the correct UUID
                    if (window.currentUploadUuid !== uuid) {
                        console.log('UUID mismatch detected, stopping polling for UUID:', uuid);
                        clearInterval(window.uploadCheckInterval);
                        window.uploadCheckInterval = null;
                        return;
                    }

                    const response = await fetch(`/api/handoff-image-upload/check/${uuid}`);
                    const data = await response.json();

                    console.log('Upload check response for UUID', uuid, ':', data);

                    if (data.success && data.uploaded) {
                        // Image has been uploaded
                        clearInterval(window.uploadCheckInterval);
                        window.uploadCheckInterval = null;
                        window.currentUploadUuid = null;

                        // Update the status
                        const statusElement = document.getElementById('handoff-image-upload-status');
                        if (statusElement) {
                            statusElement.textContent = '{!! addslashes(__('handoff-image-upload::handoff-image-upload.image_uploaded_successfully')) !!}';
                            statusElement.classList.remove('hidden');
                        }

                        // Emit an event with the image data
                        const event = new CustomEvent('handoff-image-uploaded', {
                            detail: {
                                path: data.path,
                                url: data.url,
                                originalName: data.original_name
                            }
                        });
                        document.dispatchEvent(event);

                        const qrcodeScanningContainer = document.getElementById('qr-code-scanning-mode-container');
                        qrcodeScanningContainer.remove();

                        // Close the modal after a delay
                        setTimeout(() => {
                            const modal = document.getElementById('handoff-image-upload-modal');
                            const qrcodeContainer = document.getElementById('handoff-image-upload-qrcode');
                            if (modal) modal.classList.add('hidden');
                            if (qrcodeContainer) qrcodeContainer.innerHTML = '';
                            if (statusElement) statusElement.classList.add('hidden');
                        }, 2000);
                    }
                } catch (error) {
                    console.error('Error checking upload status for UUID', uuid, ':', error);
                }
            }, 2000);
        },

        handleRemoveClick() {
            // Confirm removal with user
            if (confirm('{!! addslashes(__('handoff-image-upload::handoff-image-upload.confirm_remove_image')) !!}')) {
                // Clear the image state
                this.imagePath = '';
                this.imageUrl = '';
                this.state = null;

                // The state change will trigger the backend removal logic
                // through the Livewire state binding
            }
        }
    }"
>
    <div class="space-y-4 max-h-[300px] overflow-hidden">
        <!-- Preview container -->
        <div class="relative">
            <!-- Image preview when image exists -->
            <div x-show="imageUrl" class="relative">
                <div class="fi-fo-field-wrp">
                    <div class="fi-fo-field-wrp-label">
                        <label class="fi-fo-field-wrp-label-text text-sm font-medium text-gray-950 dark:text-white">
                            {{ __('handoff-image-upload::handoff-image-upload.image_preview') }}
                        </label>
                    </div>
                    <div class="fi-fo-field-wrp-hint">
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ __('handoff-image-upload::handoff-image-upload.current_uploaded_image') }}
                        </p>
                    </div>
                </div>
                <div class="mt-2 p-4 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-800 relative">
                    <img x-bind:src="imageUrl" class="max-w-full h-48 object-contain mx-auto rounded-lg" />
                    <!-- Overlay upload button for existing image -->
                    <div class="absolute inset-0 flex items-center justify-center opacity-0 hover:opacity-100 transition-opacity duration-300 bg-black/50 rounded-lg">
                        <div class="flex flex-col space-y-2">
                            <x-filament::button
                                @click="handleUploadClick()"
                                icon="heroicon-m-camera"
                                size="lg"
                                color="white"
                            >
                                {{ __('handoff-image-upload::handoff-image-upload.update_image') }}
                            </x-filament::button>
                            <x-filament::button
                                @click="handleRemoveClick()"
                                icon="heroicon-m-trash"
                                size="lg"
                                color="danger"
                            >
                                {{ __('handoff-image-upload::handoff-image-upload.remove_image') }}
                            </x-filament::button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty state when no image -->
            <div x-show="!imageUrl" class="relative">
                <div class="fi-fo-field-wrp">
                    <div class="fi-fo-field-wrp-label">
                        <label class="fi-fo-field-wrp-label-text text-sm font-medium text-gray-950 dark:text-white">
                            {{ __('handoff-image-upload::handoff-image-upload.image_preview') }}
                        </label>
                    </div>
                    <div class="fi-fo-field-wrp-hint">
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ __('handoff-image-upload::handoff-image-upload.no_image_uploaded') }}
                        </p>
                    </div>
                </div>
                <div class="mt-2 p-8 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-800">
                    <div class="text-center p-6">
                        <svg class="mx-auto h-8 w-12 text-gray-400 dark:text-gray-500" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">{{ __('handoff-image-upload::handoff-image-upload.no_image') }}</h3>
                        <p class="mt-1 mb-2 text-sm text-gray-500 dark:text-gray-400">{{ __('handoff-image-upload::handoff-image-upload.upload_image_preview') }}</p>
                        <!-- Upload button for empty state -->
                        <div class="mt-4">
                            <x-filament::button
                                @click="handleUploadClick()"
                                icon="heroicon-m-camera"
                                size="lg"
                            >
                                {{ __('handoff-image-upload::handoff-image-upload.upload_image') }}
                            </x-filament::button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Choice Modal for Mobile Devices -->
    <div x-show="showChoiceModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" x-transition>
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg max-w-md w-full mx-4">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">{{ __('handoff-image-upload::handoff-image-upload.choose_upload_method') }}</h3>
                <button type="button" @click="showChoiceModal = false" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="space-y-4">
                <p class="text-gray-600 dark:text-gray-200 mb-4">{{ __('handoff-image-upload::handoff-image-upload.upload_description') }}</p>

                <div class="space-y-3">
                    <button @click="chooseCurrentDevice()" class="w-full bg-blue-500 hover:bg-blue-700 dark:bg-blue-600 dark:hover:bg-blue-500 dark:text-white font-bold py-3 px-4 rounded-lg flex items-center justify-center space-x-2">
                        <span>{{ __('handoff-image-upload::handoff-image-upload.capture_upload_device') }}</span>
                    </button>

                    <button @click="chooseExternalDevice()" class="w-full bg-gray-500 hover:bg-gray-700 dark:bg-gray-600 dark:hover:bg-gray-500 dark:text-white font-bold py-3 px-4 rounded-lg flex items-center justify-center space-x-2">
                        <span>{{ __('handoff-image-upload::handoff-image-upload.use_qr_code') }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div id="handoff-image-upload-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 hidden">
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg max-w-md w-full">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">{{ __('handoff-image-upload::handoff-image-upload.scan_qr_code') }}</h3>
                <button type="button" id="handoff-image-upload-close" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="text-center mb-4" id="qr-code-scanning-mode-container">
                <p class="mb-4 text-gray-700 dark:text-gray-200">{{ __('handoff-image-upload::handoff-image-upload.scan_qr_instruction') }}</p>
                <div id="handoff-image-upload-qrcode" class="mx-auto"></div>
            </div>
            <div id="handoff-image-upload-status" class="mt-4 text-center text-gray-700 dark:text-gray-200 font-medium hidden"></div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.1/build/qrcode.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Setup close button for QR code modal
        const closeBtn = document.getElementById('handoff-image-upload-close');
        const modal = document.getElementById('handoff-image-upload-modal');
        const qrcodeContainer = document.getElementById('handoff-image-upload-qrcode');
        const statusElement = document.getElementById('handoff-image-upload-status');

        closeBtn.addEventListener('click', function() {
            modal.classList.add('hidden');
            qrcodeContainer.innerHTML = '';
            statusElement.classList.add('hidden');

            // Properly clean up polling and UUID tracking
            if (window.uploadCheckInterval) {
                clearInterval(window.uploadCheckInterval);
                window.uploadCheckInterval = null;
            }
            window.currentUploadUuid = null;

            console.log('QR modal closed by user, polling stopped');
        });
    });
</script>
