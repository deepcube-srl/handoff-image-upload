<?php

namespace Se09deluca\HandoffImageUpload;

use Filament\Forms\Components\Component;

class HandoffImageUpload extends Component {

    protected string $view = 'handoff-image-upload::input';

    public static function make(): static
    {
        return app(static::class);
    }
}
