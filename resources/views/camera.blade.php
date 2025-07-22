<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carica Immagine</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            max-width: 500px;
            margin: 0 auto;
        }
        .container {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        #video-container {
            width: 100%;
            max-width: 400px;
            margin-bottom: 20px;
        }
        #video {
            width: 100%;
            border-radius: 8px;
            border: 1px solid #ccc;
        }
        #preview-container {
            width: 100%;
            max-width: 400px;
            margin-bottom: 20px;
            display: none;
        }
        #preview {
            width: 100%;
            border-radius: 8px;
            border: 1px solid #ccc;
        }
        .button {
            background-color: #4CAF50;
            border: none;
            color: white;
            padding: 10px 20px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            margin: 4px 2px;
            cursor: pointer;
            border-radius: 4px;
        }
        .button-secondary {
            background-color: #f44336;
        }
        .button-container {
            display: flex;
            justify-content: space-between;
            width: 100%;
            max-width: 400px;
        }
        .status {
            margin-top: 20px;
            padding: 10px;
            border-radius: 4px;
            width: 100%;
            max-width: 400px;
            text-align: center;
        }
        .success {
            background-color: #dff0d8;
            color: #3c763d;
        }
        .error {
            background-color: #f2dede;
            color: #a94442;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Carica Immagine</h1>
        <p>Scatta una foto per caricarla</p>

        <div id="video-container">
            <video id="video" autoplay playsinline></video>
        </div>

        <div id="preview-container">
            <img id="preview" src="" alt="Preview">
        </div>

        <div class="button-container">
            <button id="capture-btn" class="button">Scatta Foto</button>
            <button id="switch-camera-btn" class="button">Cambia Camera</button>
        </div>

        <div id="confirm-container" style="display: none;" class="button-container">
            <button id="confirm-btn" class="button">Conferma</button>
            <button id="retake-btn" class="button button-secondary">Riprova</button>
        </div>

        <div id="status" class="status" style="display: none;"></div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const uuid = '{{ $uuid }}';
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

            let stream = null;
            let facingMode = 'environment'; // Start with back camera
            let imageCapture = null;
            let capturedBlob = null;

            // Check camera permissions
            async function checkCameraPermission() {
                // Check if the Permissions API is supported
                if (navigator.permissions && navigator.permissions.query) {
                    try {
                        const permissionStatus = await navigator.permissions.query({ name: 'camera' });

                        if (permissionStatus.state === 'granted') {
                            // Permission already granted, start camera
                            startCamera();
                        } else if (permissionStatus.state === 'prompt') {
                            // Permission has not been requested yet, show message and button
                            showStatus('Per utilizzare questa funzionalità è necessario concedere l\'accesso alla fotocamera.', '');

                            // Create a permission request button
                            const permissionBtn = document.createElement('button');
                            permissionBtn.textContent = 'Consenti accesso alla fotocamera';
                            permissionBtn.className = 'button';
                            permissionBtn.style.marginTop = '10px';

                            permissionBtn.addEventListener('click', () => {
                                // Remove the button when clicked
                                if (permissionBtn.parentNode) {
                                    permissionBtn.parentNode.removeChild(permissionBtn);
                                }
                                // Try to start the camera, which will trigger the permission prompt
                                startCamera();
                            });

                            // Add the button to the status element
                            statusElement.appendChild(permissionBtn);
                        } else if (permissionStatus.state === 'denied') {
                            // Permission was denied
                            showStatus('L\'accesso alla fotocamera è stato negato. Per utilizzare questa funzionalità, concedi l\'accesso alla fotocamera nelle impostazioni del browser.', 'error');
                        }

                        // Listen for permission changes
                        permissionStatus.addEventListener('change', () => {
                            if (permissionStatus.state === 'granted') {
                                startCamera();
                            } else if (permissionStatus.state === 'denied') {
                                showStatus('L\'accesso alla fotocamera è stato negato. Per utilizzare questa funzionalità, concedi l\'accesso alla fotocamera nelle impostazioni del browser.', 'error');
                            }
                        });
                    } catch (error) {
                        console.error('Error checking camera permission:', error);
                        // Fallback to direct camera access if permission query fails
                        startCamera();
                    }
                } else {
                    // Permissions API not supported, fallback to direct camera access
                    startCamera();
                }
            }

            // Start the camera
            async function startCamera() {
                try {
                    if (stream) {
                        stream.getTracks().forEach(track => track.stop());
                    }

                    stream = await navigator.mediaDevices.getUserMedia({
                        video: { facingMode: facingMode },
                        audio: false
                    });

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
                        showStatus('Accesso alla fotocamera negato. Per utilizzare questa funzionalità, concedi l\'accesso alla fotocamera nelle impostazioni del browser.', 'error');
                    } else if (error.name === 'NotFoundError' || error.name === 'DevicesNotFoundError') {
                        showStatus('Nessuna fotocamera trovata sul dispositivo.', 'error');
                    } else if (error.name === 'NotReadableError' || error.name === 'TrackStartError') {
                        showStatus('La fotocamera è già in uso da un\'altra applicazione.', 'error');
                    } else if (error.name === 'OverconstrainedError' || error.name === 'ConstraintNotSatisfiedError') {
                        showStatus('La fotocamera non supporta i requisiti richiesti.', 'error');
                    } else {
                        showStatus('Errore nell\'accesso alla fotocamera. Assicurati di aver concesso i permessi.', 'error');
                    }
                }
            }

            // Switch between front and back camera
            switchCameraBtn.addEventListener('click', function() {
                facingMode = facingMode === 'environment' ? 'user' : 'environment';
                startCamera();
            });

            // Capture photo
            captureBtn.addEventListener('click', async function() {
                try {
                    const blob = await imageCapture.takePhoto();
                    capturedBlob = blob;

                    previewElement.src = URL.createObjectURL(blob);

                    videoContainer.style.display = 'none';
                    previewContainer.style.display = 'block';
                    buttonContainer.style.display = 'none';
                    confirmContainer.style.display = 'flex';
                } catch (error) {
                    console.error('Error capturing photo:', error);
                    showStatus('Errore durante lo scatto della foto.', 'error');
                }
            });

            // Retake photo
            retakeBtn.addEventListener('click', function() {
                startCamera();
            });

            // Confirm and upload photo
            confirmBtn.addEventListener('click', async function() {
                if (!capturedBlob) {
                    showStatus('Nessuna immagine da caricare.', 'error');
                    return;
                }

                const formData = new FormData();
                formData.append('image', capturedBlob, 'image.jpg');

                try {
                    showStatus('Caricamento in corso...', '');

                    const response = await fetch(`/api/handoff-image-upload/upload/${uuid}`, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    });

                    const result = await response.json();

                    if (response.ok && result.success) {
                        showStatus('Immagine caricata con successo!', 'success');
                        // Close the window after a delay
                        setTimeout(() => {
                            window.close();
                        }, 3000);
                    } else {
                        showStatus('Errore durante il caricamento dell\'immagine.', 'error');
                    }
                } catch (error) {
                    console.error('Error uploading image:', error);
                    showStatus('Errore durante il caricamento dell\'immagine.', 'error');
                }
            });

            function showStatus(message, type) {
                statusElement.textContent = message;
                statusElement.style.display = 'block';

                statusElement.className = 'status';
                if (type) {
                    statusElement.classList.add(type);
                }
            }

            // Check camera permissions and start the camera when the page loads
            checkCameraPermission();
        });
    </script>
</body>
</html>
