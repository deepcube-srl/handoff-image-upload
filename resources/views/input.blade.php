<div
    x-data="{
        state: $wire.entangle('{{ $getStatePath() }}'),
        imagePath: '',
        imageUrl: '',

        init() {
            // Listen for the handoff-image-uploaded event
            document.addEventListener('handoff-image-uploaded', (event) => {
                this.imagePath = event.detail.path;
                this.imageUrl = event.detail.url;
                this.state = this.imagePath;

                // Call the Livewire method to update the state
                $wire.call('setImagePath', this.imagePath);
            });
        }
    }"
>
    <div class="space-y-2">
        <div class="flex items-center space-x-2">
            <x-filament::button id="handoff-image-upload-btn">
                Carica da mobile
            </x-filament::button>

            <div x-show="imageUrl" class="text-sm text-gray-500">
                Immagine caricata
            </div>
        </div>

        <div x-show="imageUrl" class="mt-2">
            <img x-bind:src="imageUrl" class="max-w-xs rounded-lg border border-gray-300" />
        </div>
    </div>

    <div id="handoff-image-upload-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden">
        <div class="bg-white p-6 rounded-lg shadow-lg max-w-md w-full">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium">Scansiona il QR Code</h3>
                <button id="handoff-image-upload-close" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="text-center mb-4">
                <p class="mb-4">Scansiona questo QR Code con il tuo dispositivo mobile per caricare un'immagine</p>
                <div id="handoff-image-upload-qrcode" class="mx-auto"></div>
            </div>
            <div id="handoff-image-upload-status" class="mt-4 text-center hidden"></div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.1/build/qrcode.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const button = document.getElementById('handoff-image-upload-btn');
        const modal = document.getElementById('handoff-image-upload-modal');
        const closeBtn = document.getElementById('handoff-image-upload-close');
        const qrcodeContainer = document.getElementById('handoff-image-upload-qrcode');
        const statusElement = document.getElementById('handoff-image-upload-status');

        let uploadCheckInterval = null;

        button.addEventListener('click', async function() {
            try {
                // Show modal
                modal.classList.remove('hidden');

                // Generate UUID
                const response = await fetch('/api/handoff-image-upload/generate-uuid', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                const data = await response.json();
                const uuid = data.uuid;

                // Generate QR code URL
                const url = `http://192.168.1.19:8000/handoff-image-upload/camera/${uuid}`;
console.log(url)
                // Generate QR code
                QRCode.toDataURL(url, { width: 200 }, function(error, dataURL) {
                    if (error) {
                        console.error('Error generating QR code:', error);
                        statusElement.textContent = 'Errore nella generazione del QR code';
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
                startCheckingForUpload(uuid);
            } catch (error) {
                console.error('Error:', error);
                statusElement.textContent = 'Si è verificato un errore';
                statusElement.classList.remove('hidden');
            }
        });

        closeBtn.addEventListener('click', function() {
            modal.classList.add('hidden');
            qrcodeContainer.innerHTML = '';
            statusElement.classList.add('hidden');

            if (uploadCheckInterval) {
                clearInterval(uploadCheckInterval);
                uploadCheckInterval = null;
            }
        });

        function startCheckingForUpload(uuid) {
            // Clear any existing interval
            if (uploadCheckInterval) {
                clearInterval(uploadCheckInterval);
            }

            // Check every 2 seconds if an image has been uploaded
            uploadCheckInterval = setInterval(async function() {
                try {
                    const response = await fetch(`/api/handoff-image-upload/check/${uuid}`);
                    const data = await response.json();

                    if (data.success && data.uploaded) {
                        // Image has been uploaded
                        clearInterval(uploadCheckInterval);
                        uploadCheckInterval = null;

                        // Update the status
                        statusElement.textContent = 'Immagine caricata con successo!';
                        statusElement.classList.remove('hidden');

                        // Emit an event with the image data
                        const event = new CustomEvent('handoff-image-uploaded', {
                            detail: {
                                path: data.path,
                                url: data.url
                            }
                        });
                        document.dispatchEvent(event);

                        // Close the modal after a delay
                        setTimeout(function() {
                            modal.classList.add('hidden');
                            qrcodeContainer.innerHTML = '';
                            statusElement.classList.add('hidden');
                        }, 3000);
                    }
                } catch (error) {
                    console.error('Error checking upload status:', error);
                }
            }, 2000);
        }
    });
</script>
