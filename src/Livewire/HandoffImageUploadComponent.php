<?php

namespace Deepcube\HandoffImageUpload\Livewire;

use Filament\Forms\Components\Component;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Livewire\Component as LivewireComponent;
use Deepcube\HandoffImageUpload\HandoffImageUpload;

class HandoffImageUploadComponent extends LivewireComponent implements HasForms
{
    use InteractsWithForms;

    public $imagePath = '';

    /**
     * Set the image path and update the component state
     */
    public function setImagePath(string $path): void
    {
        $this->imagePath = $path;

        // Find the HandoffImageUpload component in the form
        $component = $this->findHandoffImageUploadComponent($this->getCachedForms());

        if ($component) {
            $component->setImagePath($path);
        }
    }

    /**
     * Find the HandoffImageUpload component in the form
     */
    protected function findHandoffImageUploadComponent(array $forms): ?HandoffImageUpload
    {
        foreach ($forms as $form) {
            foreach ($form->getComponents() as $component) {
                if ($component instanceof HandoffImageUpload) {
                    return $component;
                }

                if (method_exists($component, 'getChildComponents')) {
                    $result = $this->findHandoffImageUploadComponentInChildren($component);

                    if ($result) {
                        return $result;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Find the HandoffImageUpload component in the children of a component
     */
    protected function findHandoffImageUploadComponentInChildren(Component $component): ?HandoffImageUpload
    {
        foreach ($component->getChildComponents() as $child) {
            if ($child instanceof HandoffImageUpload) {
                return $child;
            }

            if (method_exists($child, 'getChildComponents')) {
                $result = $this->findHandoffImageUploadComponentInChildren($child);

                if ($result) {
                    return $result;
                }
            }
        }

        return null;
    }
}
