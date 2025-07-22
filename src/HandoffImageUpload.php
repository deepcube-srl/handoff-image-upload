<?php

namespace Se09deluca\HandoffImageUpload;

use Filament\Forms\Components\Component;
use Filament\Forms\Components\Concerns\HasState;
use Illuminate\Support\Facades\Storage;

class HandoffImageUpload extends Component {
    use HasState;

    protected string $view = 'handoff-image-upload::input';

    protected string $imagePath = '';
    protected string $imageUrl = '';

    public static function make(string $name): static
    {
        $component = app(static::class);
        //$component->name($name);

        return $component;
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
        $this->imageUrl = Storage::url($path);

        $this->state($path);

        return $this;
    }
}
