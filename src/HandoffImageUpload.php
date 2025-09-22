<?php

namespace Deepcube\HandoffImageUpload;

use Filament\Forms\Components\Component;
use Filament\Forms\Components\Concerns\HasState;
use Filament\Forms\Components\Concerns\HasName;
use Illuminate\Support\Facades\Storage;

class HandoffImageUpload extends Component
{
    use HasState;
    use HasName;

    protected string $view = 'handoff-image-upload::input';

    protected ?string $imagePath = '';
    protected string $imageUrl = '';
    protected ?string $previousState = null;
    protected ?string $imageToDelete = null; // Store image path to delete at save time

    protected string $storageDisk = 'public';


    final public function __construct(string $name)
    {
        $this->name($name);
        $this->statePath($name);
    }

    public static function make(string $name): static
    {

        $static = app(static::class, ['name' => $name]);
        $static->configure();

        // Use afterStateUpdated instead of beforeStateDehydrated for more reliable deletion
        $static->afterStateUpdated(function ($state, $old, $component) {
            // Handle image removal (when state becomes null)
            if ($old && ($state === null || $state === '')) {
                // Store the image that should be deleted when form is saved
                $component->imageToDelete = $old;
                \Log::info("Marked image for removal via afterStateUpdated: {$old}");
            }
            // Handle image replacement (when old image exists and new one is different)
            elseif ($old && $old !== $state && !empty(trim($old))) {
                // Store the image that should be deleted when form is saved
                $component->imageToDelete = $old;
                \Log::info("Marked image for deletion via afterStateUpdated: {$old}");
            }
        });

        // Keep beforeStateDehydrated as backup for form save
        $static->beforeStateDehydrated(function ($component) {
            // Get the current state from the component
            $currentState = $component->getState();

            // Use current state instead of imagePath if imagePath is null
            $pathToCheck = $component->imagePath ?: $currentState;

            // First, move current image from tmp to final if needed
            if ($pathToCheck && strpos($pathToCheck, 'handoff-images/tmp/') !== false) {
                $finalPath = $component->moveFromTmpToFinal($pathToCheck);
                $component->imagePath = $finalPath;
                $component->imageUrl = Storage::disk($component->storageDisk)->url($finalPath);

                // Update the state directly without calling state() method to avoid recursion
                if ($component->hasContainer()) {
                    $component->state($finalPath);
                }
            }

            // Then handle deletion of previous image if needed
            if ($component->imageToDelete) {
                $component->deletePreviousImage();
            }
        });

        return $static;
    }

    public function getImagePath(): string
    {
        return $this->imagePath;
    }

    public function getImageUrl(): string
    {
        return $this->imageUrl;
    }

    public function setImagePath(string $path): static
    {
        $this->imagePath = $path;
        $this->imageUrl = Storage::disk($this->storageDisk)->url($path);

        $this->state($path);

        return $this;
    }

    public function state($state): static
    {
        // Update internal state tracking
        $this->previousState = $this->imagePath; // Store current as previous
        $this->imagePath = $state;
        if ($state) {
            $this->imageUrl = Storage::disk($this->storageDisk)->url($state);
        } else {
            $this->imageUrl = '';
        }

        // Call the parent state method to update the state only if container is available
        // The parent method will trigger the afterStateUpdated hook which handles deletion marking
        if ($this->hasContainer()) {
            return parent::state($state);
        }

        return $this;
    }

    protected function hasContainer(): bool
    {
        try {
            return isset($this->container);
        } catch (\Error $e) {
            return false;
        }
    }

    /**
     * Move image from temporary directory to final directory when form is saved
     */
    protected function moveFromTmpToFinal(string $path): string
    {
        // Check if the image is in the tmp directory
        if (strpos($path, 'handoff-images/tmp/') !== false) {
            // Generate the final path
            $filename = basename($path);
            $finalPath = 'handoff-images/' . $filename;

            try {
                // Copy the file from tmp to final directory
                if (Storage::disk($this->storageDisk)->exists($path)) {
                    $content = Storage::disk($this->storageDisk)->get($path);
                    Storage::disk($this->storageDisk)->put($finalPath, $content);

                    // Delete the temporary file
                    Storage::disk($this->storageDisk)->delete($path);

                    \Log::info("Successfully moved image from tmp to final: {$path} -> {$finalPath}");
                    return $finalPath;
                }
            } catch (\Exception $e) {
                \Log::error("Error moving image from tmp to final {$path}: " . $e->getMessage());
            }
        }

        return $path; // Return original path if not in tmp or if move failed
    }

    /**
     * Delete the previous image file when form is saved
     */
    protected function deletePreviousImage(): void
    {
        if (!$this->imageToDelete) {
            return;
        }

        $imageToDelete = $this->imageToDelete;

        try {
            if (Storage::disk($this->storageDisk)->exists($imageToDelete)) {
                $deleted = Storage::disk($this->storageDisk)->delete($imageToDelete);
                if (!$deleted) {
                    \Log::warning("Failed to delete previous image at form save: {$imageToDelete}");
                } else {
                    \Log::info("Successfully deleted previous image at form save: {$imageToDelete}");
                }
            } else {
                \Log::info("Previous image file not found at form save: {$imageToDelete}");
            }
        } catch (\Exception $e) {
            // Log the error but continue with the form save process
            \Log::error("Error deleting previous image at form save {$imageToDelete}: " . $e->getMessage());
        } finally {
            // Clear the deletion marker regardless of success/failure
            $this->imageToDelete = null;
        }
    }

}
